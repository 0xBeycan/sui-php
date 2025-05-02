<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Utils;
use Sui\Transactions\Data\V2;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\Expiration;
use Sui\Transactions\Commands\Command;
use Sui\Transactions\Commands\Intent;
use Sui\Transactions\Type\ObjectRef;

class Transaction
{
    /**
     * @var array<TransactionPlugin>
     */
    private array $serializationPlugins = [];

    /**
     * @var array<TransactionPlugin>
     */
    private array $buildPlugins = [];

    /**
     * @var array<string, TransactionPlugin>
     */
    private array $intentResolvers = [];

    /**
     * @var array<CallArg>
     */
    private array $inputSection = [];

    /**
     * @var array<Command>
     */
    private array $commandSection = [];

    /**
     * @var array<int>
     */
    private array $availableResults = [];

    /**
     * @var array<mixed>
     */
    private array $pendingPromises = [];

    /**
     * @var array<string, mixed>
     */
    private array $added = [];

    public TransactionData $data;

    /**
     * @param array<mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->data = new TransactionData();
        $this->buildPlugins = $options['buildPlugins'] ?? [];
        $this->serializationPlugins = $options['serializationPlugins'] ?? [];
    }

    /**
     * @param string|array<int> $serialized
     * @return self
     */
    public static function fromKind(string|array $serialized): self
    {
        $tx = new self();
        $tx->data = TransactionData::fromKindBytes(
            is_string($serialized) ? Utils::fromBase64($serialized) : $serialized
        );
        $tx->inputSection = $tx->data->getInputs();
        $tx->commandSection = $tx->data->getCommands();
        return $tx;
    }

    /**
     * @param string|array<int>|self $transaction
     * @return self
     */
    public static function from(string|array|self $transaction): self
    {
        $newTransaction = new self();

        if ($transaction instanceof self) {
            $newTransaction->data = TransactionData::fromArray($transaction->getData());
        } elseif (is_string($transaction) && !str_starts_with($transaction, '{')) {
            $newTransaction->data = TransactionData::fromBytes(
                Utils::fromBase64($transaction)
            );
        } elseif (is_array($transaction)) {
            $newTransaction->data = TransactionData::restore(V2::fromArray($transaction));
        } else {
            throw new \Exception('Invalid transaction');
        }

        $newTransaction->inputSection = $newTransaction->data->getInputs();
        $newTransaction->commandSection = $newTransaction->data->getCommands();

        return $newTransaction;
    }

    /**
     * @param TransactionPlugin $step
     * @return void
     */
    public function addSerializationPlugin(TransactionPlugin $step): void
    {
        $this->serializationPlugins[] = $step;
    }

    /**
     * @param TransactionPlugin $step
     * @return void
     */
    public function addBuildPlugin(TransactionPlugin $step): void
    {
        $this->buildPlugins[] = $step;
    }

    /**
     * @param string $intent
     * @param TransactionPlugin $resolver
     * @return void
     */
    public function addIntentResolver(string $intent, TransactionPlugin $resolver): void
    {
        if (isset($this->intentResolvers[$intent]) && $this->intentResolvers[$intent] !== $resolver) {
            throw new \Exception("Intent resolver for {$intent} already exists");
        }
        $this->intentResolvers[$intent] = $resolver;
    }

    /**
     * @param string $sender
     * @return void
     */
    public function setSender(string $sender): void
    {
        $this->data->setSender($sender);
    }

    /**
     * @param string $sender
     * @return void
     */
    public function setSenderIfNotSet(string $sender): void
    {
        $this->data->setSenderIfNotSet($sender);
    }

    /**
     * @param Expiration|null $expiration
     * @return void
     */
    public function setExpiration(?Expiration $expiration): void
    {
        $this->data->setExpiration($expiration);
    }

    /**
     * @param string|int|float $price
     * @return void
     */
    public function setGasPrice(string|int|float $price): void
    {
        $this->data->getGasData()->setPrice((string)$price);
    }

    /**
     * @param string|int|float $budget
     * @return void
     */
    public function setGasBudget(string|int|float $budget): void
    {
        $this->data->getGasData()->setBudget((string)$budget);
    }

    /**
     * @param string|int|float $budget
     * @return void
     */
    public function setGasBudgetIfNotSet(string|int|float $budget): void
    {
        if (null === $this->data->getGasData()->getBudget()) {
            $this->data->getGasData()->setBudget((string)$budget);
        }
    }

    /**
     * @param string $owner
     * @return void
     */
    public function setGasOwner(string $owner): void
    {
        $this->data->getGasData()->setOwner($owner);
    }

    /**
     * @param array<ObjectRef> $payments
     * @return void
     */
    public function setGasPayment(array $payments): void
    {
        $this->data->getGasData()->setPayment($payments);
    }

    /**
     * @return array<mixed>
     */
    public function getData(): array
    {
        return $this->data->toArray();
    }

    /**
     * @param string $type
     * @param CallArg $arg
     * @return array<string, mixed>
     */
    private function addInput(string $type, CallArg $arg): array
    {
        return $this->data->addInput($type, $arg);
    }

    /**
     * @param Command $command
     * @return Command
     */
    private function addCommand(Command $command): Command
    {
        $resultIndex = count($this->data->getCommands());
        $this->commandSection[] = $command;
        $this->availableResults[] = $resultIndex;
        $this->data->setCommands(array_merge($this->data->getCommands(), [$command]));

        $this->data->mapCommandArguments($resultIndex, function ($arg, $command, $index) {
            if ('Result' === $arg->getKind() && !in_array($arg->getResult(), $this->availableResults)) {
                throw new \Exception("Result { Result: {$arg->getResult()} } is not available to use the current transaction"); // @phpcs:ignore
            }

            if ('NestedResult' === $arg->getKind()) {
                $nestedResult = $arg->getNestedResult();
                if (!in_array($nestedResult[0], $this->availableResults)) {
                    throw new \Exception("Result { NestedResult: [{$nestedResult[0]}, {$nestedResult[1]}] } is not available to use the current transaction"); // @phpcs:ignore
                }
            }

            if ('Input' === $arg->getKind() && $arg->getInput() >= count($this->data->getInputs())) {
                throw new \Exception("Input { Input: {$arg->getInput()} } references an input that does not exist in the current transaction"); // @phpcs:ignore
            }

            return $arg;
        });

        return $command;
    }

    /**
     * @param mixed $command
     * @return mixed
     */
    public function add(mixed $command): mixed
    {
        if (is_callable($command)) {
            // @phpstan-ignore-next-line
            if (isset($this->added[spl_object_hash($command)])) {
                // @phpstan-ignore-next-line
                return $this->added[spl_object_hash($command)];
            }

            $fork = $this->fork();
            $result = $command($fork);

            if (!($result && is_object($result) && method_exists($result, 'then'))) {
                $this->availableResults = $fork->availableResults;
                // @phpstan-ignore-next-line
                $this->added[spl_object_hash($command)] = $result;
                return $result;
            }

            /** @var Intent $placeholder */
            $placeholder = $this->addCommand(new Intent('AsyncTransactionThunk', [], [
                'result' => null
            ]));

            $this->pendingPromises[] = $result->then(function ($result) use ($placeholder): void {
                $placeholder->setData(['result' => $result]);
            });

            $txResult = $this->createTransactionResult(count($this->data->getCommands()) - 1);
            // @phpstan-ignore-next-line
            $this->added[spl_object_hash($command)] = $txResult;
            return $txResult;
        } else {
            $this->addCommand($command);
        }

        return $this->createTransactionResult(count($this->data->getCommands()) - 1);
    }

    /**
     * @return self
     */
    private function fork(): self
    {
        $fork = new self();
        $fork->data = $this->data;
        $fork->serializationPlugins = $this->serializationPlugins;
        $fork->buildPlugins = $this->buildPlugins;
        $fork->intentResolvers = $this->intentResolvers;
        $fork->pendingPromises = $this->pendingPromises;
        $fork->availableResults = $this->availableResults;
        $fork->added = $this->added;
        $this->inputSection = $fork->inputSection;
        $this->commandSection = $fork->commandSection;
        return $fork;
    }

    /**
     * @param int $index
     * @param int $length
     * @return Argument
     */
    private function createTransactionResult(int $index, int $length = PHP_INT_MAX): Argument
    {
        $baseResult = ['$kind' => 'Result', 'Result' => $index];
        $nestedResults = [];

        $nestedResultFor = function (int $resultIndex) use (&$nestedResults, $index) {
            if (!isset($nestedResults[$resultIndex])) {
                $nestedResults[$resultIndex] = [
                    '$kind' => 'NestedResult',
                    'NestedResult' => [$index, $resultIndex]
                ];
            }
            return $nestedResults[$resultIndex];
        };

        return new class($baseResult, $nestedResultFor, $length) { // @phpcs:ignore
            /**
             * @param array<string, mixed> $baseResult
             * @param \Closure $nestedResultFor
             * @param int $length
             */
            public function __construct(
                private array $baseResult,
                private \Closure $nestedResultFor,
                private int $length
            ) {} // @phpcs:ignore

            /**
             * @param string $property
             * @return mixed
             */
            public function __get(string $property): mixed
            {
                if (array_key_exists($property, $this->baseResult)) {
                    return $this->baseResult[$property];
                }

                if ('Symbol.iterator' === $property) {
                    return function () {
                        $i = 0;
                        while ($i < $this->length) {
                            yield ($this->nestedResultFor)($i);
                            $i++;
                        }
                    };
                }

                if (is_numeric($property)) {
                    $resultIndex = (int)$property;
                    if ($resultIndex >= 0) {
                        return ($this->nestedResultFor)($resultIndex);
                    }
                }

                return null;
            }

            /**
             * @param string $property
             * @param mixed $value
             * @return void
             */
            public function __set(string $property, mixed $value): void
            {
                throw new \Exception(
                    'The transaction result is a proxy, and does not support setting properties directly'
                );
            }
        };
    }

    /**
     * @param array<mixed> $options
     * @return string
     */
    public function build(array $options = []): string
    {
        $options = BuildTransactionOptions::fromArray($options);
        $this->prepareForSerialization($options);
        $this->prepareBuild($options);
        return $this->data->build([
            'onlyTransactionKind' => $options->onlyTransactionKind ?? false
        ]);
    }

    /**
     * @param BuildTransactionOptions $options
     * @return void
     */
    private function prepareBuild(BuildTransactionOptions $options): void
    {
        if (!($options->onlyTransactionKind ?? false) && !$this->data->getSender()) {
            throw new \Exception('Missing transaction sender');
        }

        // @phpstan-ignore-next-line
        $this->runPlugins([...$this->buildPlugins, new class extends TransactionPlugin {
            /**
             * @param TransactionData $transactionData
             * @param BuildTransactionOptions $options
             * @param \Closure $next
             */
            public function __construct(
                TransactionData $transactionData,
                BuildTransactionOptions $options,
                \Closure $next
            ) {
                parent::__construct($transactionData, $options, $next);
            }
        }], $options);
    }

    /**
     * @param array<TransactionPlugin> $plugins
     * @param BuildTransactionOptions $options
     * @return void
     */
    private function runPlugins(array $plugins, BuildTransactionOptions $options): void
    {
        $createNext = function (int $i) use ($plugins, $options, &$createNext): \Closure {
            if ($i >= count($plugins)) {
                return function (): void {}; // @phpcs:ignore
            }

            $plugin = $plugins[$i];

            return function () use ($plugin, $options, $createNext, $i): void {
                $next = $createNext($i + 1);
                $calledNext = false;
                $nextResolved = false;

                $plugin->__construct(
                    $this->data,
                    $options,
                    function () use ($i, $next, &$calledNext, &$nextResolved): void {
                        if ($calledNext) {
                            throw new \Exception("next() was call multiple times in TransactionPlugin {$i}");
                        }

                        $calledNext = true;
                        $next();
                        $nextResolved = true;
                    }
                );

                if (!$calledNext) {
                    throw new \Exception("next() was not called in TransactionPlugin {$i}");
                }

                if (!$nextResolved) {
                    throw new \Exception("next() was not awaited in TransactionPlugin {$i}");
                }
            };
        };

        $createNext(0)();
        $this->inputSection = $this->data->getInputs();
        $this->commandSection = $this->data->getCommands();
    }

    /**
     * @param BuildTransactionOptions $options
     * @return void
     */
    public function prepareForSerialization(BuildTransactionOptions $options): void
    {
        $this->waitForPendingTasks();
        $this->sortCommandsAndInputs();

        $intents = [];
        foreach ($this->data->getCommands() as $command) {
            if ($command instanceof Intent) {
                $intents[] = $command->getName();
            }
        }

        $steps = $this->serializationPlugins;

        foreach ($intents as $intent) {
            if (in_array($intent, $options->supportedIntents ?? [])) {
                continue;
            }

            if (!isset($this->intentResolvers[$intent])) {
                throw new \Exception("Missing intent resolver for {$intent}");
            }

            $steps[] = $this->intentResolvers[$intent];
        }

        $this->runPlugins($steps, $options);
    }

    /**
     * @return void
     */
    private function waitForPendingTasks(): void
    {
        while (!empty($this->pendingPromises)) {
            $newPromise = array_shift($this->pendingPromises);
            $newPromise->wait();
        }
    }

    /**
     * @return void
     */
    private function sortCommandsAndInputs(): void
    {
        $unorderedCommands = $this->data->getCommands();
        $unorderedInputs = $this->data->getInputs();

        $orderedCommands = array_merge(
            ...array_map(
                fn($section) => [$section],
                $this->commandSection
            )
        );
        $orderedInputs = array_merge(
            ...array_map(
                fn($section) => [$section],
                $this->inputSection
            )
        );

        if (count($orderedCommands) !== count($unorderedCommands)) {
            throw new \Exception('Unexpected number of commands found in transaction data');
        }

        if (count($orderedInputs) !== count($unorderedInputs)) {
            throw new \Exception('Unexpected number of inputs found in transaction data');
        }

        $filteredCommands = array_filter(
            $orderedCommands,
            fn($cmd) => !($cmd instanceof Intent && 'AsyncTransactionThunk' === $cmd->getName())
        );

        $this->data->setCommands($filteredCommands);
        $this->data->setInputs($orderedInputs);
        $this->commandSection = $filteredCommands;
        $this->inputSection = $orderedInputs;

        $this->data->mapArguments(
            // @phpstan-ignore-next-line
            function ($arg) use ($unorderedInputs, $orderedInputs, $unorderedCommands, $filteredCommands) {
                if ('Input' === $arg->getKind()) {
                    $updated = array_search($unorderedInputs[$arg->getInput()], $orderedInputs);
                    if (false === $updated) {
                        throw new \Exception('Input has not been resolved');
                    }
                    return ['$kind' => 'Input', 'Input' => $updated];
                } elseif ('Result' === $arg->getKind()) {
                    $updated = $this->getOriginalIndex($arg->getResult(), $unorderedCommands, $filteredCommands);
                    return ['$kind' => 'Result', 'Result' => $updated];
                } elseif ('NestedResult' === $arg->getKind()) {
                    $nestedResult = $arg->getNestedResult();
                    $updated = $this->getOriginalIndex($nestedResult[0], $unorderedCommands, $filteredCommands);
                    return ['$kind' => 'NestedResult', 'NestedResult' => [$updated, $nestedResult[1]]];
                }

                return $arg;
            }
        );
    }

    /**
     * @param int $index
     * @param array<Command> $unorderedCommands
     * @param array<Command> $filteredCommands
     * @return int
     */
    private function getOriginalIndex(int $index, array $unorderedCommands, array $filteredCommands): int
    {
        $command = $unorderedCommands[$index];
        if ($command instanceof Intent && 'AsyncTransactionThunk' === $command->getName()) {
            $result = $command->getData()['result'];
            if (null === $result) {
                throw new \Exception('AsyncTransactionThunk has not been resolved');
            }
            return $this->getOriginalIndex($result->getResult(), $unorderedCommands, $filteredCommands);
        }

        $updated = array_search($command, $filteredCommands);
        if (false === $updated) {
            throw new \Exception('Unable to find original index for command');
        }

        return (int) $updated;
    }

    /**
     * Add a pure value to the transaction.
     *
     * @param string $value The value to create a pure argument from
     * @return Argument The created argument
     */
    public function pure(string $value): Argument
    {
        $result = $this->addInput('pure', Inputs::pure($value));
        return new Argument(
            $result['Input'],
            'Input',
            'pure',
            0,
            [],
            false
        );
    }

    /**
     * Get a Pure instance for creating pure values.
     *
     * @return Pure The Pure instance
     */
    public function getPure(): Pure
    {
        return new Pure(fn($value) => $this->pure($value));
    }

    /**
     * Add a new object input to the transaction using the fully-resolved object reference.
     * If you only have an object ID, use `builder.object(id)` instead.
     *
     * @param ObjectRef $objectRef The object reference
     * @return Argument The created argument
     */
    public function objectRef(ObjectRef $objectRef): Argument
    {
        return $this->object(Inputs::objectRef($objectRef));
    }

    /**
     * Add a new receiving input to the transaction using the fully-resolved object reference.
     * If you only have an object ID, use `builder.object(id)` instead.
     *
     * @param ObjectRef $objectRef The object reference
     * @return Argument The created argument
     */
    public function receivingRef(ObjectRef $objectRef): Argument
    {
        return $this->object(Inputs::receivingRef($objectRef));
    }

    /**
     * Add a new shared object input to the transaction using the fully-resolved shared object reference.
     * If you only have an object ID, use `builder.object(id)` instead.
     *
     * @param array{objectId: string, mutable: bool, initialSharedVersion: int|string} $params The parameters
     * @return Argument The created argument
     */
    public function sharedObjectRef(array $params): Argument
    {
        return $this->object(Inputs::sharedObjectRef($params));
    }

    /**
     * Add a new object input to the transaction.
     *
     * @param mixed $value The value to create an object from
     * @return Argument The created argument
     */
    public function object(mixed $value): Argument
    {
        if (is_callable($value)) {
            return $this->object($this->add($value));
        }

        if (is_object($value) && $value instanceof Argument) {
            return $value;
        }

        $id = $this->getIdFromCallArg($value);
        $inserted = null;

        foreach ($this->data->getInputs() as $input) {
            if ($id === $this->getIdFromCallArg($input)) {
                $inserted = $input;
                break;
            }
        }

        if ($inserted) {
            $inserted->getObject()->getSharedObject()->setMutable(
                $inserted->getObject()->getSharedObject()->isMutable() ||
                    $value->getObject()->getSharedObject()->isMutable()
            );

            return new Argument(
                (int) array_search($inserted, $this->data->getInputs()),
                'Input',
                'object',
                0,
                [],
                false
            );
        }

        $input = is_string($value)
            ? new CallArg(
                new Type\ObjectArg(
                    new ObjectRef('', '0', ''),
                    new Type\SharedObject('', '0', false),
                    new ObjectRef('', '0', '')
                ),
                '',
                null,
                new Type\UnresolvedObject(Utils::normalizeSuiAddress($value))
            )
            : $value;

        $result = $this->addInput('object', $input);
        return new Argument(
            $result['Input'],
            'Input',
            'object',
            0,
            [],
            false
        );
    }

    /**
     * Get the ID from a CallArg
     *
     * @param mixed $arg The argument to get the ID from
     * @return string|null The ID or null if not found
     */
    private function getIdFromCallArg(mixed $arg): ?string
    {
        if (is_string($arg)) {
            return Utils::normalizeSuiAddress($arg);
        }

        if (is_object($arg) && $arg instanceof CallArg) {
            return $arg->getObject()->getSharedObject()->getObjectId();
        }

        return null;
    }
}
