<?php

declare(strict_types=1);

namespace Sui\Tests;

use Sui\Client;
use PHPUnit\Framework\TestCase;

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
    public function testIsClient(): void
    {
        $this->assertIsObject($this->client);
    }
}
