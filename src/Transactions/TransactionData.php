<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Utils;
use Sui\Transactions\Commands\Command;
use Sui\Transactions\Commands\MoveCall;
use Sui\Transactions\Commands\TransferObjects;
use Sui\Transactions\Commands\SplitCoins;
use Sui\Transactions\Commands\MergeCoins;
use Sui\Transactions\Commands\MakeMoveVec;
use Sui\Transactions\Commands\Upgrade;

class TransactionData
{
    private const VERSION = '2';

    protected string $sender;

    private GasData $gasData;

    private Expiration $expiration;

    /**
     * @var array<CallArg>
     */
    private array $inputs;

    /**
     * @var array<Command>
     */
    private array $commands;

    /**
     * @param string $sender
     * @param GasData $gasData
     * @param Expiration $expiration
     * @param array<CallArg> $inputs
     * @param array<Command> $commands
     */
    public function __construct(
        string $sender,
        GasData $gasData,
        Expiration $expiration,
        array $inputs,
        array $commands
    ) {
        $this->sender = $sender;
        $this->gasData = $gasData;
        $this->expiration = $expiration;
        $this->inputs = $inputs;
        $this->commands = $commands;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * @param string $sender
     * @return void
     */
    public function setSenderIfNotSet(string $sender): void
    {
        if (!isset($this->sender)) {
            $this->sender = $sender;
        }
    }

    /**
     * @param string $sender
     * @return void
     */
    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @return GasData
     */
    public function getGasData(): GasData
    {
        return $this->gasData;
    }

    /**
     * @param GasData $gasData
     * @return void
     */
    public function setGasData(GasData $gasData): void
    {
        $this->gasData = $gasData;
    }

    /**
     * @return Expiration
     */
    public function getExpiration(): Expiration
    {
        return $this->expiration;
    }

    /**
     * @param Expiration $expiration
     * @return void
     */
    public function setExpiration(Expiration $expiration): void
    {
        $this->expiration = $expiration;
    }

    /**
     * @return array<CallArg>
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @param array<CallArg> $inputs
     * @return void
     */
    public function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }

    /**
     * @return array<Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @param array<Command> $commands
     * @return void
     */
    public function setCommands(array $commands): void
    {
        $this->commands = $commands;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'sender' => $this->sender,
            'gasData' => $this->gasData->toArray(),
            'expiration' => $this->expiration->toArray(),
            'inputs' => array_map(fn(CallArg $input) => $input->toArray(), $this->inputs),
            'commands' => array_map(fn(Command $command) => $command->toArray(), $this->commands),
        ];
    }

    /**
     * @param int $index
     * @param callable(Argument,Command,int):Argument $callback
     * @return void
     */
    public function mapCommandArguments(int $index, callable $callback): void
    {
        $command = $this->commands[$index];

        switch ($command->getKind()) {
            case 'MoveCall':
                /** @var MoveCall $command */
                $command->setArguments(
                    array_map(
                        fn(Argument $argument) => $callback($argument, $command, $index),
                        $command->getArguments()
                    )
                );
                break;
            case 'TransferObjects':
                /** @var TransferObjects $command */
                $command->setObjects(
                    array_map(
                        fn(Argument $object) => $callback($object, $command, $index),
                        $command->getObjects()
                    )
                );
                $command->setAddress($callback($command->getAddress(), $command, $index));
                break;
            case 'SplitCoins':
                /** @var SplitCoins $command */
                $command->setCoin($callback($command->getCoin(), $command, $index));
                $command->setAmounts(
                    array_map(
                        fn(Argument $amount) => $callback($amount, $command, $index),
                        $command->getAmounts()
                    )
                );
                break;
            case 'MergeCoins':
                /** @var MergeCoins $command */
                $command->setDestination($callback($command->getDestination(), $command, $index));
                $command->setSources(
                    array_map(
                        fn(Argument $source) => $callback($source, $command, $index),
                        $command->getSources()
                    )
                );
                break;
            case 'MakeMoveVec':
                /** @var MakeMoveVec $command */
                $command->setElements(
                    array_map(
                        fn(Argument $element) => $callback($element, $command, $index),
                        $command->getElements()
                    )
                );
                break;
            case 'Upgrade':
                /** @var Upgrade $command */
                $command->setTicket($callback($command->getTicket(), $command, $index));
                break;
            case 'Publish':
                // Publish command has no arguments to map
                break;
            default:
                throw new \Exception('Unexpected transaction kind: ' . $command->getKind());
        }
    }


    /**
     * @param callable(Argument, Command, int): Argument $callback
     * @return void
     */
    public function mapArguments(callable $callback): void
    {
        foreach (array_keys($this->commands) as $commandIndex) {
            $this->mapCommandArguments($commandIndex, $callback);
        }
    }

    /**
     * @param int $index
     * @param Command|array<Command> $replacement
     * @param int $resultIndex
     * @return void
     */
    public function replaceCommand(int $index, Command|array $replacement, int $resultIndex = null): void
    {
        if (null === $resultIndex) {
            $resultIndex = $index;
        }

        if (!is_array($replacement)) {
            $this->commands[$index] = $replacement;
            return;
        }

        $sizeDiff = count($replacement) - 1;
        array_splice($this->commands, $index, 1, $replacement);

        if (0 !== $sizeDiff) {
            $this->mapArguments(
                function ($arg, $command, $commandIndex) use ($index, $replacement, $resultIndex, $sizeDiff) {
                    if ($commandIndex < $index + count($replacement)) {
                        return $arg;
                    }

                    switch ($arg->getKind()) {
                        case 'Result':
                            if ($index === $arg->getResult()) {
                                $arg->setResult($resultIndex);
                            }

                            if ($arg->getResult() > $index) {
                                $arg->setResult($arg->getResult() + $sizeDiff);
                            }
                            break;

                        case 'NestedResult':
                            $nestedResult = $arg->getNestedResult();
                            if ($index === $nestedResult[0]) {
                                $nestedResult[0] = $resultIndex;
                                $arg->setNestedResult($nestedResult);
                            }

                            if ($nestedResult[0] > $index) {
                                $nestedResult[0] += $sizeDiff;
                                $arg->setNestedResult($nestedResult);
                            }
                            break;
                    }
                    return $arg;
                }
            );
        }
    }

    /**
     * @param 'object'|'pure' $type
     * @param CallArg $arg
     * @return array<string, int|string>
     */
    public function addInput(string $type, CallArg $arg): array
    {
        $index = count($this->inputs);
        $this->inputs[] = $arg;
        return ['Input' => $index, 'type' => $type, '$kind' => 'Input'];
    }

    /**
     * @param int $index
     * @param callable(Argument, Command): void $fn
     * @return void
     */
    public function getInputUses(int $index, callable $fn): void
    {
        $this->mapArguments(
            function ($arg, $command) use ($index, $fn) {
                if ('Input' === $arg->getKind() && $index === $arg->getInput()) {
                    $fn($arg, $command);
                }

                return $arg;
            }
        );
    }

    /**
     * Builds the transaction data
     *
     * @param array<string, mixed> $options The build options containing:
     *                      - maxSizeBytes?: int
     *                      - overrides?: array{
     *                          expiration?: TransactionExpiration,
     *                          sender?: string,
     *                          gasConfig?: array<string, mixed>,
     *                          gasData?: array<string, mixed>
     *                      }
     *                      - onlyTransactionKind?: bool
     * @return string The serialized transaction data
     */
    public function build(array $options = []): string
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
            return $this->serializeTransactionKind($kind, $maxSizeBytes);
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

        return $this->serializeTransactionData(['V1' => $transactionData], $maxSizeBytes);
    }

    /**
     * @param array<string, mixed> $kind
     * @param int $maxSize
     * @return string
     */
    private function serializeTransactionKind(array $kind, int $maxSize): string
    {
        // TODO: Implement BCS serialization for TransactionKind
        return '';
    }

    /**
     * @param array<string, mixed> $data
     * @param int $maxSize
     * @return string
     */
    private function serializeTransactionData(array $data, int $maxSize): string
    {
        // TODO: Implement BCS serialization for TransactionData
        return '';
    }
}
