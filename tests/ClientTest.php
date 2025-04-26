<?php

declare(strict_types=1);

namespace Sui\Tests;

use Sui\Client;
use Sui\Constants;
use Sui\Type\SuiObject;
use PHPUnit\Framework\TestCase;
use Sui\Response\ObjectResponse;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var string
     */
    protected string $balanceAddress = '0xd4a5e15e39bed8eb14a87459e2cb43fcec3c0653002e5a9c31320ba8964b6052';

    /**
     * @var string
     */
    protected string $nftType =
    '0xd324a3ddcd34338b978a02b17407781bfc17cb0b432c38c2e60033522a5e4045::Test_NFT::TEST_NFT';

    /**
     * @var string
     */
    protected string $tokenType =
    '0xdb2062063e6756bb0c39c1c4a208a8b341f2241d941621ee5c52f00b13e4cb46::Test_USDC::TEST_USDC';

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->client = new Client('https://fullnode.devnet.sui.io:443');
    }

    /**
     * @return void
     */
    public function testGetBalance(): void
    {
        $response = $this->client->getBalance($this->balanceAddress);
        $this->assertEquals($response->totalBalance, 100 * 10 ** 9);
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
        $this->assertEquals($response->decimals, 6);
        $this->assertEquals($response->symbol, 'TUSDC');
        $this->assertEquals($response->name, 'Test USDC');
    }

    /**
     * @return void
     */
    public function testTotalSupply(): void
    {
        $response = $this->client->getTotalSupply($this->tokenType);
        $this->assertEquals($response, (string) 100000000 * 10 ** 6);
    }

    /**
     * @return void
     */
    public function testGetObject(): void
    {
        $objectId = '0x57f764ca497379aca2553ceaccd319194d8057999554a0d0c0e99805f1d0eb9d';
        $response = $this->client->getObject($objectId, [
            "showType" => true,
            "showOwner" => true,
            "showPreviousTransaction" => true,
            "showDisplay" => false,
            "showContent" => true,
            "showBcs" => false,
            "showStorageRebate" => true
        ]);

        $this->assertInstanceOf(SuiObject::class, $response);
        $this->assertEquals($this->nftType, $response->type);
    }

    /**
     * @return void
     */
    public function testGetOwnedObject(): void
    {
        $response = $this->client->getOwnedObjects($this->balanceAddress);
        $this->assertEquals($response->nextCursor, '0xffdb31461dc8c82c4a267c5a27f8424e1c4cf2f13fc36aff259b813f0201571b');
    }
}
