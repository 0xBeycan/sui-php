<?php

declare(strict_types=1);

namespace Sui\Tests\Transactions;

use Sui\Utils;
use PHPUnit\Framework\TestCase;
use Sui\Transactions\Transaction;
use Sui\Transactions\ArgumentFactory;
use Sui\Transactions\BuildTransactionOptions;

class ArgumentsTest extends TestCase
{
    /**
     * @return void
     */
    public function testCanCreateArguments(): void
    {
        $factory = new ArgumentFactory();

        $digest = Utils::toBase58(array_fill(0, 32, 0x1));

        $args = [
            $factory->object->object('0x123'),
            $factory->receivingRef('1', $digest, '123'),
            $factory->sharedObjectRef('2', true, '123'),
            $factory->objectRef('3', $digest, '123'),
            $factory->pure->address('0x2'),
            $factory->object->system(),
            $factory->object->clock(),
            $factory->object->random(),
            $factory->object->denyList(),
            $factory->object->option('0x123::example::Thing', '0x456'),
            $factory->object->option('0x123::example::Thing', $factory->object->object('0x456')),
            $factory->object->option('0x123::example::Thing', null),
        ];

        $tx = new Transaction(new BuildTransactionOptions());

        $tx->moveCall([
            'target' => '0x2::foo::bar',
            'arguments' => $args,
        ]);

        $result = json_decode($tx->toJSON(), true);

        $this->assertEquals($result, [
            "commands" => [
                [
                    "\$kind" => "MoveCall",
                    "MoveCall" => [
                        "arguments" => [
                            [
                                "\$kind" => "Input",
                                "Input" => 9,
                                "type" => "object"
                            ]
                        ],
                        "function" => "some",
                        "module" => "option",
                        "package" => "0x0000000000000000000000000000000000000000000000000000000000000001",
                        "typeArguments" => ["0x123::example::Thing"]
                    ]
                ],
                [
                    "\$kind" => "MoveCall",
                    "MoveCall" => [
                        "arguments" => [
                            [
                                "\$kind" => "Input",
                                "Input" => 9,
                                "type" => "object"
                            ]
                        ],
                        "function" => "some",
                        "module" => "option",
                        "package" => "0x0000000000000000000000000000000000000000000000000000000000000001",
                        "typeArguments" => ["0x123::example::Thing"]
                    ]
                ],
                [
                    "\$kind" => "MoveCall",
                    "MoveCall" => [
                        "arguments" => [],
                        "function" => "none",
                        "module" => "option",
                        "package" => "0x0000000000000000000000000000000000000000000000000000000000000001",
                        "typeArguments" => ["0x123::example::Thing"]
                    ]
                ],
                [
                    "\$kind" => "MoveCall",
                    "MoveCall" => [
                        "arguments" => [
                            ["\$kind" => "Input", "Input" => 0, "type" => "object"],
                            ["\$kind" => "Input", "Input" => 1, "type" => "object"],
                            ["\$kind" => "Input", "Input" => 2, "type" => "object"],
                            ["\$kind" => "Input", "Input" => 3, "type" => "object"],
                            ["\$kind" => "Input", "Input" => 4, "type" => "pure"],
                            ["\$kind" => "Input", "Input" => 5, "type" => "object"],
                            ["\$kind" => "Input", "Input" => 6, "type" => "object"],
                            ["\$kind" => "Input", "Input" => 7, "type" => "object"],
                            ["\$kind" => "Input", "Input" => 8, "type" => "object"],
                            ["\$kind" => "Result", "Result" => 0],
                            ["\$kind" => "Result", "Result" => 1],
                            ["\$kind" => "Result", "Result" => 2]
                        ],
                        "function" => "bar",
                        "module" => "foo",
                        "package" => "0x0000000000000000000000000000000000000000000000000000000000000002",
                        "typeArguments" => []
                    ]
                ]
            ],
            "expiration" => null,
            "gasData" => [
                "budget" => null,
                "owner" => null,
                "payment" => null,
                "price" => null
            ],
            "inputs" => [
                [
                    "\$kind" => "UnresolvedObject",
                    "UnresolvedObject" => [
                        "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000123"
                    ]
                ],
                [
                    "\$kind" => "Object",
                    "Object" => [
                        "\$kind" => "Receiving",
                        "Receiving" => [
                            "digest" => "4vJ9JU1bJJE96FWSJKvHsmmFADCg4gpZQff4P3bkLKi",
                            "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000001",
                            "version" => "123"
                        ]
                    ]
                ],
                [
                    "\$kind" => "Object",
                    "Object" => [
                        "\$kind" => "SharedObject",
                        "SharedObject" => [
                            "initialSharedVersion" => "123",
                            "mutable" => true,
                            "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000002"
                        ]
                    ]
                ],
                [
                    "\$kind" => "Object",
                    "Object" => [
                        "\$kind" => "ImmOrOwnedObject",
                        "ImmOrOwnedObject" => [
                            "digest" => "4vJ9JU1bJJE96FWSJKvHsmmFADCg4gpZQff4P3bkLKi",
                            "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000003",
                            "version" => "123"
                        ]
                    ]
                ],
                [
                    "\$kind" => "Pure",
                    "Pure" => [
                        "bytes" => "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAI="
                    ]
                ],
                [
                    "\$kind" => "UnresolvedObject",
                    "UnresolvedObject" => [
                        "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000005"
                    ]
                ],
                [
                    "\$kind" => "UnresolvedObject",
                    "UnresolvedObject" => [
                        "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000006"
                    ]
                ],
                [
                    "\$kind" => "UnresolvedObject",
                    "UnresolvedObject" => [
                        "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000008"
                    ]
                ],
                [
                    "\$kind" => "UnresolvedObject",
                    "UnresolvedObject" => [
                        "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000403"
                    ]
                ],
                [
                    "\$kind" => "UnresolvedObject",
                    "UnresolvedObject" => [
                        "objectId" => "0x0000000000000000000000000000000000000000000000000000000000000456"
                    ]
                ]
            ],
            "sender" => null,
            "version" => 2
        ]);
    }
}
