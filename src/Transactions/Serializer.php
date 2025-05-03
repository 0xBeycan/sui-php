<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Bcs\Bcs;
use Sui\Bcs\Map;
use Sui\Bcs\Type;
use Sui\Constants;
use Sui\Transactions\Type\TypeSignature;

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
     * @param TypeSignature $param
     * @return bool
     */
    public static function isTxContext(TypeSignature $param): bool
    {
        $struct =
            is_object($param->body) && isset($param->body->datatype) ? $param->body->datatype : null;

        return (
            !!$struct &&
            Utils::normalizeSuiAddress('0x2') === Utils::normalizeSuiAddress($struct->package) &&
            'tx_context' === $struct->module &&
            'TxContext' === $struct->type
        );
    }

    /**
     * Convert a string to an array of bytes
     * @param string $val
     * @return array<int>
     */
    public static function utf8Bytes(string $val): array
    {
        return array_values(unpack('C*', mb_convert_encoding($val, 'UTF-8')) ?: []);
    }

    /**
     * Get the BCS schema for a given type signature
     * @param mixed $typeSignature The type signature to get schema for
     * @return Type|null The BCS type or null if not found
     */
    public static function getPureBcsSchema(mixed $typeSignature): ?Type
    {
        if (is_string($typeSignature)) {
            switch ($typeSignature) {
                case 'address':
                    return Map::address();
                case 'bool':
                    return Bcs::bool();
                case 'u8':
                    return Bcs::u8();
                case 'u16':
                    return Bcs::u16();
                case 'u32':
                    return Bcs::u32();
                case 'u64':
                    return Bcs::u64();
                case 'u128':
                    return Bcs::u128();
                case 'u256':
                    return Bcs::u256();
                default:
                    throw new \InvalidArgumentException("Unknown type signature {$typeSignature}");
            }
        }

        if (isset($typeSignature->vector)) {
            if ('u8' === $typeSignature->vector) {
                return Bcs::vector(Bcs::u8())->transform(
                    null,
                    function (string|array $val) {
                        return is_string($val) ? self::utf8Bytes($val) : $val;
                    },
                    function (string|array $val) {
                        return $val;
                    },
                );
            }
            $type = self::getPureBcsSchema($typeSignature->vector);
            return $type ? Bcs::vector($type) : null;
        }

        if (isset($typeSignature->datatype)) {
            $pkg = Utils::normalizeSuiAddress($typeSignature->datatype->package);

            if ($pkg === Utils::normalizeSuiAddress(Constants::MOVE_STDLIB_ADDRESS)) {
                if (
                    self::STD_ASCII_MODULE_NAME === $typeSignature->datatype->module &&
                    self::STD_ASCII_STRUCT_NAME === $typeSignature->datatype->type
                ) {
                    return Bcs::string();
                }

                if (
                    self::STD_UTF8_MODULE_NAME === $typeSignature->datatype->module &&
                    self::STD_UTF8_STRUCT_NAME === $typeSignature->datatype->type
                ) {
                    return Bcs::string();
                }

                if (
                    self::STD_OPTION_MODULE_NAME === $typeSignature->datatype->module &&
                    self::STD_OPTION_STRUCT_NAME === $typeSignature->datatype->type
                ) {
                    $type = self::getPureBcsSchema($typeSignature->datatype->typeParameters[0]);
                    return $type ? Bcs::vector($type) : null;
                }
            }

            if (
                Utils::normalizeSuiAddress(Constants::SUI_FRAMEWORK_ADDRESS) === $pkg &&
                self::OBJECT_MODULE_NAME === $typeSignature->datatype->module &&
                self::ID_STRUCT_NAME === $typeSignature->datatype->type
            ) {
                return Map::address();
            }
        }

        return null;
    }

    /**
     * Convert a normalized type to a Move type signature body
     * @param mixed $type
     * @return mixed
     */
    public static function normalizedTypeToMoveTypeSignatureBody(
        mixed $type
    ): mixed {
        if (is_string($type)) {
            switch ($type) {
                case 'Address':
                    return 'address';
                case 'Bool':
                    return 'bool';
                case 'U8':
                    return 'u8';
                case 'U16':
                    return 'u16';
                case 'U32':
                    return 'u32';
                case 'U64':
                    return 'u64';
                case 'U128':
                    return 'u128';
                case 'U256':
                    return 'u256';
                default:
                    throw new \Exception("Unexpected type {$type}");
            }
        }

        if (isset($type->vector)) {
            return [
                'vector' => self::normalizedTypeToMoveTypeSignatureBody($type->vector),
            ];
        }

        // phpcs:disable
        if (isset($type->Struct)) {
            return [
                'datatype' => [
                    'package' => $type->Struct->address,
                    'module' => $type->Struct->module,
                    'type' => $type->Struct->name,
                    'typeParameters' => array_map(
                        fn(mixed $type) => self::normalizedTypeToMoveTypeSignatureBody($type),
                        $type->Struct->typeArguments,
                    ),
                ],
            ];
        }

        if (isset($type->TypeParameter)) {
            return [
                'typeParameter' => $type->TypeParameter,
            ];
        }
        // phpcs:enable
        throw new \Exception("Unexpected type {$type}");
    }

    /**
     * Convert a normalized type to a Move type signature
     * @param mixed $type
     * @return TypeSignature
     */
    public static function normalizedTypeToMoveTypeSignature(
        mixed $type
    ): TypeSignature {
        if (is_object($type) && isset($type->Reference)) { // phpcs:ignore
            return new TypeSignature(
                ref: '&',
                body: self::normalizedTypeToMoveTypeSignatureBody($type->Reference), // phpcs:ignore
            );
        }
        if (is_object($type) && isset($type->MutableReference)) { // phpcs:ignore
            return new TypeSignature(
                ref: '&mut',
                body: self::normalizedTypeToMoveTypeSignatureBody($type->MutableReference), // phpcs:ignore
            );
        }

        return new TypeSignature(
            ref: null,
            body: self::normalizedTypeToMoveTypeSignatureBody($type),
        );
    }

    /**
     * Convert a Move type signature to a pure BCS schema
     * @param mixed $typeSignature
     * @return Type
     */
    public static function pureBcsSchemaFromOpenMoveTypeSignatureBody(
        mixed $typeSignature
    ): Type {
        if (is_string($typeSignature)) {
            switch ($typeSignature) {
                case 'address':
                    return Map::address();
                case 'bool':
                    return Bcs::bool();
                case 'u8':
                    return Bcs::u8();
                case 'u16':
                    return Bcs::u16();
                case 'u32':
                    return Bcs::u32();
                case 'u64':
                    return Bcs::u64();
                case 'u128':
                    return Bcs::u128();
                case 'u256':
                    return Bcs::u256();
                default:
                    throw new \Exception("Unknown type signature {$typeSignature}");
            }
        }

        if (isset($typeSignature->vector)) {
            return Bcs::vector(self::pureBcsSchemaFromOpenMoveTypeSignatureBody($typeSignature->vector));
        }

        throw new \Exception("Expected pure typeSignature, but got {$typeSignature}");
    }
}
