<?php

declare(strict_types=1);

namespace Sui\Transactions\Data;

use Sui\Utils;
use Sui\Transactions\TransactionData;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\ObjectRef;
use Sui\Transactions\Type\ObjectArg;
use Sui\Transactions\Type\SharedObject;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\UnresolvedObject;
use Sui\Transactions\Type\GasData;
use Sui\Transactions\Type\Expiration;
use Sui\Transactions\Commands\MoveCall;
use Sui\Transactions\Commands\TransferObjects;
use Sui\Transactions\Commands\SplitCoins;
use Sui\Transactions\Commands\MergeCoins;
use Sui\Transactions\Commands\Publish;
use Sui\Transactions\Commands\MakeMoveVec;
use Sui\Transactions\Commands\Upgrade;

class V1
{
    /**
     * @var string
     */
    private string $version;

    /**
     * @var string|null
     */
    private ?string $sender;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $expiration;

    /**
     * @var array<string, mixed>
     */
    private array $gasConfig;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $inputs;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $transactions;

    /**
     * @param string $version
     * @param string|null $sender
     * @param array<string, mixed>|null $expiration
     * @param array<string, mixed> $gasConfig
     * @param array<int, array<string, mixed>> $inputs
     * @param array<int, array<string, mixed>> $transactions
     */
    public function __construct(
        string $version,
        ?string $sender,
        ?array $expiration,
        array $gasConfig,
        array $inputs,
        array $transactions
    ) {
        $this->version = $version;
        $this->sender = $sender;
        $this->expiration = $expiration;
        $this->gasConfig = $gasConfig;
        $this->inputs = $inputs;
        $this->transactions = $transactions;
    }

    /**
     * Get the version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get the sender
     *
     * @return string|null
     */
    public function getSender(): ?string
    {
        return $this->sender;
    }

    /**
     * Get the expiration
     *
     * @return array<string, mixed>|null
     */
    public function getExpiration(): ?array
    {
        return $this->expiration;
    }

    /**
     * Get the gas config
     *
     * @return array<string, mixed>
     */
    public function getGasConfig(): array
    {
        return $this->gasConfig;
    }

    /**
     * Get the inputs
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Get the transactions
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTransactions(): array
    {
        return $this->transactions;
    }

    /**
     * Serializes a TransactionData object into V1 format
     *
     * @param TransactionData $transactionData The transaction data to serialize
     * @return self The serialized V1 transaction data
     */
    public static function serializeV1TransactionData(TransactionData $transactionData): self
    {
        $inputs = array_map(
            function ($input, $index) {
                if (isset($input->object)) {
                    if (isset($input->object->immOrOwnedObject)) {
                        return [
                            'kind' => 'Input',
                            'index' => $index,
                            'value' => [
                                'Object' => [
                                    'ImmOrOwned' => [
                                        'digest' => $input->getObject()->getImmOrOwnedObject()->getDigest(),
                                        'version' => $input->getObject()->getImmOrOwnedObject()->getVersion(),
                                        'objectId' => $input->getObject()->getImmOrOwnedObject()->getObjectId(),
                                    ],
                                ],
                            ],
                            'type' => 'object',
                        ];
                    }
                    if (isset($input->object->receiving)) {
                        return [
                            'kind' => 'Input',
                            'index' => $index,
                            'value' => [
                                'Object' => [
                                    'Receiving' => [
                                        'digest' => $input->getObject()->getReceiving()->getDigest(),
                                        'version' => $input->getObject()->getReceiving()->getVersion(),
                                        'objectId' => $input->getObject()->getReceiving()->getObjectId(),
                                    ],
                                ],
                            ],
                            'type' => 'object',
                        ];
                    }
                    if (isset($input->object->sharedObject)) {
                        return [
                            'kind' => 'Input',
                            'index' => $index,
                            'value' => [
                                'Object' => [
                                    'Shared' => [
                                        'mutable' => $input->getObject()->getSharedObject()->isMutable(),
                                        'initialSharedVersion' => $input->getObject()
                                            ->getSharedObject()
                                            ->getInitialSharedVersion(),
                                        'objectId' => $input->getObject()->getSharedObject()->getObjectId(),
                                    ],
                                ],
                            ],
                            'type' => 'object',
                        ];
                    }
                }
                if (isset($input->pure)) {
                    return [
                        'kind' => 'Input',
                        'index' => $index,
                        'value' => [
                            'Pure' => Utils::fromBase64($input->getPureBytes()),
                        ],
                        'type' => 'pure',
                    ];
                }
                if (isset($input->unresolvedPure)) {
                    return [
                        'kind' => 'Input',
                        'type' => 'pure',
                        'index' => $index,
                        'value' => $input->getUnresolvedPure()->getValue(),
                    ];
                }
                if (isset($input->unresolvedObject)) {
                    return [
                        'kind' => 'Input',
                        'type' => 'object',
                        'index' => $index,
                        'value' => $input->getUnresolvedObject()->getObjectId(),
                    ];
                }

                throw new \InvalidArgumentException('Invalid input');
            },
            $transactionData->getInputs(),
            array_keys($transactionData->getInputs())
        );

        $expiration = $transactionData->getExpiration();
        $expirationData = null;
        if (!$expiration->isNone()) {
            $expirationData = ['Epoch' => (int)$expiration->getEpoch()];
        } else {
            $expirationData = ['None' => true];
        }

        $gasData = $transactionData->getGasData();
        $gasConfig = [
            'owner' => $gasData->getOwner(),
            'budget' => $gasData->getBudget(),
            'price' => $gasData->getPrice(),
            'payment' => $gasData->getPayment(),
        ];

        $transactions = array_map(
            function ($command) use ($inputs) {
                if ($command instanceof MakeMoveVec) {
                    return [
                        'kind' => 'MakeMoveVec',
                        'type' => null === $command->getType()
                            ? ['None' => true]
                            : ['Some' => $command->getType()],
                        'objects' => array_map(
                            fn($arg) => self::convertTransactionArgument($arg, $inputs),
                            $command->getElements()
                        ),
                    ];
                }
                if ($command instanceof MergeCoins) {
                    return [
                        'kind' => 'MergeCoins',
                        'destination' => self::convertTransactionArgument($command->getDestination(), $inputs),
                        'sources' => array_map(
                            fn($arg) => self::convertTransactionArgument($arg, $inputs),
                            $command->getSources()
                        ),
                    ];
                }
                if ($command instanceof MoveCall) {
                    return [
                        'kind' => 'MoveCall',
                        'target' => "{$command->getPackage()}::{$command->getModule()}::{$command->getFunction()}",
                        'typeArguments' => $command->getTypeArguments(),
                        'arguments' => array_map(
                            fn($arg) => self::convertTransactionArgument($arg, $inputs),
                            $command->getArguments()
                        ),
                    ];
                }
                if ($command instanceof Publish) {
                    return [
                        'kind' => 'Publish',
                        'modules' => array_map(
                            fn($mod) => Utils::fromBase64($mod),
                            $command->getModules()
                        ),
                        'dependencies' => $command->getDependencies(),
                    ];
                }
                if ($command instanceof SplitCoins) {
                    return [
                        'kind' => 'SplitCoins',
                        'coin' => self::convertTransactionArgument($command->getCoin(), $inputs),
                        'amounts' => array_map(
                            fn($arg) => self::convertTransactionArgument($arg, $inputs),
                            $command->getAmounts()
                        ),
                    ];
                }
                if ($command instanceof TransferObjects) {
                    return [
                        'kind' => 'TransferObjects',
                        'objects' => array_map(
                            fn($arg) => self::convertTransactionArgument($arg, $inputs),
                            $command->getObjects()
                        ),
                        'address' => self::convertTransactionArgument($command->getAddress(), $inputs),
                    ];
                }
                if ($command instanceof Upgrade) {
                    return [
                        'kind' => 'Upgrade',
                        'modules' => array_map(
                            fn($mod) => Utils::fromBase64($mod),
                            $command->getModules()
                        ),
                        'dependencies' => $command->getDependencies(),
                        'packageId' => $command->getPackage(),
                        'ticket' => self::convertTransactionArgument($command->getTicket(), $inputs),
                    ];
                }

                throw new \InvalidArgumentException('Unknown transaction type');
            },
            $transactionData->getCommands()
        );

        return new self(
            '1',
            $transactionData->getSender(),
            $expirationData,
            $gasConfig,
            $inputs,
            $transactions
        );
    }

    /**
     * Converts a transaction argument to V1 format
     *
     * @param Argument $arg The argument to convert
     * @param array<mixed> $inputs The inputs array
     * @return array<mixed> The converted argument
     */
    private static function convertTransactionArgument(Argument $arg, array $inputs): array
    {
        if ('GasCoin' === $arg->getKind()) {
            return ['kind' => 'GasCoin'];
        }
        if ('Result' === $arg->getKind()) {
            return ['kind' => 'Result', 'index' => $arg->getResult()];
        }
        if ('NestedResult' === $arg->getKind()) {
            return [
                'kind' => 'NestedResult',
                'index' => $arg->getNestedResult()[0],
                'resultIndex' => $arg->getNestedResult()[1],
            ];
        }
        if ('Input' === $arg->getKind()) {
            return $inputs[$arg->getInput()];
        }

        throw new \InvalidArgumentException('Invalid argument type');
    }

    /**
     * Creates a TransactionData object from V1 format
     *
     * @param self $data The V1 transaction data
     * @return TransactionData The created TransactionData object
     */
    public static function transactionDataFromV1(self $data): TransactionData
    {
        $expiration = null;
        if ($data->getExpiration()) {
            if (isset($data->getExpiration()['Epoch'])) {
                $expiration = new Expiration($data->getExpiration()['Epoch'], false);
            } else {
                $expiration = new Expiration('0', true);
            }
        }

        if (!$data->getSender()) {
            throw new \InvalidArgumentException('Sender is required');
        }

        if (!$expiration) {
            throw new \InvalidArgumentException('Expiration is required');
        }

        return new TransactionData(
            $data->getSender(),
            new GasData(
                $data->getGasConfig()['owner'],
                $data->getGasConfig()['budget'],
                $data->getGasConfig()['price'],
                $data->getGasConfig()['payment']
            ),
            $expiration,
            array_map(
                function ($input) {
                    if ('Input' === $input['kind']) {
                        if (isset($input['value']['Object'])) {
                            $object = $input['value']['Object'];
                            if (isset($object['ImmOrOwned'])) {
                                return new CallArg(
                                    new ObjectArg(
                                        new ObjectRef(
                                            $object['ImmOrOwned']['objectId'],
                                            (string)$object['ImmOrOwned']['version'],
                                            $object['ImmOrOwned']['digest']
                                        ),
                                        new SharedObject('', '0', false),
                                        new ObjectRef('', '0', '')
                                    ),
                                    '',
                                    null,
                                    new UnresolvedObject('')
                                );
                            }
                            if (isset($object['Shared'])) {
                                return new CallArg(
                                    new ObjectArg(
                                        new ObjectRef('', '0', ''),
                                        new SharedObject(
                                            $object['Shared']['objectId'],
                                            (string)$object['Shared']['initialSharedVersion'],
                                            $object['Shared']['mutable']
                                        ),
                                        new ObjectRef('', '0', '')
                                    ),
                                    '',
                                    null,
                                    new UnresolvedObject('')
                                );
                            }
                            if (isset($object['Receiving'])) {
                                return new CallArg(
                                    new ObjectArg(
                                        new ObjectRef('', '0', ''),
                                        new SharedObject('', '0', false),
                                        new ObjectRef(
                                            $object['Receiving']['objectId'],
                                            (string)$object['Receiving']['version'],
                                            $object['Receiving']['digest']
                                        )
                                    ),
                                    '',
                                    null,
                                    new UnresolvedObject('')
                                );
                            }
                        }

                        if (isset($input['value']['Pure'])) {
                            return new CallArg(
                                new ObjectArg(
                                    new ObjectRef('', '0', ''),
                                    new SharedObject('', '0', false),
                                    new ObjectRef('', '0', '')
                                ),
                                Utils::toBase64($input['value']['Pure']),
                                null,
                                new UnresolvedObject('')
                            );
                        }

                        if ('object' === $input['type']) {
                            return new CallArg(
                                new ObjectArg(
                                    new ObjectRef('', '0', ''),
                                    new SharedObject('', '0', false),
                                    new ObjectRef('', '0', '')
                                ),
                                '',
                                null,
                                new UnresolvedObject($input['value'])
                            );
                        }

                        return new CallArg(
                            new ObjectArg(
                                new ObjectRef('', '0', ''),
                                new SharedObject('', '0', false),
                                new ObjectRef('', '0', '')
                            ),
                            '',
                            null,
                            new UnresolvedObject($input['value'])
                        );
                    }

                    throw new \InvalidArgumentException('Invalid input');
                },
                $data->getInputs()
            ),
            array_map(
                function ($transaction) {
                    switch ($transaction['kind']) {
                        case 'MakeMoveVec':
                            return new MakeMoveVec(
                                array_map(
                                    fn($arg) => self::parseV1TransactionArgument($arg),
                                    $transaction['objects']
                                ),
                                null === $transaction['type']['Some']
                                    ? null
                                    : $transaction['type']['Some']
                            );
                        case 'MergeCoins':
                            return new MergeCoins(
                                self::parseV1TransactionArgument($transaction['destination']),
                                array_map(
                                    fn($arg) => self::parseV1TransactionArgument($arg),
                                    $transaction['sources']
                                )
                            );
                        case 'MoveCall':
                            [$pkg, $mod, $fn] = explode('::', $transaction['target']);
                            return new MoveCall(
                                $pkg,
                                $mod,
                                $fn,
                                $transaction['typeArguments'],
                                array_map(
                                    fn($arg) => self::parseV1TransactionArgument($arg),
                                    $transaction['arguments']
                                )
                            );
                        case 'Publish':
                            return new Publish(
                                array_map(
                                    fn($mod) => Utils::toBase64($mod),
                                    $transaction['modules']
                                ),
                                $transaction['dependencies']
                            );
                        case 'SplitCoins':
                            return new SplitCoins(
                                self::parseV1TransactionArgument($transaction['coin']),
                                array_map(
                                    fn($arg) => self::parseV1TransactionArgument($arg),
                                    $transaction['amounts']
                                )
                            );
                        case 'TransferObjects':
                            return new TransferObjects(
                                array_map(
                                    fn($arg) => self::parseV1TransactionArgument($arg),
                                    $transaction['objects']
                                ),
                                self::parseV1TransactionArgument($transaction['address'])
                            );
                        case 'Upgrade':
                            return new Upgrade(
                                array_map(
                                    fn($mod) => Utils::toBase64($mod),
                                    $transaction['modules']
                                ),
                                $transaction['dependencies'],
                                $transaction['packageId'],
                                self::parseV1TransactionArgument($transaction['ticket'])
                            );
                    }

                    throw new \InvalidArgumentException('Unknown transaction type');
                },
                $data->getTransactions()
            )
        );
    }

    /**
     * Parses a V1 transaction argument
     *
     * @param array<mixed> $arg The argument to parse
     * @return Argument The parsed argument
     */
    private static function parseV1TransactionArgument(array $arg): Argument
    {
        switch ($arg['kind']) {
            case 'GasCoin':
                return new Argument(0, 'GasCoin', '', 0, [], true);
            case 'Result':
                return new Argument(0, 'Result', '', $arg['index']);
            case 'NestedResult':
                return new Argument(0, 'NestedResult', '', 0, [$arg['index'], $arg['resultIndex']]);
            case 'Input':
                return new Argument($arg['index'], 'Input', '', 0);
        }

        throw new \InvalidArgumentException('Invalid argument type');
    }

    /**
     * Converts the V1 transaction data to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'sender' => $this->sender,
            'expiration' => $this->expiration,
            'gasConfig' => $this->gasConfig,
            'inputs' => $this->inputs,
            'transactions' => $this->transactions,
        ];
    }

    /**
     * @param array<mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['version'],
            $data['sender'],
            $data['expiration'],
            $data['gasConfig'],
            $data['inputs'],
            $data['transactions'],
        );
    }
}
