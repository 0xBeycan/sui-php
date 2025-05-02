<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Bcs\Bcs;
use Sui\Bcs\Map;
use Sui\Bcs\Type;
use Sui\Constants;
use Sui\Utils;

class Serializer
{
    private const OBJECT_MODULE_NAME = 'object';
    private const ID_STRUCT_NAME = 'ID';
    private const STD_ASCII_MODULE_NAME = 'ascii';
    private const STD_ASCII_STRUCT_NAME = 'String';
    private const STD_UTF8_MODULE_NAME = 'string';
    private const STD_UTF8_STRUCT_NAME = 'String';
    private const STD_OPTION_MODULE_NAME = 'option';
    private const STD_OPTION_STRUCT_NAME = 'Option';

    /**
     * Check if the parameter is a transaction context
     *
     * @param array{ref: string|null, body: array<string, mixed>|string} $param The parameter to check
     * @return bool True if the parameter is a transaction context
     */
    public static function isTxContext(array $param): bool
    {
        $struct = is_array($param['body']) && isset($param['body']['datatype']) ? $param['body']['datatype'] : null;

        return null !== $struct &&
            Utils::normalizeSuiAddress($struct['package']) === Utils::normalizeSuiAddress('0x2') &&
            'tx_context' === $struct['module'] &&
            'TxContext' === $struct['type'];
    }

    /**
     * Get the pure BCS schema for a type signature
     *
     * @param array<string, mixed>|string $typeSignature The type signature to get the schema for
     * @return Type|null The BCS type or null if not found
     */
    public static function getPureBcsSchema(array|string $typeSignature): ?Type
    {
        if (is_string($typeSignature)) {
            return match ($typeSignature) {
                'address' => Map::address(),
                'bool' => Bcs::bool(),
                'u8' => Bcs::u8(),
                'u16' => Bcs::u16(),
                'u32' => Bcs::u32(),
                'u64' => Bcs::u64(),
                'u128' => Bcs::u128(),
                'u256' => Bcs::u256(),
                default => throw new \InvalidArgumentException("Unknown type signature {$typeSignature}"),
            };
        }

        if (isset($typeSignature['vector'])) {
            if ('u8' === $typeSignature['vector']) {
                return Bcs::vector(Bcs::u8())->transform(
                    'vector<u8>',
                    function (string|array $val): array {
                        if (is_string($val)) {
                            return array_values(unpack('C*', $val) ?: []);
                        }
                        return $val;
                    },
                    function (array $val): array {
                        return $val;
                    }
                );
            }
            $type = self::getPureBcsSchema($typeSignature['vector']);
            return $type ? Bcs::vector($type) : null;
        }

        if (isset($typeSignature['datatype'])) {
            $pkg = Utils::normalizeSuiAddress($typeSignature['datatype']['package']);

            if ($pkg === Utils::normalizeSuiAddress(Constants::MOVE_STDLIB_ADDRESS)) {
                if (
                    self::STD_ASCII_MODULE_NAME === $typeSignature['datatype']['module'] &&
                    self::STD_ASCII_STRUCT_NAME === $typeSignature['datatype']['type']
                ) {
                    return Bcs::string();
                }

                if (
                    self::STD_UTF8_MODULE_NAME === $typeSignature['datatype']['module'] &&
                    self::STD_UTF8_STRUCT_NAME === $typeSignature['datatype']['type']
                ) {
                    return Bcs::string();
                }

                if (
                    self::STD_OPTION_MODULE_NAME === $typeSignature['datatype']['module'] &&
                    self::STD_OPTION_STRUCT_NAME === $typeSignature['datatype']['type']
                ) {
                    $type = self::getPureBcsSchema($typeSignature['datatype']['typeParameters'][0]);
                    return $type ? Bcs::vector($type) : null;
                }
            }

            if (
                $pkg === Utils::normalizeSuiAddress(Constants::SUI_FRAMEWORK_ADDRESS) &&
                self::OBJECT_MODULE_NAME === $typeSignature['datatype']['module'] &&
                self::ID_STRUCT_NAME === $typeSignature['datatype']['type']
            ) {
                return Map::address();
            }
        }

        return null;
    }

    /**
     * Convert a normalized type to a Move type signature
     *
     * @param array<string, mixed>|string $type The normalized type to convert
     * @return array{ref: string|null, body: array<string, mixed>|string} The Move type signature
     */
    public static function normalizedTypeToMoveTypeSignature(array|string $type): array
    {
        if (is_array($type) && isset($type['Reference'])) {
            return [
                'ref' => '&',
                'body' => self::normalizedTypeToMoveTypeSignatureBody($type['Reference']),
            ];
        }

        if (is_array($type) && isset($type['MutableReference'])) {
            return [
                'ref' => '&mut',
                'body' => self::normalizedTypeToMoveTypeSignatureBody($type['MutableReference']),
            ];
        }

        return [
            'ref' => null,
            'body' => self::normalizedTypeToMoveTypeSignatureBody($type),
        ];
    }

    /**
     * Convert a normalized type to a Move type signature body
     *
     * @param array<string, mixed>|string $type The normalized type to convert
     * @return array<string, mixed>|string The Move type signature body
     */
    private static function normalizedTypeToMoveTypeSignatureBody(array|string $type): array|string
    {
        if (is_string($type)) {
            return match ($type) {
                'Address' => 'address',
                'Bool' => 'bool',
                'U8' => 'u8',
                'U16' => 'u16',
                'U32' => 'u32',
                'U64' => 'u64',
                'U128' => 'u128',
                'U256' => 'u256',
                default => throw new \InvalidArgumentException("Unexpected type {$type}"),
            };
        }

        if (isset($type['Vector'])) {
            return ['vector' => self::normalizedTypeToMoveTypeSignatureBody($type['Vector'])];
        }

        if (isset($type['Struct'])) {
            return [
                'datatype' => [
                    'package' => $type['Struct']['address'],
                    'module' => $type['Struct']['module'],
                    'type' => $type['Struct']['name'],
                    'typeParameters' => array_map(
                        fn($arg) => self::normalizedTypeToMoveTypeSignatureBody($arg),
                        $type['Struct']['typeArguments']
                    ),
                ],
            ];
        }

        if (isset($type['TypeParameter'])) {
            return ['typeParameter' => $type['TypeParameter']];
        }

        throw new \InvalidArgumentException('Unexpected type ' . json_encode($type));
    }

    /**
     * Get a pure BCS schema from an Open Move type signature body
     *
     * @param array<string, mixed>|string $typeSignature The type signature to get the schema for
     * @return Type The BCS type
     */
    public static function pureBcsSchemaFromOpenMoveTypeSignatureBody(array|string $typeSignature): Type
    {
        if (is_string($typeSignature)) {
            return match ($typeSignature) {
                'address' => Map::address(),
                'bool' => Bcs::bool(),
                'u8' => Bcs::u8(),
                'u16' => Bcs::u16(),
                'u32' => Bcs::u32(),
                'u64' => Bcs::u64(),
                'u128' => Bcs::u128(),
                'u256' => Bcs::u256(),
                default => throw new \InvalidArgumentException("Unknown type signature {$typeSignature}"),
            };
        }

        if (isset($typeSignature['vector'])) {
            return Bcs::vector(self::pureBcsSchemaFromOpenMoveTypeSignatureBody($typeSignature['vector']));
        }

        throw new \InvalidArgumentException('Expected pure typeSignature, but got ' . json_encode($typeSignature));
    }
}
