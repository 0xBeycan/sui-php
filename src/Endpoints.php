<?php

declare(strict_types=1);

namespace Sui;

trait Endpoints
{
    /**
     * @return string
     */
    public static function local(): string
    {
        return 'http://127.0.0.1:9000';
    }

    /**
     * @return string
     */
    public static function devnet(): string
    {
        return 'https://fullnode.devnet.sui.io:443';
    }

    /**
     * @return string
     */
    public static function testnet(): string
    {
        return 'https://fullnode.testnet.sui.io:443';
    }

    /**
     * @return string
     */
    public static function mainnet(): string
    {
        return 'https://fullnode.mainnet.sui.io:443';
    }
}
