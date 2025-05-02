<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Client;
use Sui\Constants;
use Sui\Utils;
use Sui\Bcs\Bcs;
use Sui\Bcs\Map;
use Sui\Type\SuiObjetData;
use Sui\Transactions\Commands\TypeSignature;
use Sui\Transactions\Commands\MoveCall;
use Sui\Transactions\Commands\SplitCoins;
use Sui\Transactions\Commands\TransferObjects;

class JsonRpcResolver
{
    private const MAX_OBJECTS_PER_FETCH = 50;
    private const GAS_SAFE_OVERHEAD = 1000;
    private const MAX_GAS = 50_000_000_000;

    private Client $client;
    private bool $onlyTransactionKind;

    /**
     * Constructor for JsonRpcResolver
     *
     * @param Client $client The Sui client instance
     * @param bool $onlyTransactionKind Whether to only resolve transaction kind
     */
    public function __construct(Client $client, bool $onlyTransactionKind = false)
    {
        $this->client = $client;
        $this->onlyTransactionKind = $onlyTransactionKind;
    }

    /**
     * Resolves transaction data by normalizing inputs, resolving object references,
     * and setting gas configuration
     *
     * @param TransactionData $transactionData The transaction data to resolve
     * @return void
     */
    public function resolveTransactionData(TransactionData $transactionData): void
    {
        $this->normalizeInputs($transactionData);
        $this->resolveObjectReferences($transactionData);

        if (!$this->onlyTransactionKind) {
            $this->setGasPrice($transactionData);
            $this->setGasBudget($transactionData);
            $this->setGasPayment($transactionData);
        }

        $this->validate($transactionData);
    }

    /**
     * Sets the gas price for the transaction if not already set
     *
     * @param TransactionData $transactionData The transaction data
     * @return void
     */
    private function setGasPrice(TransactionData $transactionData): void
    {
        if (!$transactionData->getGasData()->getPrice()) {
            $gasPrice = $this->client->getReferenceGasPrice();
            $transactionData->getGasData()->setPrice((string)$gasPrice);
        }
    }

    /**
     * Sets the gas budget for the transaction if not already set
     *
     * @param TransactionData $transactionData The transaction data
     * @return void
     */
    private function setGasBudget(TransactionData $transactionData): void
    {
        if ($transactionData->getGasData()->getBudget()) {
            return;
        }

        $dryRunResult = $this->client->dryRunTransactionBlock([
            'transactionBlock' => $transactionData->build([
                'overrides' => [
                    'gasData' => [
                        'budget' => (string)self::MAX_GAS,
                        'payment' => []
                    ]
                ]
            ])
        ]);

        if ('success' !== $dryRunResult->effects->status->status) {
            throw new \Exception(
                'Dry run failed, could not automatically determine a budget: ' .
                    $dryRunResult->effects->status->error
            );
        }

        $safeOverhead = self::GAS_SAFE_OVERHEAD * (int)$transactionData->getGasData()->getPrice();
        $baseComputationCostWithOverhead = (int)$dryRunResult->effects->gasUsed->computationCost + $safeOverhead;
        $gasBudget = $baseComputationCostWithOverhead +
            (int)$dryRunResult->effects->gasUsed->storageCost -
            (int)$dryRunResult->effects->gasUsed->storageRebate;

        $transactionData->getGasData()->setBudget(
            (string)($gasBudget > $baseComputationCostWithOverhead ? $gasBudget : $baseComputationCostWithOverhead)
        );
    }

    /**
     * Sets the gas payment for the transaction if not already set
     *
     * @param TransactionData $transactionData The transaction data
     * @return void
     */
    private function setGasPayment(TransactionData $transactionData): void
    {
        if (!$transactionData->getGasData()->getPayment()) {
            $owner = $transactionData->getGasData()->getOwner() ?? $transactionData->getSender();
            $coins = $this->client->getCoins($owner, Constants::SUI_TYPE_ARG);

            $paymentCoins = array_filter($coins->data, function ($coin) use ($transactionData) {
                foreach ($transactionData->getInputs() as $input) {
                    if ($input->getObject()->getImmOrOwnedObject()->getObjectId() === $coin->coinObjectId) {
                        return false;
                    }
                }
                return true;
            });

            if (empty($paymentCoins)) {
                throw new \Exception('No valid gas coins found for the transaction.');
            }

            $transactionData->getGasData()->setPayment(
                array_map(
                    fn($coin) => new Type\ObjectRef(
                        $coin->coinObjectId,
                        $coin->version,
                        $coin->digest
                    ),
                    $paymentCoins
                )
            );
        }
    }

    /**
     * Resolves object references in the transaction data
     *
     * @param TransactionData $transactionData The transaction data
     * @return void
     */
    private function resolveObjectReferences(TransactionData $transactionData): void
    {
        $objectsToResolve = array_filter($transactionData->getInputs(), function ($input) {
            return !($input->getUnresolvedObject()->getVersion() ||
                $input->getUnresolvedObject()->getInitialSharedVersion());
        });

        $dedupedIds = array_unique(array_map(
            fn($input) => Utils::normalizeSuiObjectId($input->getUnresolvedObject()->getObjectId()),
            $objectsToResolve
        ));

        $objectChunks = array_chunk($dedupedIds, self::MAX_OBJECTS_PER_FETCH);
        /** @var array<SuiObjetData> $resolved */
        $resolved = [];

        foreach ($objectChunks as $chunk) {
            /** @var array<SuiObjetData> $result */
            $result = $this->client->multiGetObjects($chunk, ['showOwner' => true]);
            /** @var array<SuiObjetData> $result */
            $resolved = array_merge($resolved, $result);
        }

        $responsesById = array_combine($dedupedIds, $resolved);

        $invalidObjects = array_filter($responsesById, fn($obj) => isset($obj->error));
        if (!empty($invalidObjects)) {
            throw new \Exception(
                'The following input objects are invalid: ' .
                    implode(', ', array_map('json_encode', $invalidObjects))
            );
        }

        $objects = array_map(function ($object) {
            /** @var SuiObjetData $object */
            if (isset($object->error) || !isset($object->data)) {
                throw new \Exception('Failed to fetch object: ' . ($object->error ?? 'Unknown error'));
            }

            $owner = $object->owner;
            if (!isset($owner->value)) {
                throw new \Exception('Failed to fetch object: ' . ($object->error ?? 'Unknown error'));
            }
            $initialSharedVersion = $owner->value;

            return [
                'objectId' => $object->objectId,
                'digest' => $object->digest,
                'version' => $object->version,
                'initialSharedVersion' => $initialSharedVersion
            ];
        }, $resolved);

        $objectsById = array_combine($dedupedIds, $objects);

        foreach ($transactionData->getInputs() as $index => $input) {
            $id = Utils::normalizeSuiAddress($input->getUnresolvedObject()->getObjectId());
            $object = $objectsById[$id] ?? null;

            if (!$object) {
                continue;
            }

            if ($input->getUnresolvedObject()->getInitialSharedVersion() ?? $object['initialSharedVersion']) {
                $updated = Inputs::sharedObjectRef([
                    'objectId' => $id,
                    'initialSharedVersion' => $input->getUnresolvedObject()->getInitialSharedVersion() ??
                        $object['initialSharedVersion'],
                    'mutable' => $this->isUsedAsMutable($transactionData, $index)
                ]);
            } elseif ($this->isUsedAsReceiving($transactionData, $index)) {
                $updated = Inputs::receivingRef(new Type\ObjectRef(
                    $id,
                    $input->getUnresolvedObject()->getVersion() ?? $object['version'],
                    $input->getUnresolvedObject()->getDigest() ?? $object['digest']
                ));
            } else {
                $updated = Inputs::objectRef(new Type\ObjectRef(
                    $id,
                    $input->getUnresolvedObject()->getVersion() ?? $object['version'],
                    $input->getUnresolvedObject()->getDigest() ?? $object['digest']
                ));
            }

            $inputs = $transactionData->getInputs();
            $inputs[$index] = $updated;
            $transactionData->setInputs($inputs);
        }
    }

    /**
     * Normalizes inputs in the transaction data
     *
     * @param TransactionData $transactionData The transaction data
     * @return void
     */
    private function normalizeInputs(TransactionData $transactionData): void
    {
        $inputs = $transactionData->getInputs();
        $commands = $transactionData->getCommands();
        $moveCallsToResolve = [];
        $moveFunctionsToResolve = [];

        foreach ($commands as $command) {
            if ($command instanceof MoveCall) {
                if ($command->getArgumentTypes()) {
                    continue;
                }

                $needsResolution = false;
                foreach ($command->getArguments() as $arg) {
                    if ('Input' === $arg->getKind()) {
                        $input = $inputs[$arg->getInput()];
                        // @phpstan-ignore-next-line
                        if ($input->getUnresolvedPure() || $input->getUnresolvedObject()) {
                            $needsResolution = true;
                            break;
                        }
                    }
                }

                if ($needsResolution) {
                    $functionName = sprintf(
                        '%s::%s::%s',
                        $command->getPackage(),
                        $command->getModule(),
                        $command->getFunction()
                    );
                    $moveFunctionsToResolve[] = $functionName;
                    $moveCallsToResolve[] = $command;
                }
            }

            // Handle wellKnownEncoding pattern
            switch ($command->getKind()) {
                case 'SplitCoins':
                    if ($command instanceof SplitCoins) {
                        foreach ($command->getAmounts() as $amount) {
                            $this->normalizeRawArgument($amount, 'U64', $transactionData);
                        }
                    }
                    break;
                case 'TransferObjects':
                    if ($command instanceof TransferObjects) {
                        $this->normalizeRawArgument(
                            $command->getAddress(),
                            'Address',
                            $transactionData
                        );
                    }
                    break;
            }
        }

        $moveFunctionParameters = [];
        if (!empty($moveFunctionsToResolve)) {
            foreach ($moveFunctionsToResolve as $functionName) {
                [$packageId, $moduleId, $functionId] = explode('::', $functionName);
                $def = $this->client->getNormalizedMoveFunction($packageId, $moduleId, $functionId);

                $moveFunctionParameters[$functionName] = array_map(
                    fn($param) => Serializer::normalizedTypeToMoveTypeSignature(
                        is_array($param->value) || is_string($param->value) ? $param->value : []
                    ),
                    $def->parameters
                );
            }
        }

        foreach ($moveCallsToResolve as $moveCall) {
            $parameters = $moveFunctionParameters[sprintf(
                '%s::%s::%s',
                $moveCall->getPackage(),
                $moveCall->getModule(),
                $moveCall->getFunction()
            )] ?? null;

            if (!$parameters) {
                continue;
            }

            $hasTxContext = Serializer::isTxContext(end($parameters));
            $params = $hasTxContext ? array_slice($parameters, 0, -1) : $parameters;

            $moveCall->setArgumentTypes(array_map(
                fn($param) => new TypeSignature(
                    // @phpstan-ignore-next-line
                    is_array($param['ref']) ? $param['ref'] : [],
                    // @phpstan-ignore-next-line
                    $param['body'] ?? []
                ),
                $params
            ));
        }

        foreach ($commands as $command) {
            if (!$command instanceof MoveCall) {
                continue;
            }

            $moveCall = $command;
            $fnName = sprintf(
                '%s::%s::%s',
                $moveCall->getPackage(),
                $moveCall->getModule(),
                $moveCall->getFunction()
            );
            $params = $moveCall->getArgumentTypes();

            if (!$params) {
                continue;
            }

            if (count($params) !== count($moveCall->getArguments())) {
                throw new \Exception("Incorrect number of arguments for $fnName");
            }

            foreach ($params as $i => $param) {
                $arg = $moveCall->getArguments()[$i];
                if ('Input' !== $arg->getKind()) {
                    continue;
                }

                $input = $inputs[$arg->getInput()];
                // @phpstan-ignore-next-line
                if (!$input->getUnresolvedPure() && !$input->getUnresolvedObject()) {
                    continue;
                }

                // @phpstan-ignore-next-line
                $inputValue = $input->getUnresolvedPure() ?? $input->getUnresolvedObject()->getObjectId();

                $schema = Serializer::getPureBcsSchema($param->getBody());
                if ($schema) {
                    $arg->setType('pure');
                    $inputs[$arg->getInput()] = Inputs::pure($schema->serialize($inputValue)->toBytes());
                    continue;
                }

                if (!is_string($inputValue)) {
                    throw new \Exception(
                        "Expect the argument to be an object id string, got " .
                            json_encode($inputValue, JSON_PRETTY_PRINT)
                    );
                }

                $arg->setType('object');
                // @phpstan-ignore-next-line
                $unresolvedObject = $input->getUnresolvedPure() ?
                    new Type\UnresolvedObject($inputValue) :
                    $input;

                $inputs[$arg->getInput()] = $unresolvedObject;
            }
        }
    }

    /**
     * Validates the transaction data
     *
     * @param TransactionData $transactionData The transaction data to validate
     * @return void
     */
    private function validate(TransactionData $transactionData): void
    {
        foreach ($transactionData->getInputs() as $index => $input) {
            // @phpstan-ignore-next-line
            if (!$input->getObject() && !$input->getPureBytes()) {
                throw new \Exception(
                    "Input at index $index has not been resolved. Expected a Pure or Object input, but found " .
                        json_encode($input)
                );
            }
        }
    }

    /**
     * Normalizes a raw argument in the transaction data
     *
     * @param Type\Argument $arg The argument to normalize
     * @param string $schema The schema to use for normalization
     * @param TransactionData $transactionData The transaction data
     * @return void
     */
    private function normalizeRawArgument(
        Type\Argument $arg,
        string $schema,
        TransactionData $transactionData
    ): void {
        if ('Input' !== $arg->getKind()) {
            return;
        }

        $input = $transactionData->getInputs()[$arg->getInput()];
        if (!$input->getUnresolvedPure()) {
            return;
        }

        $inputs = $transactionData->getInputs();
        $serialized = match ($schema) {
            'U64' => Bcs::u64()->serialize($input->getUnresolvedPure()->getValue()),
            'Address' => Map::address()->serialize($input->getUnresolvedPure()->getValue()),
            default => throw new \Exception("Unknown schema: $schema")
        };
        $inputs[$arg->getInput()] = Inputs::pure($serialized->toBytes());
        $transactionData->setInputs($inputs);
    }

    /**
     * Checks if an input is used as mutable in the transaction
     *
     * @param TransactionData $transactionData The transaction data
     * @param int $index The input index to check
     * @return bool Whether the input is used as mutable
     */
    private function isUsedAsMutable(TransactionData $transactionData, int $index): bool
    {
        $usedAsMutable = false;

        $transactionData->getInputUses($index, function ($arg, $tx) use (&$usedAsMutable): void {
            if ($tx instanceof MoveCall && $tx->getArgumentTypes()) {
                $argIndex = array_search($arg, $tx->getArguments());
                // @phpstan-ignore-next-line
                $usedAsMutable = '&' !== $tx->getArgumentTypes()[$argIndex]->getRef() || $usedAsMutable;
            }

            if (in_array($tx->getKind(), ['MakeMoveVec', 'MergeCoins', 'SplitCoins'])) {
                $usedAsMutable = true;
            }
        });

        return $usedAsMutable;
    }

    /**
     * Checks if an input is used as receiving in the transaction
     *
     * @param TransactionData $transactionData The transaction data
     * @param int $index The input index to check
     * @return bool Whether the input is used as receiving
     */
    private function isUsedAsReceiving(TransactionData $transactionData, int $index): bool
    {
        $usedAsReceiving = false;

        $transactionData->getInputUses($index, function ($arg, $tx) use (&$usedAsReceiving): void {
            if ($tx instanceof MoveCall && $tx->getArgumentTypes()) {
                $argIndex = array_search($arg, $tx->getArguments());
                $usedAsReceiving = $this->isReceivingType(
                    $tx->getArgumentTypes()[$argIndex]
                ) || $usedAsReceiving;
            }
        });

        return $usedAsReceiving;
    }

    /**
     * Checks if a type is a receiving type
     *
     * @param TypeSignature $type The type to check
     * @return bool Whether the type is a receiving type
     */
    private function isReceivingType(TypeSignature $type): bool
    {
        $body = $type->getBody();
        if (!isset($body['datatype'])) {
            return false;
        }

        return '0x2' === $body['datatype']['package'] &&
            'transfer' === $body['datatype']['module'] &&
            'Receiving' === $body['datatype']['type'];
    }
}
