<?php

declare(strict_types=1);

namespace Tests\Transactions;

use PHPUnit\Framework\TestCase;
use Sui\Transactions\Type\Expiration;
use Sui\Transactions\Type\ObjectRef;
use Sui\Transactions\Transaction;
use Sui\Transactions\Commands;
use Sui\Transactions\Inputs;
use Sui\Bcs\Bcs;
use Sui\Utils;

class TransactionTest extends TestCase
{
    /**
     * Test that an empty transaction can be constructed and serialized
     *
     * @return void
     */
    public function testCanConstructAndSerializeEmptyTransaction(): void
    {
        $tx = new Transaction();
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that a receiving transaction argument can be constructed
     *
     * @return void
     */
    public function testCanConstructReceivingTransactionArgument(): void
    {
        $tx = new Transaction();
        $tx->object(Inputs::ReceivingRef($this->objectRef()));
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that receiving transaction argument is different from object argument
     *
     * @return void
     */
    public function testReceivingTransactionArgumentDifferentFromObjectArgument(): void
    {
        $oref = $this->objectRef();
        $rtx = new Transaction();
        $rtx->object(Inputs::ReceivingRef($oref));
        $otx = new Transaction();
        $otx->object(Inputs::ObjectRef($oref));
        $this->assertNotNull($rtx->serialize());
        $this->assertNotNull($otx->serialize());
        $this->assertNotEquals($otx->serialize(), $rtx->serialize());
    }

    /**
     * Test that a transaction can be serialized and deserialized to the same values
     *
     * @return void
     */
    public function testCanBeSerializedAndDeserializedToSameValues(): void
    {
        $tx = new Transaction();
        $tx->add(Commands::SplitCoins($tx->gasArgument(), [$tx->getPure()->u64(100)]));
        $serialized = $tx->serialize();
        $tx2 = Transaction::from($serialized);
        $this->assertEquals($serialized, $tx2->serialize());
    }

    /**
     * Test that transfer is allowed with the result of split commands
     *
     * @return void
     */
    public function testAllowsTransferWithResultOfSplitCommands(): void
    {
        $tx = new Transaction();
        $coin = $tx->add(Commands::SplitCoins($tx->gasArgument(), [$tx->getPure()->u64(100)]));
        $tx->add(Commands::TransferObjects([$coin], $tx->object('0x2')));
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that nested results are supported through array index or destructuring
     *
     * @return void
     */
    public function testSupportsNestedResultsThroughArrayIndexOrDestructuring(): void
    {
        $tx = new Transaction();
        $registerResult = $tx->add(
            Commands::MoveCall([
                'target' => '0x2::game::register',
            ])
        );

        [$nft, $account] = $registerResult;

        $this->assertEquals($nft, $registerResult[0]);
        $this->assertEquals($account, $registerResult[1]);
    }

    /**
     * Test that an empty transaction can be built offline when provided sufficient data
     *
     * @return void
     */
    public function testBuildsEmptyTransactionOfflineWhenProvidedSufficientData(): void
    {
        $tx = $this->txInstance();
        $tx->build();
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that epoch expiration is supported
     *
     * @return void
     */
    public function testSupportsEpochExpiration(): void
    {
        $tx = $this->txInstance();
        $tx->setExpiration(new Expiration(1));
        $tx->build();
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that a split transaction can be built
     *
     * @return void
     */
    public function testBuildsSplitTransaction(): void
    {
        $tx = $this->txInstance();
        $tx->add(Commands::SplitCoins($tx->gasArgument(), [$tx->getPure()->u64(100)]));
        $tx->build();
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that reference equality is broken when cloning transactions
     *
     * @return void
     */
    public function testBreaksReferenceEquality(): void
    {
        $tx = $this->txInstance();
        $tx2 = Transaction::from($tx);

        $tx->setGasBudget(999);

        $this->assertNotEquals($tx2->getV1Data(), $tx->getV1Data());
        $this->assertNotSame($tx->getV1Data(), $tx->getV1Data());
        $this->assertNotSame($tx->getV1Data()->getGasConfig(), $tx->getV1Data()->getGasConfig());
        $this->assertNotSame($tx->getV1Data()->getTransactions(), $tx->getV1Data()->getTransactions());
        $this->assertNotSame($tx->getV1Data()->getInputs(), $tx->getV1Data()->getInputs());
    }

    /**
     * Test that the type of inputs for built-in commands can be determined
     *
     * @return void
     */
    public function testCanDetermineTypeOfInputsForBuiltInCommands(): void
    {
        $tx = $this->txInstance();
        $tx->splitCoins($tx->gasArgument(), [100]);
        $tx->build();
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that pre-serialized inputs as bytes are supported
     *
     * @return void
     */
    public function testSupportsPreSerializedInputsAsBytes(): void
    {
        $tx = $this->txInstance();
        $inputBytes = Bcs::u64()->serialize(100)->toBytes();
        $tx->add(Commands::SplitCoins($tx->gasArgument(), [$tx->getPure()->$inputBytes]));
        $tx->build();
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that a complex interaction can be built
     *
     * @return void
     */
    public function testBuildsComplexInteraction(): void
    {
        $tx = $this->txInstance();
        $coin = $tx->splitCoins($tx->gasArgument(), [100]);
        $tx->add(Commands::MergeCoins($tx->gasArgument(), [$coin, $tx->object(Inputs::ObjectRef($this->objectRef()))]));
        $tx->add(
            Commands::MoveCall([
                'target' => '0x2::devnet_nft::mint',
                'typeArguments' => [],
                'arguments' => [
                    $tx->getPure()->string('foo'),
                    $tx->getPure()->string('bar'),
                    $tx->getPure()->string('baz')
                ],
            ])
        );
        $tx->build();
        $this->assertNotNull($tx->serialize());
    }

    /**
     * Test that receiving arguments can be used
     *
     * @return void
     */
    public function testUsesReceivingArgument(): void
    {
        $tx = $this->txInstance();
        $tx->object(Inputs::ObjectRef($this->objectRef()));
        $coin = $tx->splitCoins($tx->gasArgument(), [100]);
        $tx->add(Commands::MergeCoins($tx->gasArgument(), [$coin, $tx->object(Inputs::ObjectRef($this->objectRef()))]));
        $tx->add(
            Commands::MoveCall([
                'target' => '0x2::devnet_nft::mint',
                'typeArguments' => [],
                'arguments' => [
                    $tx->object(Inputs::ObjectRef($this->objectRef())),
                    $tx->object(Inputs::ReceivingRef($this->objectRef()))
                ],
            ])
        );

        $bytes = $tx->build();
        $tx2 = Transaction::from($bytes);
        $bytes2 = $tx2->build();

        $this->assertEquals($bytes, $bytes2);
    }

    /**
     * Generate a random reference object
     *
     * @return array{objectId: string, version: string, digest: string}
     */
    private function ref(): array
    {
        return [
            'objectId' => str_pad((string)(rand(0, 100000)), 64, '0'),
            'version' => (string)rand(0, 10000),
            'digest' => Utils::toBase58(array_merge(
                range(0, 9),
                range(0, 9),
                range(0, 9),
                [1, 2]
            ))
        ];
    }

    /**
     * Generate an object reference
     *
     * @return ObjectRef
     */
    private function objectRef(): ObjectRef
    {
        return new ObjectRef(
            $this->ref()['objectId'],
            $this->ref()['version'],
            $this->ref()['digest']
        );
    }

    /**
     * Set up a basic transaction with default values
     *
     * @return Transaction
     */
    private function txInstance(): Transaction
    {
        $tx = new Transaction();
        $tx->setSender('0x2');
        $tx->setGasPrice(5);
        $tx->setGasBudget(100);
        $tx->setGasPayment([$this->objectRef()]);
        return $tx;
    }
}
