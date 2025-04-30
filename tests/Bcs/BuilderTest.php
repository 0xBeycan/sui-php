<?php

declare(strict_types=1);

namespace Sui\Tests\Bcs;

use PHPUnit\Framework\TestCase;
use Sui\Bcs\Bcs;
use Sui\Bcs\Reader as BcsReader;
use Sui\Bcs\Type;
use Sui\Bcs\Writer as BcsWriter;
use Sui\Utils;

class BuilderTest extends TestCase
{
    /**
     * Test base types
     *
     * @return void
     */
    public function testBaseTypes(): void
    {
        $this->testType('true', Bcs::bool(), true, '01');
        $this->testType('false', Bcs::bool(), false, '00');
        $this->testType('uleb128 0', Bcs::uleb128(), 0, '00');
        $this->testType('uleb128 1', Bcs::uleb128(), 1, '01');
        $this->testType('uleb128 127', Bcs::uleb128(), 127, '7f');
        $this->testType('uleb128 128', Bcs::uleb128(), 128, '8001');
        $this->testType('uleb128 255', Bcs::uleb128(), 255, 'ff01');
        $this->testType('uleb128 256', Bcs::uleb128(), 256, '8002');
        $this->testType('uleb128 16383', Bcs::uleb128(), 16383, 'ff7f');
        $this->testType('uleb128 16384', Bcs::uleb128(), 16384, '808001');
        $this->testType('uleb128 2097151', Bcs::uleb128(), 2097151, 'ffff7f');
        $this->testType('uleb128 2097152', Bcs::uleb128(), 2097152, '80808001');
        $this->testType('uleb128 268435455', Bcs::uleb128(), 268435455, 'ffffff7f');
        $this->testType('uleb128 268435456', Bcs::uleb128(), 268435456, '8080808001');
        $this->testType('u8 0', Bcs::u8(), 0, '00');
        $this->testType('u8 1', Bcs::u8(), 1, '01');
        $this->testType('u8 255', Bcs::u8(), 255, 'ff');
        $this->testType('u16 0', Bcs::u16(), 0, '0000');
        $this->testType('u16 1', Bcs::u16(), 1, '0100');
        $this->testType('u16 255', Bcs::u16(), 255, 'ff00');
        $this->testType('u16 256', Bcs::u16(), 256, '0001');
        $this->testType('u16 65535', Bcs::u16(), 65535, 'ffff');
        $this->testType('u32 0', Bcs::u32(), 0, '00000000');
        $this->testType('u32 1', Bcs::u32(), 1, '01000000');
        $this->testType('u32 255', Bcs::u32(), 255, 'ff000000');
        $this->testType('u32 256', Bcs::u32(), 256, '00010000');
        $this->testType('u32 65535', Bcs::u32(), 65535, 'ffff0000');
        $this->testType('u32 65536', Bcs::u32(), 65536, '00000100');
        $this->testType('u32 16777215', Bcs::u32(), 16777215, 'ffffff00');
        $this->testType('u32 16777216', Bcs::u32(), 16777216, '00000001');
        $this->testType('u32 4294967295', Bcs::u32(), 4294967295, 'ffffffff');
        $this->testType('u64 0', Bcs::u64(), '0', '0000000000000000', '0');
        $this->testType('u64 1', Bcs::u64(), '1', '0100000000000000', '1');
        $this->testType('u64 255', Bcs::u64(), '255', 'ff00000000000000', '255');
        $this->testType('u64 256', Bcs::u64(), '256', '0001000000000000', '256');
        $this->testType('u64 65535', Bcs::u64(), '65535', 'ffff000000000000', '65535');
        $this->testType('u64 65536', Bcs::u64(), '65536', '0000010000000000', '65536');
        $this->testType('u64 16777215', Bcs::u64(), '16777215', 'ffffff0000000000', '16777215');
        $this->testType('u64 16777216', Bcs::u64(), '16777216', '0000000100000000', '16777216');
        $this->testType('u64 4294967295', Bcs::u64(), '4294967295', 'ffffffff00000000', '4294967295');
        $this->testType('u64 4294967296', Bcs::u64(), '4294967296', '0000000001000000', '4294967296');
        $this->testType('u64 1099511627775', Bcs::u64(), '1099511627775', 'ffffffffff000000', '1099511627775');
        $this->testType('u64 1099511627776', Bcs::u64(), '1099511627776', '0000000000010000', '1099511627776');
        $this->testType(
            'u64 281474976710655',
            Bcs::u64(),
            '281474976710655',
            'ffffffffffff0000',
            '281474976710655'
        );
        $this->testType(
            'u64 281474976710656',
            Bcs::u64(),
            '281474976710656',
            '0000000000000100',
            '281474976710656'
        );
        $this->testType(
            'u64 72057594037927935',
            Bcs::u64(),
            '72057594037927935',
            'ffffffffffffff00',
            '72057594037927935'
        );
        $this->testType(
            'u64 72057594037927936',
            Bcs::u64(),
            '72057594037927936',
            '0000000000000001',
            '72057594037927936'
        );
        $this->testType(
            'u64 18446744073709551615',
            Bcs::u64(),
            '18446744073709551615',
            'ffffffffffffffff',
            '18446744073709551615'
        );
        $this->testType('u128 0', Bcs::u128(), '0', '00000000000000000000000000000000', '0');
        $this->testType('u128 1', Bcs::u128(), '1', '01000000000000000000000000000000', '1');
        $this->testType('u128 255', Bcs::u128(), '255', 'ff000000000000000000000000000000', '255');
        $this->testType(
            'u128 18446744073709551615',
            Bcs::u128(),
            '18446744073709551615',
            'ffffffffffffffff0000000000000000',
            '18446744073709551615'
        );
        $this->testType(
            'u128 18446744073709551616',
            Bcs::u128(),
            '18446744073709551616',
            '00000000000000000100000000000000',
            '18446744073709551616'
        );
        $this->testType(
            'u128 340282366920938463463374607431768211455',
            Bcs::u128(),
            '340282366920938463463374607431768211455',
            'ffffffffffffffffffffffffffffffff',
            '340282366920938463463374607431768211455'
        );
    }

    /**
     * Test vectors
     *
     * @return void
     */
    public function testVector(): void
    {
        $this->testType('vector([])', Bcs::vector(Bcs::u8()), [], '00');
        $this->testType('vector([1, 2, 3])', Bcs::vector(Bcs::u8()), [1, 2, 3], '03010203');
        $this->testType(
            'vector([1, null, 3])',
            Bcs::vector(Bcs::option(Bcs::u8())),
            [1, null, 3],
            '03' . '0101' . '00' . '0103'
        );
    }

    /**
     * Test fixed vectors
     *
     * @return void
     */
    public function testFixedVector(): void
    {
        $this->testType('fixedVector([])', Bcs::fixedArray(0, Bcs::u8()), [], '');
        $this->testType('vector([1, 2, 3])', Bcs::fixedArray(3, Bcs::u8()), [1, 2, 3], '010203');
        $this->testType(
            'fixedVector([1, null, 3])',
            Bcs::fixedArray(3, Bcs::option(Bcs::u8())),
            [1, null, 3],
            '0101' . '00' . '0103'
        );
    }

    /**
     * Test options
     *
     * @return void
     */
    public function testOptions(): void
    {
        $this->testType('optional u8 undefined', Bcs::option(Bcs::u8()), null, '00');
        $this->testType('optional u8 null', Bcs::option(Bcs::u8()), null, '00');
        $this->testType('optional u8 0', Bcs::option(Bcs::u8()), 0, '0100');
        $this->testType('optional vector(null)', Bcs::option(Bcs::vector(Bcs::u8())), null, '00');
        $this->testType(
            'optional vector([1, 2, 3])',
            Bcs::option(Bcs::vector(Bcs::option(Bcs::u8()))),
            [1, null, 3],
            '01' . '03' . '0101' . '00' . '0103'
        );
    }

    /**
     * Test strings
     *
     * @return void
     */
    public function testString(): void
    {
        $this->testType('string empty', Bcs::string(), '', '00');
        $this->testType('string hello', Bcs::string(), 'hello', '0568656c6c6f');
        $this->testType(
            'string çå∞≠¢õß∂ƒ∫',
            Bcs::string(),
            'çå∞≠¢õß∂ƒ∫',
            '18c3a7c3a5e2889ee289a0c2a2c3b5c39fe28882c692e288ab'
        );
    }

    /**
     * Test bytes
     *
     * @return void
     */
    public function testBytes(): void
    {
        $this->testType('bytes', Bcs::bytes(4), [1, 2, 3, 4], '01020304');
    }

    /**
     * Test byte vectors
     *
     * @return void
     */
    public function testByteVector(): void
    {
        $this->testType('byteVector', Bcs::byteVector(), [1, 2, 3], '03010203');
    }

    /**
     * Test tuples
     *
     * @return void
     */
    public function testTuples(): void
    {
        $this->testType('tuple(u8, u8)', Bcs::tuple([Bcs::u8(), Bcs::u8()]), [1, 2], '0102');
        $this->testType(
            'tuple(u8, string, boolean)',
            Bcs::tuple([Bcs::u8(), Bcs::string(), Bcs::bool()]),
            [1, 'hello', true],
            '010568656c6c6f01'
        );

        $this->testType(
            'tuple(null, u8)',
            Bcs::tuple([Bcs::option(Bcs::u8()), Bcs::option(Bcs::u8())]),
            [null, 1],
            '000101'
        );
    }

    /**
     * Test structs
     *
     * @return void
     */
    public function testStructs(): void
    {
        $myStruct = Bcs::struct('MyStruct', [
            'boolean' => Bcs::bool(),
            'bytes' => Bcs::vector(Bcs::u8()),
            'label' => Bcs::string(),
        ]);

        $wrapper = Bcs::struct('Wrapper', [
            'inner' => $myStruct,
            'name' => Bcs::string(),
        ]);

        $this->testType(
            'struct { boolean: bool, bytes: Vec<u8>, label: String }',
            $myStruct,
            [
                'boolean' => true,
                'bytes' => [0xc0, 0xde],
                'label' => 'a',
            ],
            '0102c0de0161',
            [
                'boolean' => true,
                'bytes' => [0xc0, 0xde],
                'label' => 'a',
            ]
        );

        $this->testType(
            'struct { inner: MyStruct, name: String }',
            $wrapper,
            [
                'inner' => [
                    'boolean' => true,
                    'bytes' => [0xc0, 0xde],
                    'label' => 'a',
                ],
                'name' => 'b',
            ],
            '0102c0de01610162',
            [
                'inner' => [
                    'boolean' => true,
                    'bytes' => [0xc0, 0xde],
                    'label' => 'a',
                ],
                'name' => 'b',
            ]
        );
    }

    /**
     * Test enums
     *
     * @return void
     */
    public function testEnums(): void
    {
        $e = Bcs::enum('E', [
            'Variant0' => Bcs::u16(),
            'Variant1' => Bcs::u8(),
            'Variant2' => Bcs::string(),
        ]);

        $this->testType('Enum::Variant0(1)', $e, ['Variant0' => 1], '000100', ['$kind' => 'Variant0', 'Variant0' => 1]);
        $this->testType('Enum::Variant1(1)', $e, ['Variant1' => 1], '0101', ['$kind' => 'Variant1', 'Variant1' => 1]);
        $this->testType(
            'Enum::Variant2("hello")',
            $e,
            ['Variant2' => 'hello'],
            '020568656c6c6f',
            ['$kind' => 'Variant2', 'Variant2' => 'hello']
        );
    }

    /**
     * Test transforms
     *
     * @return void
     */
    public function testTransforms(): void
    {
        $stringU8 = Bcs::u8()->transform(
            'stringU8',
            function (string $val): int {
                return intval($val);
            },
            function (int $val): string {
                return strval($val);
            }
        );

        $this->testType('transform', $stringU8, '1', '01', '1');

        // Output only
        $bigIntu64 = Bcs::u64()->transform(
            'bigIntu64',
            null,
            function (string $val): string {
                return $val;
            }
        );

        $this->testType('transform', $bigIntu64, '1', '0100000000000000', '1');
        $this->testType('transform', $bigIntu64, 1, '0100000000000000', '1');

        // Input only
        $hexU8 = Bcs::u8()->transform(
            'hexU8',
            function (string $val): int {
                return intval($val, 16);
            },
            null
        );

        $this->testType('transform', $hexU8, 'ff', 'ff', 255);
    }

    /**
     * Helper function to test BCS type serialization and deserialization
     *
     * @param string $name Test name
     * @param Type $schema BCS type schema
     * @param mixed $value Value to test
     * @param string $hex Expected hex representation
     * @param mixed|null $expected Expected deserialized value (defaults to $value)
     * @return void
     */
    private function testType(string $name, Type $schema, mixed $value, string $hex, mixed $expected = null): void
    {
        $expected = $expected ?? $value;

        $serialized = $schema->serialize($value);
        $bytes = $serialized->toBytes();

        $this->assertEquals($hex, Utils::toHex($bytes), "toHex for {$name}");
        $this->assertEquals($hex, $serialized->toHex(), "serialized->toHex() for {$name}");
        $this->assertEquals(Utils::toBase64($bytes), $serialized->toBase64(), "toBase64 for {$name}");
        $this->assertEquals(Utils::toBase58($bytes), $serialized->toBase58(), "toBase58 for {$name}");

        $deserialized = $schema->parse($bytes);
        $this->assertEquals($expected, $deserialized, "parse for {$name}");

        $writer = new BcsWriter(['initialSize' => strlen($bytes)]);
        $schema->write($value, $writer);
        $this->assertEquals($hex, Utils::toHex($writer->toBytes()), "writer->toHex() for {$name}");

        $reader = new BcsReader($bytes);
        $this->assertEquals($expected, $schema->read($reader), "read for {$name}");
    }
}
