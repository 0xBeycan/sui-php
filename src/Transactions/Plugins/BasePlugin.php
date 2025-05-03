<?php

declare(strict_types=1);

namespace Sui\Transactions\Plugins;

use Sui\Client;
use Sui\Constants;
use Sui\Bcs\Bcs;
use Sui\Bcs\Type;
use Sui\Bcs\Map as BcsMap;
use Sui\Type\SuiObjetData;
use Sui\Type\CoinStruct;
use Sui\Transactions\Set;
use Sui\Transactions\Map;
use Sui\Transactions\Inputs;
use Sui\Transactions\Utils;
use Sui\Type\Move\NormalizedType;
use Sui\Transactions\Serializer;
use Sui\Transactions\Normalizer;
use Sui\Transactions\Type\Command;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\TypeSignature;
use Sui\Transactions\Type\UnresolvedObject;
use Sui\Transactions\BuildTransactionOptions;
use Sui\Transactions\TransactionDataBuilder;

abstract class BasePlugin
{
    // The maximum objects that can be fetched at once using multiGetObjects.
    private const MAX_OBJECTS_PER_FETCH = 50;

    // An amount of gas (in gas units) that is added to transactions as an overhead to ensure transactions do not fail.
    private const GAS_SAFE_OVERHEAD = 1000;
    private const MAX_GAS = 50_000_000_000;

    protected BuildTransactionOptions $options;
    protected TransactionDataBuilder $builder;

    /**
     * @param BuildTransactionOptions $options
     * @param TransactionDataBuilder $builder
     * @return void
     */
    protected function init(
        BuildTransactionOptions $options,
        TransactionDataBuilder $builder,
    ): void {
        $this->options = $options;
        $this->builder = $builder;
    }

    /**
     * @return void
     */
    protected function setGasPrice(): void
    {
        if (!isset($this->builder->gasData->price)) {
            $this->builder->gasData->price = (string) $this->getClient()->getReferenceGasPrice();
        }
    }

    /**
     * @return void
     */
    protected function setGasBudget(): void
    {
        if (isset($this->builder->gasData->budget)) {
            return;
        }

        $dryRunResult = $this->getClient()->dryRunTransactionBlock($this->builder->build([
            'overrides' => [
                'gasData' => [
                    'budget' => self::MAX_GAS,
                    'payment' => [],
                ],
            ],
        ]));

        if ('success' !== $dryRunResult->effects->status->status) {
            throw new \Exception(
                "Dry run failed, could not automatically determine a budget: {$dryRunResult->effects->status->error}",
            );
        }

        $safeOverhead = self::GAS_SAFE_OVERHEAD * (int) ($this->builder->gasData->price ?? 1);

        $baseComputationCostWithOverhead =
            (int) $dryRunResult->effects->gasUsed->computationCost + $safeOverhead;

        $gasBudget =
            $baseComputationCostWithOverhead +
            (int) $dryRunResult->effects->gasUsed->storageCost -
            (int) $dryRunResult->effects->gasUsed->storageRebate;

        $this->builder->gasData->budget = (string) ($gasBudget > $baseComputationCostWithOverhead ? $gasBudget : $baseComputationCostWithOverhead); // phpcs:ignore
    }

    /**
     * @return void
     */
    protected function setGasPayment(): void
    {
        if (!isset($this->builder->gasData->payment)) {
            $sender = $this->builder->gasData->owner ?? $this->builder->sender;

            if (null === $sender) {
                throw new \Exception('No sender or owner provided for the transaction.');
            }

            $coins = $this->getClient()->getCoins(
                $sender,
                Constants::SUI_TYPE_ARG,
            );

            $mappedCoins = array_map(function (CoinStruct $coin) {
                return [
                    'objectId' => $coin->coinObjectId,
                    'digest' => $coin->digest,
                    'version' => $coin->version,
                ];
            }, $coins->data);

            $paymentCoins = array_filter($mappedCoins, function (array $coin) {

                $matchingInput = array_filter($this->builder->inputs, function (CallArg $input) use ($coin) {
                    if (isset($input->value->objectId)) {
                        return $coin['objectId'] === $input->value->objectId;
                    }

                    return false;
                });

                return 0 === count($matchingInput);
            });

            if (0 === count($paymentCoins)) {
                throw new \Exception('No valid gas coins found for the transaction.');
            }

            $this->builder->gasData->payment = array_map(function (array $payment) {
                return Normalizer::objectRef([
                    'objectId' => $payment['objectId'],
                    'digest' => $payment['digest'],
                    'version' => $payment['version'],
                ]);
            }, $paymentCoins);
        }
    }

    /**
     * @return void
     */
    protected function resolveObjectReferences(): void
    {
        // Keep track of the object references that will need to be resolved at the end of the transaction.
        // We keep the input by-reference to avoid needing to re-resolve it:
        $objectsToResolve = array_filter($this->builder->inputs, function (CallArg $input) {
            return (
                'UnresolvedObject' === $input->kind &&
                // @phpstan-ignore-next-line
                !($input->value->version || $input->value->initialSharedVersion)
            );
        });

        $dedupedIds = array_map(function (CallArg $input) {
            // @phpstan-ignore-next-line
            return Utils::normalizeSuiAddress($input->value->objectId);
        }, $objectsToResolve);

        $objectChunks = count($dedupedIds) ? Utils::chunk($dedupedIds, self::MAX_OBJECTS_PER_FETCH) : [];

        /** @var SuiObjetData[] */
        $resolved = Utils::flattenArray(
            array_map(function (array $chunk) {
                return $this->getClient()->multiGetObjects($chunk, ['showOwner' => true]);
            }, $objectChunks)
        );

        $responsesById = new Map(
            array_map(
                function (string $id, int $index) use ($resolved) {
                    return [$id, $resolved[$index]];
                },
                $dedupedIds,
                array_keys($dedupedIds)
            ),
        );

        $invalidObjects = array_filter(array_map(function (mixed $obj) {
            return !is_array($obj);
        }, $responsesById->toArray()));

        // @phpstan-ignore-next-line
        if (count($invalidObjects)) {
            throw new \Exception("The following input objects are invalid: " . implode(', ', $invalidObjects));
        }

        $objects = array_map(function (SuiObjetData $object) {
            $owner = $object->owner;
            $initialSharedVersion = $owner && $owner->value;
            return [
                'objectId' => $object->objectId,
                'digest' => $object->digest,
                'version' => $object->version,
                'initialSharedVersion' => $initialSharedVersion,
            ];
        }, $resolved);

        /** @var Map<string, mixed> */
        $objectsById = new Map(
            array_map(function (mixed $index) use ($objects, $dedupedIds) {
                return [$dedupedIds[$index], $objects[$index]];
            }, array_keys($dedupedIds)),
        );

        foreach ($this->builder->inputs as $index => $input) {
            if ('UnresolvedObject' !== $input->kind) {
                continue;
            }

            /** @var UnresolvedObject $value */
            $value = $input->value;

            $id = Utils::normalizeSuiAddress($value->objectId);
            $object = $objectsById->get($id);

            if ($value->initialSharedVersion || $object['initialSharedVersion']) {
                $updated = Inputs::SharedObjectRef(
                    $id,
                    $this->isUsedAsMutable($index),
                    $value->initialSharedVersion ?? $object['initialSharedVersion']
                );
            } elseif ($this->isUsedAsReceiving($index)) {
                $updated = Inputs::ReceivingRef(
                    $id,
                    $value->digest ?? $object['digest'],
                    $value->version ?? $object['version'],
                );
            }

            $this->builder->inputs[$index] =
                $updated ??
                Inputs::ObjectRef(
                    $id,
                    $value->digest ?? $object['digest'],
                    $value->version ?? $object['version'],
                );
        }
    }

    /**
     * @return void
     */
    protected function normalizeInputs(): void
    {
        $inputs = $this->builder->inputs;
        $commands = $this->builder->commands;
        $moveCallsToResolve = [];
        /** @var Set<string> */
        $moveFunctionsToResolve = new Set();

        foreach ($commands as $command) {
            // Special case move call:
            if ('MoveCall' === $command->kind) {
                // Determine if any of the arguments require encoding.
                // - If they don't, then this is good to go.
                // - If they do, then we need to fetch the normalized move module.

                // If we already know the argument types, we don't need to resolve them again
                if (isset($command->value->_argumentTypes)) {
                    return;
                }

                $inputs = array_map(function (Argument $arg) use ($inputs) {
                    if ('Input' === $arg->kind) {
                        return $inputs[$arg->value];
                    }
                    return null;
                }, $command->value->arguments);

                $needsResolution = array_filter($inputs, function (CallArg|null $input) {
                    return $input?->UnresolvedPure || $input?->UnresolvedObject; // phpcs:ignore
                });

                if (count($needsResolution) > 0) {
                    $functionName = "{$command->value->package}::{$command->value->module}::{$command->value->function}"; // phpcs:ignore
                    $moveFunctionsToResolve->add($functionName);
                    $moveCallsToResolve[] = $command->value;
                }
            }

            // Special handling for values that where previously encoded using the wellKnownEncoding pattern.
            // This should only happen when transaction data was hydrated from an old version of the SDK
            switch ($command->kind) {
                case 'SplitCoins':
                    foreach ($command->value->amounts as $amount) {
                        $this->normalizeRawArgument($amount, Bcs::u64());
                    }
                    break;
                case 'TransferObjects':
                    $this->normalizeRawArgument($command->value->address, BcsMap::address());
                    break;
            }
        }

        /** @var Map<string, TypeSignature[]> */
        $moveFunctionParameters = new Map();

        if ($moveFunctionsToResolve->count() > 0) {
            $client = $this->getClient();
            array_map(function (string $functionName) use ($client, &$moveFunctionParameters) { // phpcs:ignore
                $parts = explode('::', $functionName);
                $packageId = $parts[0];
                $moduleId = $parts[1];
                $functionId = $parts[2];
                $def = $client->getNormalizedMoveFunction($packageId, $moduleId, $functionId);

                $moveFunctionParameters->set($functionName, array_map(function (NormalizedType $type) {
                    return Serializer::normalizedTypeToMoveTypeSignature($type);
                }, $def->parameters));
            }, $moveFunctionsToResolve->toArray());
        }

        if (count($moveCallsToResolve) > 0) {
            array_map(function (Command $moveCall) use ($moveFunctionParameters) { // phpcs:ignore
                $parameters = $moveFunctionParameters->get(
                    "{$moveCall->value->package}::{$moveCall->value->module}::{$moveCall->value->function}",
                );

                if (null === $parameters) {
                    return;
                }

                // Entry functions can have a mutable reference to an instance of the TxContext
                // struct defined in the TxContext module as the last parameter. The caller of
                // the function does not need to pass it in as an argument.
                $hasTxContext = count($parameters) > 0 &&  Serializer::isTxContext($parameters[count($parameters) - 1]);
                $params = $hasTxContext ? array_slice($parameters, 0, count($parameters) - 1) : $parameters;

                $moveCall->value->_argumentTypes = $params;
            }, $moveCallsToResolve);
        }

        foreach ($commands as $command) {
            if ('MoveCall' !== $command->kind) {
                return;
            }

            $moveCall = $command->value;
            $fnName = "{$moveCall->package}::{$moveCall->module}::{$moveCall->function}";
            $params = $moveCall->_argumentTypes;

            if (null === $params) {
                return;
            }

            if (count($params) !== count($command->value->arguments)) {
                throw new \Exception("Incorrect number of arguments for {$fnName}");
            }

            foreach ($params as $i => $param) {
                $arg = $moveCall->arguments[$i];
                if ('Input' !== $arg->kind) {
                    return;
                }

                $input = $this->builder->inputs[$arg->value];

                // Skip if the input is already resolved
                if ('UnresolvedPure' !== $input->kind && 'UnresolvedObject' !== $input->kind) {
                    return;
                }

                $inputValue = $input->UnresolvedPure ? $input->value : $input->UnresolvedObject->objectId; // phpcs:ignore

                $schema = Serializer::getPureBcsSchema($param->body);
                if (null !== $schema) {
                    $arg->type = 'pure';
                    $index = array_search($input, $inputs, true);
                    $this->builder->inputs[$index] = Inputs::Pure($schema->serialize($inputValue)->toArray());
                    return;
                }

                if (is_string($inputValue)) {
                    throw new \Exception(
                        "Expect the argument to be an object id string, got {$inputValue}",
                    );
                }

                $arg->type = 'object';

                $valueKind = isset($input->value->kind) ? $input->value->kind : null;

                $unresolvedObject = $input->kind === $valueKind
                    ? Normalizer::callArg([
                        'UnresolvedPure' => [
                            'objectId' => $inputValue,
                        ]
                    ])
                    : $input;

                $this->builder->inputs[$arg->value] = $unresolvedObject;
            }
        }
    }

    /**
     * @return void
     */
    protected function validate(): void
    {
        foreach ($this->builder->inputs as $index => $input) {
            if ('Object' !== $input->kind && 'Pure' !== $input->kind) {
                throw new \Exception(
                    "Input at index {$index} has not been resolved.  Expected a Pure or Object input, but found {$input->kind}", // phpcs:ignore
                );
            }
        }
    }

    /**
     * @param Argument $arg
     * @param Type $schema
     * @return void
     */
    protected function normalizeRawArgument(
        Argument $arg,
        Type $schema,
    ): void {
        if ('Input' !== $arg->kind) {
            return;
        }

        $input = $this->builder->inputs[$arg->value];

        if ('UnresolvedPure' !== $input->kind) {
            return;
        }

        // @phpstan-ignore-next-line
        $this->builder->inputs[$arg->value] = Inputs::Pure($schema->serialize($input->value->value)->toArray());
    }

    /**
     * @param int $index
     * @return bool
     */
    protected function isUsedAsMutable(int $index): bool
    {
        $usedAsMutable = false;

        $this->builder->getInputUses($index, function (Argument $arg, Command $tx) use (&$usedAsMutable) { // phpcs:ignore
            if ('MoveCall' === $tx->kind && $tx->value->_argumentTypes) {
                $argIndex = array_search($arg, $tx->value->arguments);
                $usedAsMutable =  '&' !== $tx->value->_argumentTypes[$argIndex]->ref || $usedAsMutable;
            }

            if ('MakeMoveVec' === $tx->kind || 'MergeCoins' === $tx->kind || 'SplitCoins' === $tx->kind) {
                $usedAsMutable = true;
            }
        });

        return $usedAsMutable;
    }

    /**
     * @param TypeSignature $type
     * @return bool
     */
    protected function isReceivingType(TypeSignature $type): bool
    {
        if (!is_object($type->body) || !isset($type->body->datatype)) {
            return false;
        }

        return (
            '0x2' === $type->body->datatype->package &&
            'transfer' === $type->body->datatype->module &&
            'Receiving' === $type->body->datatype->type
        );
    }

    /**
     * @param int $index
     * @return bool
     */
    protected function isUsedAsReceiving(int $index): bool
    {
        $usedAsReceiving = false;

        $this->builder->getInputUses($index, function (Argument $arg, Command $tx) use (&$usedAsReceiving) { // phpcs:ignore
            if ('MoveCall' === $tx->kind && $tx->value->_argumentTypes) {
                $argIndex = array_search($arg, $tx->value->arguments);
                $usedAsReceiving = $this->isReceivingType($tx->value->_argumentTypes[$argIndex]) || $usedAsReceiving;
            }
        });

        return $usedAsReceiving;
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        if (!isset($this->options->client)) {
            throw new \Exception(
                "No sui client passed to Transaction#build, but transaction data was not sufficient to build offline.",
            );
        }

        return $this->options->client;
    }
}
