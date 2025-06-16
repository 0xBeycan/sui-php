<?php

declare(strict_types=1);

namespace Sui\Tests\Transactions;

use Sui\Utils;
use Sui\Bcs\Bcs;
use PHPUnit\Framework\TestCase;
use Sui\Transactions\Inputs;
use Sui\Transactions\Commands;
use Sui\Transactions\Transaction;
use Sui\Transactions\BuildTransactionOptions;
use Sui\Transactions\Type\TransactionExpiration;

class TransactionTest extends TestCase
{
    /**
     * @param int|null $staticObjId
     * @param int|null $staticVer
     * @return Transaction
     */
    public function createTransaction(?int $staticObjId = null, ?int $staticVer = null): Transaction
    {
        $tx = new Transaction(new BuildTransactionOptions());
        $tx->setSender('0x2');
        $tx->setGasPrice(5);
        $tx->setGasBudget(100);
        $tx->setGasPayment([$this->ref($staticObjId, $staticVer)]);
        return $tx;
    }

    /**
     * @param int|null $staticObjId
     * @param int|null $staticVer
     * @return array{objectId: string, version: string, digest: string}
     */
    private function ref(?int $staticObjId = null, ?int $staticVer = null): array
    {
        return [
            'objectId' => str_pad(strval($staticObjId ?? mt_rand(0, 100000)), 64, '0'),
            'version' => $staticVer ?: strval(mt_rand(0, 10000)),
            'digest' => Utils::toBase58(json_decode('[
                0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0,
                1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1,
                2, 3, 4, 5, 6, 7, 8, 9, 1, 2
            ]'))
        ];
    }

    /**
     * @return void
     */
    public function testSerializeEmptyTransaction(): void
    {
        $tx = new Transaction(new BuildTransactionOptions());
        $this->assertNotNull($tx->toJSON());
    }

    /**
     * @return void
     */
    public function testSerializeReceivingTransaction(): void
    {
        $tx = new Transaction(new BuildTransactionOptions());
        $tx->object(Inputs::receivingRef(
            $this->ref()['objectId'],
            $this->ref()['digest'],
            $this->ref()['version']
        ));
        $this->assertNotNull($tx->toJSON());
    }

    /**
     * @return void
     */
    public function testSerializeReceivingTransactionDifferentFromObjectTransaction(): void
    {
        $oref = $this->ref();
        $rtx = new Transaction(new BuildTransactionOptions());
        $rtx->object(Inputs::receivingRef(
            $oref['objectId'],
            $oref['digest'],
            $oref['version']
        ));
        $otx = new Transaction(new BuildTransactionOptions());
        $otx->object(Inputs::objectRef(
            $oref['objectId'],
            $oref['digest'],
            $oref['version']
        ));
        $this->assertNotEquals($rtx->toJSON(), $otx->toJSON());
        $this->assertNotNull($rtx->toJSON());
        $this->assertNotNull($otx->toJSON());
        $this->assertNotEquals($rtx->toJSON(), $otx->toJSON());
    }

    /**
     * @return void
     */
    public function testSerializeAndDeserializeToSameValues(): void
    {
        $tx = new Transaction(new BuildTransactionOptions());
        $tx->add(Commands::splitCoins($tx->gas(), [$tx->pureFactory->u64(100)]));
        $serialized = $tx->toJSON();
        $tx2 = Transaction::from($serialized);
        $this->assertEquals(json_decode($serialized, true), json_decode($tx2->toJSON(), true));
    }

    /**
     * @return void
     */
    public function testTransferWithSplitCommands(): void
    {
        $tx = new Transaction(new BuildTransactionOptions());
        $coin = $tx->add(Commands::splitCoins($tx->gas(), [$tx->pureFactory->u64(100)]));
        $tx->add(Commands::transferObjects([$coin], $tx->object('0x2')));
        $this->assertNotNull($tx->toJSON());
    }

    /**
     * @return void
     */
    public function testSupportsNestedResultsThroughEitherArrayIndexOrDestructuring(): void
    {
        $tx = new Transaction(new BuildTransactionOptions());
        $registerResult = $tx->add(Commands::moveCall([
            'target' => '0x2::game::register',
        ]));
        $nft = $registerResult->kind;
        $account = $registerResult->value;
        $this->assertEquals($nft, $registerResult->kind);
        $this->assertEquals($account, $registerResult->value);
    }

    /**
     * @return void
     */
    public function testBuildsEmptyTransactionOfflineWhenProvidedSufficientData(): void
    {
        $tx = $this->createTransaction(75331, 3516);
        $this->assertEquals($tx->build(), json_decode('[
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 2, 1, 117, 51, 16,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 188,
            13, 0, 0, 0, 0, 0, 0, 32, 0, 1,
            2, 3, 4, 5, 6, 7, 8, 9, 0, 1,
            2, 3, 4, 5, 6, 7, 8, 9, 0, 1,
            2, 3, 4, 5, 6, 7, 8, 9, 1, 2,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 2, 5, 0, 0, 0, 0, 0, 0, 0,
            100, 0, 0, 0, 0, 0, 0, 0, 0
        ]'));
    }

    /**
     * @return void
     */
    public function testSupportsEpochExpiration(): void
    {
        $tx = $this->createTransaction(2343, 43423);
        $tx->setExpiration(new TransactionExpiration('Epoch', null, 1));
        $this->assertEquals($tx->build(), json_decode('[
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 2, 1, 35, 67, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 159,
            169, 0, 0, 0, 0, 0, 0, 32, 0, 1,
            2, 3, 4, 5, 6, 7, 8, 9, 0, 1,
            2, 3, 4, 5, 6, 7, 8, 9, 0, 1,
            2, 3, 4, 5, 6, 7, 8, 9, 1, 2,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 2, 5, 0, 0, 0, 0, 0, 0, 0,
            100, 0, 0, 0, 0, 0, 0, 0, 1, 1,
            0, 0, 0, 0, 0, 0, 0
        ]'));
    }

    /**
     * @return void
     */
    public function testBuildsSplitTransaction(): void
    {
        $tx = $this->createTransaction(23213, 3434);
        $tx->add(Commands::splitCoins($tx->gas(), [$tx->pureFactory->u64(100)]));
        $this->assertEquals($tx->build(), json_decode('[
            0, 0, 1, 0, 8, 100, 0, 0, 0, 0,
            0, 0, 0, 1, 2, 0, 1, 1, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 2, 1, 35, 33, 48, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 106, 13, 0, 0, 0,
            0, 0, 0, 32, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 1, 2, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 2, 5, 0,
            0, 0, 0, 0, 0, 0, 100, 0, 0, 0,
            0, 0, 0, 0, 0
        ]'));
    }

    /**
     * @return void
     */
    public function testBreaksReferenceEquality(): void
    {
        $tx = $this->createTransaction(1212, 2423);
        $tx2 = Transaction::from($tx);
        $tx->setGasBudget(999);
        $this->assertNotEquals($tx->getData(), $tx2->getData());
    }

    /**
     * @return void
     */
    public function testCanDetermineTheTypeOfInputsForBuiltInCommands(): void
    {
        $tx = $this->createTransaction(2332, 3534);
        $tx->splitCoins($tx->gas(), [$tx->pureFactory->u64(100)]);
        $this->assertEquals($tx->build(), json_decode('[
            0, 0, 1, 0, 8, 100, 0, 0, 0, 0,
            0, 0, 0, 1, 2, 0, 1, 1, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 2, 1, 35, 50, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 206, 13, 0, 0, 0,
            0, 0, 0, 32, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 1, 2, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 2, 5, 0,
            0, 0, 0, 0, 0, 0, 100, 0, 0, 0,
            0, 0, 0, 0, 0
        ]'));
    }

    /**
     * @return void
     */
    public function testSupportsPreSerializedInputsAsUint8Array(): void
    {
        $tx = $this->createTransaction(24234, 2344);
        $inputBytes = Bcs::u64()->serialize(100)->toArray();
        $tx->add(Commands::splitCoins($tx->gas(), [$tx->pure($inputBytes)]));
        $this->assertEquals($tx->build(), json_decode('[
            0, 0, 1, 0, 8, 100, 0, 0, 0, 0,
            0, 0, 0, 1, 2, 0, 1, 1, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 2, 1, 36, 35, 64, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 40, 9, 0, 0, 0,
            0, 0, 0, 32, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 0, 1, 2, 3, 4, 5,
            6, 7, 8, 9, 1, 2, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0, 0, 0, 0, 0, 0, 2, 5, 0,
            0, 0, 0, 0, 0, 0, 100, 0, 0, 0,
            0, 0, 0, 0, 0
        ]'));
    }

    /**
     * @return void
     */
    public function testBuildsAMoreComplexInteraction(): void
    {
        $ref = $this->ref(2233, 2323);
        $tx = $this->createTransaction(32434, 324324);
        $coin = $tx->splitCoins($tx->gas(), [100]);
        $tx->add(Commands::mergeCoins($tx->gas(), [$coin, $tx->object(Inputs::objectRef(
            $ref['objectId'],
            $ref['digest'],
            $ref['version']
        ))]));
        $tx->add(Commands::moveCall([
            'target' => '0x2::devnet_nft::mint',
            'typeArguments' => [],
            'arguments' => [
                $tx->pureFactory->string('foo'),
                $tx->pureFactory->string('bar'),
                $tx->pureFactory->string('baz'),
            ],
        ]));
        $this->assertEquals($tx->build(), json_decode('[
            0,0,5,0,8,100,0,0,0,0,0,0,0,1,0,34,51,0,0,0,0,0,0,0,0,0,0,0,0,
            0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,19,9,0,0,0,0,0,0,32,0,1,2,
            3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,1,2,0,4,
            3,102,111,111,0,4,3,98,97,114,0,4,3,98,97,122,3,2,0,1,1,0,0,3,
            0,2,2,0,0,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
            0,0,0,0,0,0,0,0,0,2,10,100,101,118,110,101,116,95,110,102,116,
            4,109,105,110,116,0,3,1,2,0,1,3,0,1,4,0,0,0,0,0,0,0,0,0,0,0,0,
            0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,2,1,50,67,64,0,0,0,0,0,
            0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,228,242,4,0,0,0,
            0,0,32,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,
            9,1,2,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
            0,2,5,0,0,0,0,0,0,0,100,0,0,0,0,0,0,0,0
        ]'));
    }

    /**
     * @return void
     */
    public function testUsesAReceivingArgument(): void
    {
        $tx = $this->createTransaction();
        $tx->object(Inputs::objectRef(
            $this->ref()['objectId'],
            $this->ref()['digest'],
            $this->ref()['version']
        ));
        $coin = $tx->splitCoins($tx->gas(), [100]);
        $tx->add(Commands::mergeCoins($tx->gas(), [$coin, $tx->object(Inputs::objectRef(
            $this->ref()['objectId'],
            $this->ref()['digest'],
            $this->ref()['version']
        ))]));
        $tx->add(Commands::moveCall([
            'target' => '0x2::devnet_nft::mint',
            'typeArguments' => [],
            'arguments' => [
                $tx->object(Inputs::objectRef(
                    $this->ref()['objectId'],
                    $this->ref()['digest'],
                    $this->ref()['version']
                )),
                $tx->object(Inputs::receivingRef(
                    $this->ref()['objectId'],
                    $this->ref()['digest'],
                    $this->ref()['version']
                ))
            ]
        ]));

        $bytes = $tx->build();
        $tx2 = Transaction::from($bytes);
        $bytes2 = $tx2->build();
        $this->assertEquals($bytes, $bytes2);
    }

    /**
     * @return void
     */
    public function testBuildsAMoreComplexInteraction2(): void
    {
        $tx = $this->createTransaction();
        $coin = $tx->splitCoins($tx->gas(), [100]);
        $tx->add(Commands::mergeCoins($tx->gas(), [$coin, $tx->object(Inputs::objectRef(
            $this->ref()['objectId'],
            $this->ref()['digest'],
            $this->ref()['version']
        ))]));
        $tx->add(Commands::moveCall([
            'target' => '0x2::devnet_nft::mint',
            'typeArguments' => [],
            'arguments' => [
                $tx->pureFactory->string('foo'),
                $tx->pureFactory->string('bar'),
                $tx->pureFactory->string('baz'),
            ],
        ]));
        $bytes = $tx->build();
        $tx2 = Transaction::from($bytes);
        $bytes2 = $tx2->build();
        $this->assertEquals($bytes, $bytes2);
    }
}
