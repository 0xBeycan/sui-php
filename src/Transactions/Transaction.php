<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Bcs\Serialized;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\Command;
use Sui\Transactions\Type\ObjectRef;
use Sui\Transactions\Type\TransactionData;
use Sui\Transactions\Type\NormalizedCallArg;
use Sui\Transactions\Type\TransactionExpiration;
use Sui\Transactions\Plugins\TransactionPlugin;
use Sui\Transactions\Plugins\PluginRegistry;
use Sui\Transactions\Plugins\ResolveTransactionData;

class Transaction
{
    public TransactionDataBuilder $data;

    /**
     * @var TransactionPlugin[]
     */
    private array $serializationPlugins = [];

    /**
     * @var TransactionPlugin[]
     */
    private array $buildPlugins = [];

    /**
     * @var array<string, TransactionPlugin>
     */
    private array $intentResolvers = [];

    /**
     * @var array<CallArg|array<mixed>>
     */
    private array $inputSection = [];

    /**
     * @var array<Command|array<mixed>>
     */
    private array $commandSection = [];

    /**
     * @var Set<int>
     */
    private Set $availableResults;

    /**
     * @var Set<\Closure>
     */
    private Set $pendingPromises;

    /**
     * @var \SplObjectStorage<object, mixed>
     */
    private \SplObjectStorage $added;

    /**
     * @var PureFactory
     */
    private PureFactory $pureFactory;

    /**
     * @var ObjectFactory
     */
    private ObjectFactory $objectFactory;

    /**
     * @var BuildTransactionOptions
     */
    private BuildTransactionOptions $options;

    /**
     * @param BuildTransactionOptions $options
     */
    public function __construct(BuildTransactionOptions $options)
    {
        $this->options = $options;
        $this->added = new \SplObjectStorage();
        $this->availableResults = new Set();
        $this->pendingPromises = new Set();
        $globalPlugins = PluginRegistry::getInstance();
        $this->data = new TransactionDataBuilder();
        $this->buildPlugins = [...$globalPlugins->buildPlugins];
        $this->serializationPlugins = [...$globalPlugins->serializationPlugins];
        $this->pureFactory = PureFactory::create(function (mixed $value): Argument {
            if ($value instanceof Serialized) {
                return $this->addInput('pure', Normalizer::callArg([
                    'Pure' => [
                        'bytes' => $value->toBase64(),
                    ],
                ]));
            }

            if ($value instanceof NormalizedCallArg) {
                return $this->addInput('pure', new CallArg('Pure', $value->value));
            }

            if (is_array($value)) {
                return $this->addInput('pure', Inputs::pure($value));
            }

            return $this->addInput('pure', Normalizer::callArg([
                'UnresolvedPure' => [
                    'value' => $value,
                ],
            ]));
        });
        $this->objectFactory = ObjectFactory::create(function (mixed $value): Argument {
            if (is_callable($value)) {
                return $this->object($this->add($value));
            }

            if ($value instanceof Argument) {
                return $value;
            }

            $id = Utils::getIdFromCallArg($value);

            $insertedArray = array_filter($this->data->inputs, function (CallArg $input) use ($id) {
                return $id === Utils::getIdFromCallArg($input);
            });

            if (count($insertedArray) > 0) {
                /** @var CallArg $inserted */
                $inserted = $insertedArray[0];
                // Upgrade shared object inputs to mutable if needed:
                if (
                    is_object($value) &&
                    isset($value?->Object?->SharedObject) && // @phpcs:ignore
                    isset($inserted?->Object?->SharedObject) // @phpcs:ignore
                ) {
                    // @phpcs:ignore
                    $inserted->Object->SharedObject->mutable =
                        // @phpcs:ignore
                        $inserted->Object->SharedObject->mutable || $value->Object->SharedObject->mutable;
                }
            }

            if (isset($inserted)) {
                $index = array_search($inserted, $this->data->inputs);
                return new Argument('Input', $index, 'object');
            }

            if (is_string($value)) {
                return $this->addInput('object', Normalizer::callArg([
                    'UnresolvedObject' => [
                        'objectId' => Utils::normalizeSuiAddress($value),
                    ],
                ]));
            }

            return $this->addInput('object', $value);
        });
    }

    /**
     * @param BuildTransactionOptions $options
     * @return void
     */
    public function setOptions(BuildTransactionOptions $options): void
    {
        $this->options = $options;
    }

    /**
     * @return TransactionData
     */
    public function getData(): TransactionData
    {
        return $this->data->snapshot();
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
        $this->data->sender = $sender;
    }

    /**
     * Sets the sender only if it has not already been set.
     * This is useful for sponsored transaction flows where the sender may not be the same as the signer address.
     *
     * @param string $sender
     * @return void
     */
    public function setSenderIfNotSet(string $sender): void
    {
        if (!$this->data->sender) {
            $this->data->sender = $sender;
        }
    }

    /**
     * @param TransactionExpiration|null $expiration
     * @return void
     */
    public function setExpiration(TransactionExpiration|null $expiration): void
    {
        $this->data->expiration = $expiration;
    }

    /**
     * @param int|string $price
     * @return void
     */
    public function setGasPrice(int|string $price): void
    {
        $this->data->gasData->price = strval($price);
    }

    /**
     * @param int|string $budget
     * @return void
     */
    public function setGasBudget(int|string $budget): void
    {
        $this->data->gasData->budget = strval($budget);
    }

    /**
     * @param int|string $budget
     * @return void
     */
    public function setGasBudgetIfNotSet(int|string $budget): void
    {
        if (null == $this->data->gasData->budget) {
            $this->data->gasData->budget = strval($budget);
        }
    }

    /**
     * @param string $owner
     * @return void
     */
    public function setGasOwner(string $owner): void
    {
        $this->data->gasData->owner = $owner;
    }

    /**
     * @param array<ObjectRef> $payments
     * @return void
     */
    public function setGasPayment(array $payments): void
    {
        $this->data->gasData->payment = $payments;
    }

    /**
     * @param string $type
     * @param CallArg $input
     * @return Argument
     */
    private function addInput(string $type, CallArg $input): Argument
    {
        $this->inputSection[] = $input;
        return $this->data->addInput($type, $input);
    }

    /**
     * @param mixed $arg
     * @return Argument
     */
    private function normalizeTransactionArgument(mixed $arg): Argument
    {
        if ($arg instanceof Serialized) {
            return $this->pure($arg);
        }

        return $this->resolveArgument($arg);
    }

    /**
     * @param mixed $arg
     * @return Argument
     */
    private function resolveArgument(mixed $arg): Argument
    {
        if (is_callable($arg)) {
            $resolved = $this->add($arg);
            if (is_callable($resolved)) {
                return $this->resolveArgument($resolved);
            }

            return $resolved;
        }

        return $arg;
    }

    /**
     * Splits a coin into multiple amounts.
     *
     * @param mixed $coin The coin to be split.
     * @param array<mixed> $amounts The amounts to split the coin into.
     * @return Proxy
     */
    public function splitCoins(mixed $coin, array $amounts): Proxy
    {
        $command = Commands::splitCoins(
            is_string($coin) ? $this->object($coin) : $this->resolveArgument($coin),
            array_map(function (mixed $amount) {
                if (is_numeric($amount) && is_int($amount)) {
                    return $this->pureFactory->u64($amount);
                }
                return $this->normalizeTransactionArgument($amount);
            }, $amounts),
        );

        $this->addCommand($command);

        return self::createTransactionResult(count($this->data->commands) - 1, count($amounts));
    }

    /**
     * Merges multiple coins into a single coin.
     *
     * @param mixed $destination The destination coin.
     * @param array<mixed> $sources The sources coins.
     * @return mixed
     */
    public function mergeCoins(mixed $destination, array $sources): mixed
    {
        return $this->add(
            Commands::mergeCoins(
                is_string($destination) ? $this->object($destination) : $this->resolveArgument($destination),
                array_map(function (mixed $source) {
                    return is_string($source) ? $this->object($source) : $this->resolveArgument($source);
                }, $sources),
            ),
        );
    }

    /**
     * Publishes a module.
     *
     * @param array<array<int>> $modules The modules to publish.
     * @param array<string> $dependencies The dependencies to publish.
     * @return mixed
     */
    public function publish(array $modules, array $dependencies): mixed
    {
        return $this->add(
            Commands::publish($modules, $dependencies),
        );
    }

    /**
     * Upgrades a module.
     *
     * @param array<array<int>> $modules The modules to upgrade.
     * @param array<string> $dependencies The dependencies to upgrade.
     * @param string $packageId The package ID to upgrade.
     * @param mixed $ticket The ticket to upgrade.
     * @return mixed
     */
    public function upgrade(array $modules, array $dependencies, string $packageId, mixed $ticket): mixed
    {
        return $this->add(
            Commands::upgrade($modules, $dependencies, $packageId, $this->object($ticket)),
        );
    }

    /**
     * @param array<mixed> $input
     * @return mixed
     */
    public function moveCall(array $input): mixed
    {
        if (isset($input['arguments'])) {
            $input['arguments'] = array_map(function (mixed $arg) {
                return $this->normalizeTransactionArgument($arg);
            }, $input['arguments']);
        }
        return $this->add(Commands::moveCall($input));
    }

    /**
     * @param array<mixed> $objects
     * @param mixed $address
     * @return mixed
     */
    public function transferObjects(array $objects, mixed $address): mixed
    {
        return $this->add(
            Commands::transferObjects(
                array_map(function (mixed $obj) {
                    return $this->object($obj);
                }, $objects),
                is_string($address)
                    ? $this->pureFactory->address($address)
                    : $this->normalizeTransactionArgument($address),
            ),
        );
    }

    /**
     * @param array<mixed> $elements
     * @param string|null $type
     * @return mixed
     */
    public function makeMoveVec(array $elements, string $type = null): mixed
    {
        return $this->add(
            Commands::makeMoveVec(array_map(function (mixed $element) {
                return $this->object($element);
            }, $elements), $type),
        );
    }

    /**
     * Returns an argument for the gas coin, to be used in a transaction.
     * @return Argument
     */
    public function gas(): Argument
    {
        return new Argument('GasCoin', true);
    }

    /**
     * @param string|Serialized|array<mixed> $value
     * @return Argument
     */
    public function pure(string|Serialized|array $value): Argument
    {
        return $this->pureFactory->pure($value);
    }

    /**
     * @param string|CallArg|Argument $value
     * @return Argument
     */
    public function object(string|CallArg|Argument $value): Argument
    {
        return $this->objectFactory->object($value);
    }

    /**
     * Add a new object input to the transaction using the fully-resolved object reference.
     * If you only have an object ID, use `builder.object(id)` instead.
     * @param string $objectId
     * @param string $digest
     * @param string $version
     * @return Argument
     */
    public function objectRef(string $objectId, string $digest, string $version): Argument
    {
        return $this->object(Inputs::objectRef($objectId, $digest, $version));
    }

    /**
     * Add a new shared object input to the transaction using the fully-resolved shared object reference.
     * If you only have an object ID, use `builder.object(id)` instead.
     * @param string $objectId
     * @param bool $mutable
     * @param int|string $initialSharedVersion
     * @return Argument
     */
    public function sharedObjectRef(string $objectId, bool $mutable, int|string $initialSharedVersion): Argument
    {
        return $this->object(Inputs::sharedObjectRef($objectId, $mutable, $initialSharedVersion));
    }

    /**
     * @param string $objectId
     * @param string $digest
     * @param string $version
     * @return Argument
     */
    public function receivingRef(string $objectId, string $digest, string $version): Argument
    {
        return $this->object(Inputs::receivingRef($objectId, $digest, $version));
    }

    /**
     * @return void
     */
    private function prepareForSerialization(): void
    {
        $this->waitForPendingTasks();
        $this->sortCommandsAndInputs();
        /** @var Set<string> */
        $intents = new Set();
        foreach ($this->data->commands as $command) {
            if (isset($command->{'$Intent'}->name)) {
                $intents->add($command->{'$Intent'}->name);
            }
        }

        $steps = [...$this->serializationPlugins];

        foreach ($intents->toArray() as $intent) {
            if (in_array($intent, $this->options->supportedIntents ?? [])) {
                continue;
            }

            if (!isset($this->intentResolvers[$intent])) {
                throw new \Exception("Missing intent resolver for {$intent}");
            }

            $steps[] = $this->intentResolvers[$intent];
        }

        $this->runPlugins($steps);
    }

    /**
     * @return string|false
     */
    public function toJSON(): string|false
    {
        $this->prepareForSerialization();
        return json_encode($this->data->snapshot());
    }

    /**
     * @param array<TransactionPlugin> $plugins
     * @return void
     */
    private function runPlugins(array $plugins): void
    {
        $createNext = function (int $i) use (&$plugins, &$createNext) { // @phpcs:ignore
            if ($i >= count($plugins)) {
                return function () { // @phpcs:ignore
                };
            }
            $plugin = $plugins[$i];
            return function () use (&$plugin, &$createNext, $i) { // @phpcs:ignore
                $calledNext = false;
                $nextResolved = false;
                $next = $createNext($i + 1);

                $plugin->run(
                    $this->data,
                    $this->options,
                    function () use (&$calledNext, &$nextResolved, &$next, $i) { // @phpcs:ignore
                        if ($calledNext) {
                            throw new \Exception("next() was call multiple times in TransactionPlugin {$i}");
                        }

                        $calledNext = true;

                        $next();

                        $nextResolved = true;
                    }
                );

                if (!$calledNext) {
                    throw new \Exception("next() was not called in TransactionPlugin $i");
                }

                if (!$nextResolved) {
                    throw new \Exception("next() was not awaited in TransactionPlugin $i");
                }
            };
        };
        $createNext(0)();
        $this->inputSection = $this->data->inputs;
        $this->commandSection = $this->data->commands;
    }

    /**
     * @return void
     */
    private function sortCommandsAndInputs(): void
    {
        $unorderedCommands = $this->data->commands;
        $unorderedInputs = $this->data->inputs;

        $orderedCommands = Utils::flattenArray($unorderedCommands);
        $orderedInputs = Utils::flattenArray($unorderedInputs);

        if (count($orderedCommands) !== count($unorderedCommands)) {
            throw new \Exception('Unexpected number of commands found in transaction data');
        }

        if (count($orderedInputs) !== count($unorderedInputs)) {
            throw new \Exception('Unexpected number of inputs found in transaction data');
        }

        $filteredCommands = array_filter($orderedCommands, function (Command $cmd) {
            return 'AsyncTransactionThunk' !== $cmd->{'$Intent'}?->name;
        });

        $this->data->commands = $filteredCommands;
        $this->data->inputs = $orderedInputs;
        $this->commandSection = $filteredCommands;
        $this->inputSection = $orderedInputs;

        $getOriginalIndex = function (int $index) use (
            &$unorderedCommands,
            &$filteredCommands,
            &$getOriginalIndex
        ): int {
            $command = $unorderedCommands[$index];
            if (isset($command->{'$Intent'}->name) && 'AsyncTransactionThunk' === $command->{'$Intent'}->name) {
                // @phpstan-ignore-next-line
                $result = $command->{'$Intent'}->data->result;

                if (null === $result) {
                    throw new \Exception('AsyncTransactionThunk has not been resolved');
                }

                return $getOriginalIndex($result->Result); // @phpcs:ignore
            }

            $updated = array_search($command, $filteredCommands);

            if (-1 === $updated) {
                throw new \Exception('Unable to find original index for command');
            }

            return (int) $updated;
        };

        $this->data->mapArguments(function (Argument $arg) use (&$orderedInputs, &$getOriginalIndex) {
            if ('Input' === $arg->kind) {
                $updated = array_search($arg->value, $orderedInputs);

                if (-1 === $updated) {
                    throw new \Exception('Input has not been resolved');
                }

                return new Argument('Input', $updated, $arg->type);
            } elseif ('Result' === $arg->kind) {
                $updated = $getOriginalIndex($arg->value);

                return new Argument('Result', $updated, $arg->type);
            } elseif ('NestedResult' === $arg->kind) {
                $updated = $getOriginalIndex($arg->value[0]);

                return new Argument('NestedResult', [$updated, $arg->value[1]], $arg->type);
            }

            return $arg;
        });
    }

    /**
     * Prepare the transaction by validating the transaction data and resolving all inputs
     * so that it can be built into bytes.
     * @return void
     */
    private function prepareBuild(): void
    {
        if (!$this->options->onlyTransactionKind && !$this->data->sender) {
            throw new \Exception('Missing transaction sender');
        }

        $this->runPlugins([...$this->buildPlugins, new ResolveTransactionData()]);
    }

    /**
     * Build the transaction to BCS bytes, and sign it with the provided keypair.
     * @param \Sui\Cryptography\Keypair $signer
     * @return array<string, string>
     */
    public function sign(\Sui\Cryptography\Keypair $signer): array
    {
        return $signer->signTransaction($this->build());
    }

    /**
     * @return void
     */
    private function waitForPendingTasks(): void
    {
        if ($this->pendingPromises->count() > 0) {
            foreach ($this->pendingPromises->toArray() as $promise) {
                $promise();
            }
        }
    }

    /**
     * @return string
     */
    public function getDigest(): string
    {
        $this->prepareBuild();
        return $this->data->getDigest();
    }

    /**
     * Build the transaction to BCS bytes.
     * @return array<int>
     */
    public function build(): array
    {
        $this->prepareForSerialization();
        $this->prepareBuild();
        return $this->data->build([
            'onlyTransactionKind' => $this->options->onlyTransactionKind,
        ]);
    }

    /**
     * @return Transaction
     */
    private function fork(): Transaction
    {
        $fork = new Transaction($this->options);

        $fork->data = $this->data;
        $fork->serializationPlugins = $this->serializationPlugins;
        $fork->buildPlugins = $this->buildPlugins;
        $fork->intentResolvers = $this->intentResolvers;
        $fork->pendingPromises = $this->pendingPromises;
        $fork->availableResults = $this->availableResults->clone();
        $fork->added = $this->added;
        $this->inputSection[] = $fork->inputSection;
        $this->commandSection[] = $fork->commandSection;

        return $fork;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function add(mixed $value): mixed
    {
        if ($value instanceof \Closure) {
            if ($this->added->contains($value)) {
                return $this->added->offsetGet($value);
            }

            $fork = $this->fork();
            $result = $value($fork);

            if (!(is_object($result) && isset($result->then))) {
                $this->availableResults = $fork->availableResults;
                $this->added->attach($value, $result);
                return $result;
            }

            $placeholder = $this->addCommand(
                Normalizer::command(
                    [
                        '$Intent' => [
                            'name' => 'AsyncTransactionThunk',
                            'inputs' => [],
                            'data' => [
                                'result' => null,
                            ],
                        ],
                    ]
                )
            );

            $this->pendingPromises->add(function () use ($result, $placeholder) { // @phpcs:ignore
                $placeholder->value->data->result = $result;
            });

            $txResult = self::createTransactionResult(count($this->data->commands) - 1);
            $this->added->attach($value, $txResult);
            return $txResult;
        } elseif ($value instanceof Command) {
            $this->addCommand($value);
        } elseif (is_array($value)) {
            $this->addCommand(Normalizer::command($value));
        }

        return self::createTransactionResult(count($this->data->commands) - 1);
    }

    /**
     * @param Command $command
     * @return Command
     */
    private function addCommand(Command $command): Command
    {
        $resultIndex = count($this->data->commands);
        $this->commandSection[] = $command;
        $this->availableResults->add($resultIndex);
        $this->data->commands[] = $command;

        $this->data->mapCommandArguments(
            $resultIndex,
            function (Argument $arg): Argument {
                if ('Result' === $arg->kind && !$this->availableResults->has($arg->value)) {
                    throw new \Exception(
                        "Result { Result: {$arg->Result} } is not available to use the current transaction",
                    );
                }

                if ('NestedResult' === $arg->kind && !$this->availableResults->has($arg->value[0])) {
                    throw new \Exception(
                        "Result { NestedResult: [{$arg->value[0]}, {$arg->value[1]}] } is not available to use the current transaction", // @phpcs:ignore
                    );
                }

                if ('Input' === $arg->kind && $arg->value >= count($this->data->inputs)) {
                    throw new \Exception(
                        "Input { Input: {$arg->value} } references an input that does not exist in the current transaction", // @phpcs:ignore
                    );
                }

                return $arg;
            }
        );

        return $command;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isTransaction(mixed $value): bool
    {
        return $value instanceof Transaction;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isTransactionResult(mixed $value): bool
    {
        return $value instanceof Proxy;
    }

    /**
     * Converts from a serialize transaction kind (built with `build({ onlyTransactionKind: true })`)
     * to a `Transaction` class.
     * Supports either a byte array, or base64-encoded bytes.
     * @param string|array<int> $serialized
     * @return Transaction
     */
    public static function fromKind(string|array $serialized): Transaction
    {
        $tx = new Transaction(new BuildTransactionOptions());

        $tx->data = TransactionDataBuilder::fromKindBytes(
            is_string($serialized) ? Utils::fromBase64($serialized) : $serialized,
        );

        $tx->inputSection = $tx->data->inputs;
        $tx->commandSection = $tx->data->commands;

        return $tx;
    }

    /**
     * Converts from a serialized transaction format to a `Transaction` class.
     * There are two supported serialized formats:
     * - A string returned from `Transaction#serialize`.
     * The serialized format must be compatible, or it will throw an error.
     * - A byte array (or base64-encoded bytes) containing BCS transaction data.
     *
     * @param string|array<int>|Transaction $transaction
     * @return Transaction
     */
    public static function from(string|array|Transaction $transaction): Transaction
    {
        $newTransaction = new Transaction(new BuildTransactionOptions());

        if (self::isTransaction($transaction)) {
            /** @var Transaction $transaction */
            $newTransaction->data = new TransactionDataBuilder($transaction->getData());
        } elseif (is_array($transaction)) {
            $newTransaction->data = TransactionDataBuilder::restore($transaction);
        } elseif (is_string($transaction) && str_starts_with($transaction, '{')) {
            $newTransaction->data = TransactionDataBuilder::restore(json_decode($transaction));
        } elseif (is_string($transaction) && !str_starts_with($transaction, '{')) {
            $newTransaction->data = TransactionDataBuilder::fromBytes(Utils::fromBase64($transaction));
        } else {
            throw new \InvalidArgumentException('Invalid transaction');
        }

        $newTransaction->inputSection = $newTransaction->data->inputs;
        $newTransaction->commandSection = $newTransaction->data->commands;

        return $newTransaction;
    }


    /**
     * @param string $name
     * @param TransactionPlugin $step
     * @return void
     */
    public static function registerSerializationPlugin(string $name, TransactionPlugin $step): void
    {
        PluginRegistry::getInstance()->registerSerializationPlugin($name, $step);
    }

    /**
     * @param string $name
     * @return void
     */
    public static function unregisterSerializationPlugin(string $name): void
    {
        PluginRegistry::getInstance()->unregisterSerializationPlugin($name);
    }

    /**
     * @param string $name
     * @param TransactionPlugin $step
     * @return void
     */
    public static function registerBuildPlugin(string $name, TransactionPlugin $step): void
    {
        PluginRegistry::getInstance()->registerBuildPlugin($name, $step);
    }

    /**
     * @param string $name
     * @return void
     */
    public static function unregisterBuildPlugin(string $name): void
    {
        PluginRegistry::getInstance()->unregisterBuildPlugin($name);
    }

    /**
     * @param int $index
     * @param int $length
     * @return Proxy
     */
    public static function createTransactionResult(int $index, int $length = PHP_INT_MAX): Proxy
    {
        $baseResult = (object) [
            '$kind' => 'Result',
            'Result' => $index,
        ];

        $nestedResults = [];

        $nestedResultFor = function (int $resultIndex) use ($index, &$nestedResults): object {
            if (!isset($nestedResults[$resultIndex])) {
                $nestedResults[$resultIndex] = (object) [
                    '$kind' => 'NestedResult',
                    'NestedResult' => [$index, $resultIndex],
                ];
            }
            return $nestedResults[$resultIndex];
        };

        return new Proxy($baseResult, new class($nestedResultFor, $length) { // @phpcs:ignore
            /**
             * @var \Closure
             */
            private \Closure $nestedResultFor;

            /**
             * @var int
             */
            private int $length;

            /**
             * @param \Closure $nestedResultFor
             * @param int $length
             */
            public function __construct(\Closure $nestedResultFor, int $length)
            {
                $this->nestedResultFor = $nestedResultFor;
                $this->length = $length;
            }

            /**
             * @param int $resultIndex
             * @return object
             */
            private function nestedResultFor(int $resultIndex): object
            {
                return ($this->nestedResultFor)($resultIndex);
            }

            /**
             * @param object $target
             * @param string $property
             * @param mixed $value
             * @return void
             */
            public function set(object $target, string $property, mixed $value): void
            {
                throw new \Exception(
                    'The transaction result is a proxy, and does not support setting properties directly',
                );
            }

            /**
             * @param object $target
             * @param string $property
             * @return mixed
             */
            public function get(object $target, string $property): mixed
            {
                // This allows this transaction argument to be used in the singular form:
                if (property_exists($target, $property)) {
                    return Proxy::reflectGet($target, $property);
                }

                // Check if the property is __iterator__ for iteration support
                if ('__iterator__' === $property) {
                    return function () {
                        $i = 0;
                        while ($i < $this->length) {
                            yield $this->nestedResultFor($i);
                            $i++;
                        }
                    };
                }

                // Handle symbol-like properties
                if (Proxy::isSymbol($property)) {
                    return null;
                }

                // Check for numeric property
                if (is_numeric($property)) {
                    $resultIndex = (int)$property;
                    if ($resultIndex < 0 || $resultIndex >= $this->length) {
                        return null;
                    }
                    return $this->nestedResultFor($resultIndex);
                }

                return null;
            }
        });
    }
}
