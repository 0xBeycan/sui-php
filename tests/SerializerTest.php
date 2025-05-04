<?php

declare(strict_types=1);

namespace Sui\Tests;

use PHPUnit\Framework\TestCase;
use Sui\Bcs\Serializer;

class SerializerTest extends TestCase
{
    /**
     * Test parsing a nested struct type from a string
     * @return void
     */
    public function testParseFromStrWithNestedStruct(): void
    {
        $typeStr = '0x2::balance::Supply<0x72de5feb63c0ab6ed1cda7e5b367f3d0a999add7::amm::LP<0x2::sui::SUI, 0xfee024a3c0c03ada5cdbda7d0e8b68802e6dec80::example_coin::EXAMPLE_COIN>>'; // phpcs:ignore
        $actual = Serializer::parseFromStr($typeStr);

        $expected = [
            'struct' => [
                'address' => '0x2',
                'module' => 'balance',
                'name' => 'Supply',
                'typeParams' => [
                    [
                        'struct' => [
                            'address' => '0x72de5feb63c0ab6ed1cda7e5b367f3d0a999add7',
                            'module' => 'amm',
                            'name' => 'LP',
                            'typeParams' => [
                                [
                                    'struct' => [
                                        'address' => '0x2',
                                        'module' => 'sui',
                                        'name' => 'SUI',
                                        'typeParams' => [],
                                    ],
                                ],
                                [
                                    'struct' => [
                                        'address' => '0xfee024a3c0c03ada5cdbda7d0e8b68802e6dec80',
                                        'module' => 'example_coin',
                                        'name' => 'EXAMPLE_COIN',
                                        'typeParams' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test parsing a non-parametrized struct type from a string
     * @return void
     */
    public function testParseFromStrWithNonParametrizedStruct(): void
    {
        $typeStr = '0x72de5feb63c0ab6ed1cda7e5b367f3d0a999add7::foo::FOO';
        $actual = Serializer::parseFromStr($typeStr);

        $expected = [
            'struct' => [
                'address' => '0x72de5feb63c0ab6ed1cda7e5b367f3d0a999add7',
                'module' => 'foo',
                'name' => 'FOO',
                'typeParams' => [],
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test converting a nested struct type to a string
     * @return void
     */
    public function testTagToStringWithNestedStruct(): void
    {
        $type = [
            'struct' => [
                'address' => '0x2',
                'module' => 'balance',
                'name' => 'Supply',
                'typeParams' => [
                    [
                        'struct' => [
                            'address' => '0x72de5feb63c0ab6ed1cda7e5b367f3d0a999add7',
                            'module' => 'amm',
                            'name' => 'LP',
                            'typeParams' => [
                                [
                                    'struct' => [
                                        'address' => '0x2',
                                        'module' => 'sui',
                                        'name' => 'SUI',
                                        'typeParams' => [],
                                    ],
                                ],
                                [
                                    'struct' => [
                                        'address' => '0xfee024a3c0c03ada5cdbda7d0e8b68802e6dec80',
                                        'module' => 'example_coin',
                                        'name' => 'EXAMPLE_COIN',
                                        'typeParams' => [],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $actual = Serializer::tagToString($type);
        $expected = '0x2::balance::Supply<0x72de5feb63c0ab6ed1cda7e5b367f3d0a999add7::amm::LP<0x2::sui::SUI, 0xfee024a3c0c03ada5cdbda7d0e8b68802e6dec80::example_coin::EXAMPLE_COIN>>'; // phpcs:ignore

        $this->assertEquals($expected, $actual);
    }
}
