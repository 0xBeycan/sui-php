<?php

declare(strict_types=1);

namespace Sui\Tests\Bcs;

use PHPUnit\Framework\TestCase;
use Sui\Bcs\Bcs;
use Sui\Utils;

class EncodingTest extends TestCase
{
    /**
     * Tests deserialization of hex, base58 and base64 encoded data.
     * @return void
     */
    public function testShouldDeserializeHexBase58AndBase64(): void
    {
        // Test u8 parsing from different encodings
        $this->assertEquals(0, Bcs::u8()->parse(Utils::fromBase64('AA==')));
        $this->assertEquals(0, Bcs::u8()->parse(Utils::fromHex('00')));
        $this->assertEquals(0, Bcs::u8()->parse(Utils::fromBase58('1')));

        // Test string serialization and parsing
        $str = 'this is a test string';
        $serialized = Bcs::string()->serialize($str);

        $this->assertEquals($str, Bcs::string()->parse(Utils::fromBase58(Utils::toBase58($serialized->toBytes()))));
        $this->assertEquals($str, Bcs::string()->parse(Utils::fromBase64(Utils::toBase64($serialized->toBytes()))));
        $this->assertEquals($str, Bcs::string()->parse(Utils::fromHex(Utils::toHex($serialized->toBytes()))));
    }

    /**
     * Tests deserialization of hex strings with leading zeros.
     * @return void
     */
    public function testShouldDeserializeHexWithLeadingZeros(): void
    {
        $addressLeading0 = 'a7429d7a356dd98f688f11a330a32e0a3cc1908734a8c5a5af98f34ec93df0c';
        $this->assertEquals('0001', Utils::toHex([0, 1]));
        $this->assertEquals([1], Utils::fromHex('0x1'));
        $this->assertEquals([1], Utils::fromHex('1'));
        $this->assertEquals([1, 17], Utils::fromHex('111'));
        $this->assertEquals([0, 1], Utils::fromHex('001'));
        $this->assertEquals([0, 17], Utils::fromHex('011'));
        $this->assertEquals([0, 17], Utils::fromHex('0011'));
        $this->assertEquals([0, 17], Utils::fromHex('0x0011'));
        $expectedBytes = [
            10, 116, 41, 215, 163, 86, 221, 152, 246, 136, 241, 26, 51, 10, 50, 224, 163, 204, 25, 8,
            115, 74, 140, 90, 90, 249, 143, 52, 236, 147, 223, 12
        ];
        $this->assertEquals($expectedBytes, Utils::fromHex($addressLeading0));
        $this->assertEquals('0' . $addressLeading0, Utils::toHex(Utils::fromHex($addressLeading0)));
    }

    /**
     * Tests that invalid hex strings throw exceptions.
     * @return void
     */
    public function testShouldThrowOnInvalidHexStrings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Utils::fromHex('0xZZ');

        $this->expectException(\InvalidArgumentException::class);
        Utils::fromHex('GG');

        $this->expectException(\InvalidArgumentException::class);
        Utils::fromHex('hello');

        $this->expectException(\InvalidArgumentException::class);
        Utils::fromHex('12 34');

        $this->expectException(\InvalidArgumentException::class);
        Utils::fromHex('12\n34');

        $this->expectException(\InvalidArgumentException::class);
        Utils::fromHex('12-34');
    }
}
