<?php

declare(strict_types=1);

namespace Sui\Tests;

use Sui\Client;
use Sui\Constants;
use Sui\Type\SuiObjetData;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var string
     */
    protected string $sender = '0xd68cb1e0d64372021cd6fd54940d213c939d16cd4667bba507df880f1e17c78b';

    /**
     * @var string
     */
    protected string $balanceAddress = '0xd4a5e15e39bed8eb14a87459e2cb43fcec3c0653002e5a9c31320ba8964b6052';

    /**
     * @var string
     */
    protected string $tokenPackage = '0x669d8724a063f2b8890ba15728366f1acf924d8440909cc0bca4b7d15666068e';

    /**
     * @var string
     */
    protected string $nftPackage = '0x0a777320751f95a23df57e8d4d2b2d427d52ed94f2f6f05e8cf9f7828e9e604d';

    /**
     * @var string
     */
    protected string $nftType =
    '0x0a777320751f95a23df57e8d4d2b2d427d52ed94f2f6f05e8cf9f7828e9e604d::Test_NFT::TEST_NFT';

    /**
     * @var string
     */
    protected string $tokenType =
    '0x669d8724a063f2b8890ba15728366f1acf924d8440909cc0bca4b7d15666068e::tusdc::TUSDC';

    /**
     * @var string
     */
    protected string $nftObjectId = '0xf4a0ab6259e059a5706add99c50d1bd6c3ff23a48753e4bc8b7c6ef0ee9082cb';

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->client = new Client('https://fullnode.testnet.sui.io:443');
    }

    /**
     * @return void
     */
    public function testGetBalance(): void
    {
        $response = $this->client->getBalance($this->balanceAddress);
        $this->assertEquals($response->totalBalance, 0.2 * 10 ** 9);
    }

    /**
     * @return void
     */
    public function testGetAllBalances(): void
    {
        $response = $this->client->getAllBalances($this->balanceAddress);
        $this->assertEquals(count($response), 2);
    }

    /**
     * @return void
     */
    public function testGetRpcVersion(): void
    {
        $this->assertIsString($this->client->getRpcApiVersion());
    }

    /**
     * @return void
     */
    public function testGetCoins(): void
    {
        $response = $this->client->getCoins($this->balanceAddress, $this->tokenType);

        $this->assertIsArray($response->data);
        $this->assertEquals($response->data[0]->coinType, $this->tokenType);
    }

    /**
     * @return void
     */
    public function testGetAllCoins(): void
    {
        $response = $this->client->getAllCoins($this->balanceAddress);

        $this->assertIsArray($response->data);
        $this->assertEquals($response->data[0]->coinType, Constants::SUI_TYPE_ARG);
    }

    /**
     * @return void
     */
    public function testGetCoinMetadata(): void
    {
        $response = $this->client->getCoinMetadata($this->tokenType);
        $this->assertEquals($response->decimals, 9);
        $this->assertEquals($response->symbol, 'TUSDC');
        $this->assertEquals($response->name, 'Test USDC');
    }

    /**
     * @return void
     */
    public function testTotalSupply(): void
    {
        $response = $this->client->getTotalSupply($this->tokenType);
        $this->assertEquals($response, (string) 10000000 * 10 ** 9);
    }

    /**
     * @return void
     */
    public function testGetObject(): void
    {
        $response = $this->client->getObject($this->nftObjectId, [
            "showType" => true,
            "showOwner" => true,
            "showPreviousTransaction" => true,
            "showDisplay" => false,
            "showContent" => true,
            "showBcs" => false,
            "showStorageRebate" => true
        ]);

        $this->assertInstanceOf(SuiObjetData::class, $response);
        $this->assertEquals($this->nftType, $response->type);
    }

    /**
     * @return void
     */
    public function testGetOwnedObject(): void
    {
        $response = $this->client->getOwnedObjects($this->balanceAddress);
        $this->assertEquals(
            $response->nextCursor,
            '0xeb8b79684de4534c58755da8bf67472f5b74148f132239d43efc3a83095e20b7'
        );
    }

    /**
     * @return void
     */
    public function testGetObjectRead(): void
    {
        $objectId = '0x11af4b844ff94b3fbef6e36b518da3ad4c5856fa686464524a876b463d129760';
        $response = $this->client->tryGetPastObject($objectId, 4);

        $this->assertEquals($response->status, 'ObjectNotExists');
    }

    /**
     * @return void
     */
    public function testMultiGetObjects(): void
    {
        $response = $this->client->multiGetObjects([$this->nftObjectId], [
            "showType" => true,
            "showOwner" => true,
            "showPreviousTransaction" => true,
            "showDisplay" => false,
            "showContent" => true,
            "showBcs" => false,
            "showStorageRebate" => true
        ]);

        $this->assertInstanceOf(SuiObjetData::class, $response[0]);
        $this->assertEquals($this->nftType, $response[0]->type);
    }

    /**
     * @return void
     */
    public function testGetNormalizedMoveModulesByPackage(): void
    {
        $response = $this->client->getNormalizedMoveModulesByPackage($this->tokenPackage);
        $this->assertEquals(array_shift($response)->name, 'tusdc');
    }

    /**
     * @return void
     */
    public function testGetNormalizedMoveModule(): void
    {
        $response = $this->client->getNormalizedMoveModule($this->tokenPackage, 'tusdc');
        $this->assertEquals($response->name, 'tusdc');
    }

    /**
     * @return void
     */
    public function testGetNormalizedMoveFunction(): void
    {
        $response = $this->client->getNormalizedMoveFunction($this->tokenPackage, 'tusdc', 'init');
        $this->assertEquals($response->visibility, 'Private');
    }

    /**
     * @return void
     */
    public function testGetNormalizedMoveStruct(): void
    {
        $response = $this->client->getNormalizedMoveStruct($this->tokenPackage, 'tusdc', 'TUSDC');
        $this->assertEquals(in_array('Drop', $response->abilities['abilities']), true);
    }

    /**
     * @return void
     */
    public function testQueryTransactionBlocks(): void
    {
        $response = $this->client->queryTransactionBlocks(
            [
                'MoveFunction' => [
                    'package' => $this->nftPackage,
                    'module' => 'Test_NFT',
                    'function' => 'update_description',
                ],
            ],
            [
                'showBalanceChanges' => true,
                'showEffects' => true,
                'showEvents' => true,
                'showInput' => true,
                'showObjectChanges' => true,
                'showRawEffects' => true,
                'showRawInput' => true,
            ]
        );
        $this->assertEquals(count($response->data), 1);
        $this->assertEquals($response->data[0]->digest, 'CusvSMa23LQiTtz3cYw7GFf6jWeodCJaoYW2ktd746aC');
    }

    /**
     * @return void
     */
    public function testGetLatestSuiSystemState(): void
    {
        $response = $this->client->getLatestSuiSystemState();
        $this->assertEquals($response->stakeSubsidyDecreaseRate, 1000);
        $this->assertIsArray($response->activeValidators);
    }

    /**
     * @return void
     */
    public function testQueryEvents(): void
    {
        $response = $this->client->queryEvents([
            "All" => []
        ]);

        $this->assertIsArray($response->data);
        $this->assertEquals(true, count($response->data) > 0);
    }

    /**
     * @return void
     */
    public function testDevInspectTransactionBlock(): void
    {
        $response = $this->client->devInspectTransactionBlock(
            $this->sender,
            "AAIACEBCDwAAAAAAACDaRViin0wt1U0/vK9msi7qc3ct2JPr/MyXNgnTRXzd/QICAAEBAAABAQMAAAAAAQEA"
        );

        $this->assertEquals($response->effects->status->status, 'success');
    }

    /**
     * @return void
     */
    public function testGetCheckpoint(): void
    {
        $response = $this->client->getCheckpoint('190342129');
        $this->assertEquals($response->sequenceNumber, '190342129');
    }

    /**
     * @return void
     */
    public function testGetCheckpoints(): void
    {
        $response = $this->client->getCheckpoints();
        $this->assertIsArray($response->data);
    }

    /**
     * @return void
     */
    public function testGetCommitteeInfo(): void
    {
        $response = $this->client->getCommitteeInfo();
        $this->assertIsArray($response->validators);
    }

    /**
     * @return void
     */
    public function testGetValidatorsApy(): void
    {
        $response = $this->client->getValidatorsApy();
        $this->assertIsArray($response->apys);
    }

    /**
     * @return void
     */
    public function testGetChainIdentifier(): void
    {
        $this->assertIsString($this->client->getChainIdentifier());
    }
}
