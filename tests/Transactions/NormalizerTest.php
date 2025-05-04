<?php

declare(strict_types=1);

namespace Sui\Tests\Transactions;

use PHPUnit\Framework\TestCase;
use Sui\Transactions\Normalizer;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\ObjectRef;
use Sui\Transactions\Type\StructTag;
use Sui\Transactions\Type\GasData;
use Sui\Transactions\Type\SharedObject;
use Sui\Transactions\Type\ObjectArg;
use Sui\Transactions\Type\Command;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\Pure;
use Sui\Transactions\Type\UnresolvedPure;
use Sui\Transactions\Type\UnresolvedObject;
use Sui\Transactions\Type\TypeSignature;
use Sui\Transactions\Type\NormalizedCallArg;
use Sui\Transactions\Type\TransactionData;
use Sui\Transactions\Type\TransactionExpiration;
use Sui\Transactions\Commands\Intent;
use Sui\Transactions\Commands\MoveCall;
use Sui\Transactions\Commands\TransferObjects;
use Sui\Transactions\Commands\SplitCoins;
use Sui\Transactions\Commands\MergeCoins;
use Sui\Transactions\Commands\Publish;
use Sui\Transactions\Commands\MakeMoveVec;
use Sui\Transactions\Commands\Upgrade;

class NormalizerTest extends TestCase
{
    private const ADDRESS = '0x63a7cc78f0506be86fcec6b602695141c6d39001b11444bbe37ba189616dfe59';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    /**
     * @test
     * @return void
     */
    public function testSuiAddress(): void
    {
        $address = '0x2';
        $normalized = Normalizer::suiAddress($address);
        $this->assertIsString($normalized);
        $this->assertEquals(
            $normalized,
            '0x2000000000000000000000000000000000000000000000000000000000000000'
        );
    }

    /**
     * @test
     * @return void
     */
    public function testJsonU64(): void
    {
        $value = '1234567890';
        $normalized = Normalizer::jsonU64($value);
        $this->assertIsString($normalized);
        $this->assertEquals($value, $normalized);
    }

    /**
     * @test
     * @return void
     */
    public function testObjectRef(): void
    {
        $options = [
            'objectId' => self::ADDRESS,
            'version' => '1',
            'digest' => 'digest123'
        ];
        $result = Normalizer::objectRef($options);
        $this->assertInstanceOf(ObjectRef::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testArgument(): void
    {
        $options = ['test' => 'value'];
        $result = Normalizer::argument($options);
        $this->assertInstanceOf(Argument::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testGasData(): void
    {
        $options = [
            'budget' => '1000',
            'price' => '1',
            'owner' => self::ADDRESS,
            'payment' => [
                [
                    'objectId' => self::ADDRESS,
                    'version' => '1',
                    'digest' => 'digest123'
                ]
            ]
        ];
        $result = Normalizer::gasData($options);
        $this->assertInstanceOf(GasData::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testStructTag(): void
    {
        $options = [
            'address' => self::ADDRESS,
            'module' => 'test',
            'name' => 'Test',
            'typeParams' => []
        ];
        $result = Normalizer::structTag($options);
        $this->assertInstanceOf(StructTag::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testTypeSignature(): void
    {
        $options = [
            'body' => 'test',
            'ref' => 'reference'
        ];
        $result = Normalizer::typeSignature($options);
        $this->assertInstanceOf(TypeSignature::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testIntent(): void
    {
        $options = [
            'name' => 'test',
            'inputs' => [
                ['test' => 'value'],
                [
                    [
                        'test' => 'value'
                    ],
                    [
                        'test' => 'value'
                    ]
                ]
            ],
            'data' => [
                'test' => 'value'
            ]
        ];
        $result = Normalizer::intent($options);
        $this->assertInstanceOf(Intent::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testMoveCall(): void
    {
        $options = [
            'package' => self::ADDRESS,
            'module' => 'test',
            'function' => 'test',
            'typeArguments' => [],
            'arguments' => [['test' => 'value']],
            '_argumentTypes' => [
                ['body' => 'test', 'ref' => 'reference']
            ]
        ];
        $result = Normalizer::moveCall($options);
        $this->assertInstanceOf(MoveCall::class, $result);
    }

    /**
     * @return void
     */
    public function testCommandType(): void
    {
        $options = [
            'MoveCall' => [
                'package' => self::ADDRESS,
                'module' => 'test',
                'function' => 'test',
                'typeArguments' => [],
                'arguments' => [['test' => 'value']],
                '_argumentTypes' => [
                    ['body' => 'test', 'ref' => 'reference']
                ]
            ]
        ];
        $result = Normalizer::command($options);
        $this->assertEquals('MoveCall', $result->getKind());
        $this->assertInstanceOf(Command::class, $result);
        $this->assertInstanceOf(MoveCall::class, $result->value);
        $this->assertInstanceOf(MoveCall::class, $result->MoveCall); // @phpcs:ignore
    }

        /**
     * @return void
     */
    public function testCommandTypeRef(): void
    {
        $options = [
            'MoveCall' => [
                'package' => self::ADDRESS,
                'module' => 'test',
                'function' => 'test',
                'typeArguments' => [],
                'arguments' => [['test' => 'value']],
                '_argumentTypes' => [
                    ['body' => 'test', 'ref' => 'reference']
                ]
            ]
        ];
        $result = Normalizer::command($options);
        $this->assertInstanceOf(MoveCall::class, $result->value);
        $result->value = 'sa';
        $this->assertEquals($result->MoveCall, $result->value); // @phpcs:ignore
    }

    /**
     * @test
     * @return void
     */
    public function testMoveCallCommandType(): void
    {
        $options = [
            'package' => self::ADDRESS,
            'module' => 'test',
            'function' => 'test',
            'typeArguments' => [],
            'arguments' => [['test' => 'value']],
            '_argumentTypes' => [
                ['body' => 'test', 'ref' => 'reference']
            ]
        ];
        $result = Normalizer::moveCall($options);
        $this->assertInstanceOf(MoveCall::class, $result);
        $commandMoveCall = $result->toCommand();
        $this->assertEquals('MoveCall', $commandMoveCall->getKind());
        $this->assertInstanceOf(Command::class, $commandMoveCall);
        $this->assertEquals($commandMoveCall->value, $result);
        $this->assertEquals($commandMoveCall->MoveCall, $result); // @phpcs:ignore
        $this->assertEquals($commandMoveCall->MoveCall, $commandMoveCall->value); // @phpcs:ignore
        $this->assertInstanceOf(MoveCall::class, $commandMoveCall->MoveCall); // @phpcs:ignore
        $this->assertInstanceOf(MoveCall::class, $commandMoveCall->value);
    }

    /**
     * @test
     * @return void
     */
    public function testTransferObjects(): void
    {
        $options = [
            'objects' => [['test' => 'value']],
            'address' => ['test' => 'value']
        ];
        $result = Normalizer::transferObjects($options);
        $this->assertInstanceOf(TransferObjects::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testSplitCoins(): void
    {
        $options = [
            'coin' => ['test' => 'value'],
            'amounts' => [['test' => 'value']]
        ];
        $result = Normalizer::splitCoins($options);
        $this->assertInstanceOf(SplitCoins::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testMergeCoins(): void
    {
        $options = [
            'destination' => ['test' => 'value'],
            'sources' => [['test' => 'value']]
        ];
        $result = Normalizer::mergeCoins($options);
        $this->assertInstanceOf(MergeCoins::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testPublish(): void
    {
        $options = [
            'modules' => ['module1', 'module2'],
            'dependencies' => [self::ADDRESS]
        ];
        $result = Normalizer::publish($options);
        $this->assertInstanceOf(Publish::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testMakeMoveVec(): void
    {
        $options = [
            'elements' => [['test' => 'value']],
            'type' => 'test'
        ];
        $result = Normalizer::makeMoveVec($options);
        $this->assertInstanceOf(MakeMoveVec::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testUpgrade(): void
    {
        $options = [
            'modules' => ['module1', 'module2'],
            'dependencies' => [self::ADDRESS],
            'package' => self::ADDRESS,
            'ticket' => ['test' => 'value']
        ];
        $result = Normalizer::upgrade($options);
        $this->assertInstanceOf(Upgrade::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testCommand(): void
    {
        $options = [
            'MoveCall' => [
                'package' => '0x2',
                'module' => 'test',
                'function' => 'test',
                'typeArguments' => [],
                'arguments' => [['test' => 'value']],
                '_argumentTypes' => [
                    ['body' => 'test', 'ref' => 'reference']
                ]
            ]
        ];
        $result = Normalizer::command($options);
        $this->assertEquals('MoveCall', $result->getKind());
        $this->assertInstanceOf(Command::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testSharedObject(): void
    {
        $options = [
            'objectId' => self::ADDRESS,
            'initialSharedVersion' => '1',
            'mutable' => true
        ];
        $result = Normalizer::sharedObject($options);
        $this->assertInstanceOf(SharedObject::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testObjectArg(): void
    {
        $options = [
            'ImmOrOwnedObject' => [
                'objectId' => self::ADDRESS,
                'version' => '1',
                'digest' => 'digest123'
            ]
        ];
        $result = Normalizer::objectArg($options);
        $this->assertInstanceOf(ObjectArg::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testPure(): void
    {
        $options = ['bytes' => 'test'];
        $result = Normalizer::pure($options);
        $this->assertInstanceOf(Pure::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testUnresolvedPure(): void
    {
        $options = ['value' => 'test'];
        $result = Normalizer::unresolvedPure($options);
        $this->assertInstanceOf(UnresolvedPure::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testUnresolvedObject(): void
    {
        $options = [
            'objectId' => self::ADDRESS,
            'version' => '1',
            'digest' => 'digest123',
            'initialSharedVersion' => '1'
        ];
        $result = Normalizer::unresolvedObject($options);
        $this->assertInstanceOf(UnresolvedObject::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testCallArg(): void
    {
        $options = [
            'Object' => [
                'ImmOrOwnedObject' => [
                    'objectId' => self::ADDRESS,
                    'version' => '1',
                    'digest' => 'digest123'
                ]
            ]
        ];
        $result = Normalizer::callArg($options);
        $this->assertInstanceOf(CallArg::class, $result);
        $this->assertEquals($result->Object->ImmOrOwnedObject->digest, 'digest123'); // @phpcs:ignore
        $this->assertEquals($result->toArray()['Object']['ImmOrOwnedObject']->digest, 'digest123');
    }

    /**
     * @test
     * @return void
     */
    public function testNormalizedCallArg(): void
    {
        $options = [
            'Object' => [
                'ImmOrOwnedObject' => [
                    'objectId' => self::ADDRESS,
                    'version' => '1',
                    'digest' => 'digest123'
                ]
            ]
        ];
        $result = Normalizer::normalizedCallArg($options);
        $this->assertInstanceOf(NormalizedCallArg::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testTransactionExpiration(): void
    {
        $options = ['None' => true];
        $result = Normalizer::transactionExpiration($options);
        $this->assertInstanceOf(TransactionExpiration::class, $result);
    }

    /**
     * @test
     * @return void
     */
    public function testTransactionExpirationRef(): void
    {
        $options = ['None' => true];
        $options2 = ['Epoch' => '2323'];
        $result = Normalizer::transactionExpiration($options);
        $result2 = Normalizer::transactionExpiration($options2);
        $this->assertEquals($result->None, true); // @phpcs:ignore
        $result->None = false; // @phpcs:ignore
        $this->assertEquals($result->None, false); // @phpcs:ignore
        $this->assertEquals($result->value, false); // @phpcs:ignore
        $this->assertNotEquals($result->value, $result2->value);
    }

    /**
     * @test
     * @return void
     */
    public function testTransactionData(): void
    {
        $options = [
            'version' => 2,
            'gasData' => [
                'budget' => '1000',
                'price' => '1',
                'owner' => self::ADDRESS,
                'payment' => []
            ],
            'inputs' => [
                [
                    'Object' => [
                        'ImmOrOwnedObject' => [
                            'objectId' => self::ADDRESS,
                            'version' => '1',
                            'digest' => 'digest123'
                        ]
                    ]
                ]
            ],
            'commands' => [
                [
                    'MoveCall' => [
                        'package' => self::ADDRESS,
                        'module' => 'test',
                        'function' => 'test',
                        'typeArguments' => [],
                        'arguments' => [['test' => 'value']]
                    ]
                ]
            ],
            'sender' => self::ADDRESS,
            'expiration' => ['None' => true]
        ];
        $result = Normalizer::transactionData($options);
        $this->assertInstanceOf(TransactionData::class, $result);
    }

    /**
     * @return void
     */
    public function testPureRef(): void
    {
        $options = [
            'Pure' => [
                'bytes' => 'test'
            ]
        ];
        $result = Normalizer::callArg($options);
        $this->assertInstanceOf(Pure::class, $result->value);
    }
}
