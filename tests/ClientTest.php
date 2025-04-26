<?php

declare(strict_types=1);

namespace Sui\Tests;

use Sui\Client;
use PHPUnit\Framework\TestCase;
use Sui\Response\ObjectResponse;

class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected Client $client;

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
    public function testGetObject(): void
    {
        $type = '0xd324a3ddcd34338b978a02b17407781bfc17cb0b432c38c2e60033522a5e4045::Test_NFT::TEST_NFT';
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

        $this->assertInstanceOf(ObjectResponse::class, $response);
        $this->assertEquals($type, $response->type);
    }

    public function testGetBalance(): void
    {
        $response = $this->client->getBalance([
            'owner' => '0xd4a5e15e39bed8eb14a87459e2cb43fcec3c0653002e5a9c31320ba8964b6052',
        ]);

        $this->assertEquals($response->totalBalance, 100 * 10 ** 9);
    }
}
