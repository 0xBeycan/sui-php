<?php

declare(strict_types=1);

namespace Sui\Tests;

use PHPUnit\Framework\TestCase;
use Sui\Bcs\Bcs;

class BcsTest extends TestCase
{
    /**
     * Test that BCS supports growing size when serializing data
     * @return void
     */
    public function testShouldSupportGrowingSize(): void
    {
        $coin = Bcs::struct('Coin', [
            'value' => Bcs::u64(),
            'owner' => Bcs::string(),
            'is_locked' => Bcs::bool(),
        ]);

        $rustBcs = 'gNGxBWAAAAAOQmlnIFdhbGxldCBHdXkA';
        $expected = [
            'owner' => 'Big Wallet Guy',
            'value' => '412412400000',
            'is_locked' => false,
        ];

        $serialized = $coin->serialize($expected, ['initialSize' => 1, 'maxSize' => 1024]);
        $parsed = $coin->fromBase64($rustBcs);

        $this->assertEquals($expected, $parsed);
        $this->assertEquals($rustBcs, $serialized->toBase64());
    }

    /**
     * Test that BCS throws an exception when attempting to grow beyond the allowed size
     * @return void
     */
    public function testShouldErrorWhenAttemptingToGrowBeyondAllowedSize(): void
    {
        $coin = Bcs::struct('Coin', [
            'value' => Bcs::u64(),
            'owner' => Bcs::string(),
            'is_locked' => Bcs::bool(),
        ]);

        $expected = [
            'owner' => 'Big Wallet Guy',
            'value' => '412412400000',
            'is_locked' => false,
        ];

        $this->expectException(\Exception::class);
        $coin->serialize($expected, ['initialSize' => 1, 'maxSize' => 1]);
    }
}
