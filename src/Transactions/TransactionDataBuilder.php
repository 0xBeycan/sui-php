<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Bcs\Map;
use Sui\Transactions\Type\GasData;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\Command;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\TransactionData;

class TransactionDataBuilder extends TransactionData
{
    /**
     * @param TransactionData|null $clone
     */
    public function __construct(?TransactionData $clone = null)
    {
        parent::__construct(
            2,
            $clone?->gasData ?? new GasData(),
            $clone?->inputs ?? [],
            $clone?->commands ?? [],
            $clone?->sender ?? null,
            $clone?->expiration ?? null,
        );
    }

    /**
     * @param array<mixed> $options
     * @return array<mixed>
     */
    public function build(array $options = []): array
    {
        $maxSizeBytes = $options['maxSizeBytes'] ?? PHP_INT_MAX;
        $overrides = $options['overrides'] ?? [];
        $onlyTransactionKind = $options['onlyTransactionKind'] ?? false;

        // TODO validate that inputs and intents are actually resolved
        $inputs = $this->inputs;
        $commands = $this->commands;

        $kind = [
            'ProgrammableTransaction' => [
                'inputs' => $inputs,
                'commands' => $commands,
            ],
        ];

        if ($onlyTransactionKind) {
            return Map::transactionKind()->serialize($kind, ['maxSize' => $maxSizeBytes])->toArray();
        }

        $expiration = $overrides['expiration'] ?? $this->expiration;
        $sender = $overrides['sender'] ?? $this->sender;
        $gasData = array_merge(
            $this->gasData->toArray(),
            $overrides['gasConfig'] ?? [],
            $overrides['gasData'] ?? []
        );

        if (empty($sender)) {
            throw new \Exception('Missing transaction sender');
        }

        if (empty($gasData['budget'])) {
            throw new \Exception('Missing gas budget');
        }

        if (empty($gasData['payment'])) {
            throw new \Exception('Missing gas payment');
        }

        if (empty($gasData['price'])) {
            throw new \Exception('Missing gas price');
        }

        $transactionData = [
            'sender' => Utils::prepareSuiAddress($sender),
            'expiration' => $expiration ?: ['None' => true],
            'gasData' => [
                'payment' => $gasData['payment'],
                'owner' => Utils::prepareSuiAddress($gasData['owner'] ?? $sender),
                'price' => (string)$gasData['price'],
                'budget' => (string)$gasData['budget'],
            ],
            'kind' => [
                'ProgrammableTransaction' => [
                    'inputs' => $inputs,
                    'commands' => $commands,
                ],
            ],
        ];

        return Map::transactionData()->serialize($transactionData, ['maxSize' => $maxSizeBytes])->toArray();
    }


    /**
     * @param string $type
     * @param CallArg $arg
     * @return Argument
     */
    public function addInput(string $type, CallArg $arg): Argument
    {
        $index = count($this->inputs);
        $this->inputs[] = $arg;
        return new Argument('Input', $index, $type);
    }

    /**
     * @param int $index
     * @param \Closure $fn
     * @return void
     */
    public function getInputUses(int $index, \Closure $fn): void
    {
        $this->mapArguments(function (Argument $arg, Command $command) use ($index, $fn) {
            // @phpcs:ignore
            if ('Input' === $arg->kind && $arg->Input === $index) {
                $fn($arg, $command);
            }

            return $arg;
        });
    }

    /**
     * @param \Closure $fn
     * @return void
     */
    public function mapArguments(\Closure $fn): void
    {
        foreach ($this->commands as $commandIndex => $command) {
            $this->mapCommandArguments($commandIndex, $fn);
        }
    }

    /**
     * @param int $index
     * @param \Closure $fn
     * @return void
     */
    public function mapCommandArguments(int $index, \Closure $fn): void
    {
        $command = $this->commands[$index];

        switch ($command->kind) {
            case 'MoveCall':
                $command->value->arguments = array_map(
                    fn(Argument $arg) => $fn($arg, $command, $index),
                    $command->value->arguments
                );
                break;
            case 'TransferObjects':
                $command->value->objects = array_map(
                    fn(Argument $arg) => $fn($arg, $command, $index),
                    $command->value->objects
                );
                $command->value->address = $fn($command->value->address, $command, $index);
                break;
            case 'SplitCoins':
                $command->value->coin = $fn($command->value->coin, $command, $index);
                $command->value->amounts = array_map(
                    fn(Argument $arg) => $fn($arg, $command, $index),
                    $command->value->amounts
                );
                break;
            case 'MergeCoins':
                $command->value->destination = $fn($command->value->destination, $command, $index);
                $command->value->sources = array_map(
                    fn(Argument $arg) => $fn($arg, $command, $index),
                    $command->value->sources
                );
                break;
            case 'MakeMoveVec':
                $command->value->elements = array_map(
                    fn(Argument $arg) => $fn($arg, $command, $index),
                    $command->value->elements
                );
                break;
            case 'Upgrade':
                $command->value->ticket = $fn($command->value->ticket, $command, $index);
                break;
            case '$Intent':
                $inputs = $command->value->inputs;
                $command->value->inputs = [];

                foreach ($inputs as $key => $value) {
                    $command->value->inputs[$key] = is_array($value)
                        ? array_map(fn(Argument $arg) => $fn($arg, $command, $index), $value)
                        : $fn($value, $command, $index);
                }
                break;
            default:
                throw new \Exception('Unexpected transaction kind: ' . $command->kind);
        }
    }

    /**
     * @param int $index
     * @param Command|array<Command> $replacement
     * @param int|null $resultIndex
     * @return void
     */
    public function replaceCommand(int $index, Command | array $replacement, int $resultIndex = null): void
    {
        if (!is_array($replacement)) {
            $this->commands[$index] = $replacement;
            return;
        }

        $sizeDiff = count($replacement) - 1;
        array_splice($this->commands, $index, 1, $replacement);

        if (0 !== $sizeDiff) {
            $this->mapArguments(
                function (
                    Argument $arg,
                    Command $command,
                    int $commandIndex
                ) use (
                    $index,
                    $resultIndex,
                    $sizeDiff,
                    $replacement
                ) {
                    if ($commandIndex < $index + count($replacement)) {
                        return $arg;
                    }

                    switch ($arg->kind) {
                        case 'Result':
                            if ($arg->value === $index) {
                                $arg->value = $resultIndex;
                            }

                            if ($arg->value > $index) {
                                $arg->value += $sizeDiff;
                            }
                            break;

                        case 'NestedResult':
                            if ($arg->value[0] === $index) {
                                $arg->value[0] = $resultIndex;
                            }

                            if ($arg->value[0] > $index) {
                                $arg->value[0] += $sizeDiff;
                            }
                            break;
                    }
                    return $arg;
                }
            );
        }
    }

    /**
     * @return string
     */
    public function getDigest(): string
    {
        $bytes = $this->build(['onlyTransactionKind' => false]);
        return self::getDigestFromBytes($bytes);
    }

    /**
     * @return TransactionData
     */
    public function snapshot(): TransactionData
    {
        return new TransactionData(
            $this->version,
            $this->gasData,
            $this->inputs,
            $this->commands,
            $this->sender,
            $this->expiration
        );
    }

    /**
     * @return TransactionDataBuilder
     */
    public function shallowClone(): TransactionDataBuilder
    {
        return new TransactionDataBuilder($this->snapshot());
    }

    /**
     * @param array<mixed> $bytes
     * @return self
     */
    public static function fromKindBytes(array $bytes): self
    {
        $kind = Map::transactionKind()->parse($bytes);

        // @phpcs:ignore
        if (!isset($kind->ProgrammableTransaction)) {
            throw new \Exception('Unable to deserialize from bytes.');
        }

        // @phpcs:ignore
        $programmableTransaction = $kind->ProgrammableTransaction;

        return self::restore([
            'version' => 2,
            'sender' => null,
            'expiration' => null,
            'gasData' => [
                'budget' => null,
                'owner' => null,
                'payment' => null,
                'price' => null,
            ],
            'inputs' => $programmableTransaction->inputs,
            'commands' => $programmableTransaction->commands,
        ]);
    }

    /**
     * @param array<mixed> $bytes
     * @return self
     */
    public static function fromBytes(array $bytes): self
    {
        $rawData = Map::transactionData()->parse($bytes);

        if (!$rawData) {
            throw new \Exception('Unable to deserialize from bytes.');
        }

        $data = $rawData->V1; // @phpcs:ignore
        $programmableTx = $data?->kind->ProgrammableTransaction;

        if (!$data || !$programmableTx) {
            throw new \Exception('Unable to deserialize from bytes.');
        }

        return self::restore([
            'version' => 2,
            'sender' => $data->sender,
            'expiration' => $data->expiration,
            'gasData' => $data->gasData,
            'inputs' => $programmableTx->inputs,
            'commands' => $programmableTx->commands,
        ]);
    }

    /**
     * @param array<mixed> $data
     * @return self
     */
    public static function restore(array $data): self
    {
        $version = $data['version'] ?? 1;
        if (2 === $version) {
            return new self(Normalizer::transactionData($data));
        } else {
            throw new \Exception(
                'Unable to restore transaction data. Version ' . $version . ' is not supported.'
            );
        }
    }

    /**
     * Generate transaction digest.
     *
     * @param array<mixed> $bytes
     * @return string
     */
    public static function getDigestFromBytes(array $bytes): string
    {
        return Utils::toBase58(Utils::hashTypedData('TransactionData', $bytes));
    }
}
