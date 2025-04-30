<?php

declare(strict_types=1);

namespace Sui\Bcs;

use Sui\Utils;

class Serializer
{
    private const VECTOR_REGEX = '/^vector<(.+)>$/';
    private const STRUCT_REGEX = '/^([^:]+)::([^:]+)::([^<]+)(<(.+)>)?/';

    /**
     * Parse a type tag from a string representation
     *
     * @param string $str The string to parse
     * @param bool $normalizeAddress Whether to normalize the address
     * @return array<mixed> The parsed type tag
     */
    public static function parseFromStr(string $str, bool $normalizeAddress = false): array
    {
        if ('address' === $str) {
            return ['address' => null];
        } elseif ('bool' === $str) {
            return ['bool' => null];
        } elseif ('u8' === $str) {
            return ['u8' => null];
        } elseif ('u16' === $str) {
            return ['u16' => null];
        } elseif ('u32' === $str) {
            return ['u32' => null];
        } elseif ('u64' === $str) {
            return ['u64' => null];
        } elseif ('u128' === $str) {
            return ['u128' => null];
        } elseif ('u256' === $str) {
            return ['u256' => null];
        } elseif ('signer' === $str) {
            return ['signer' => null];
        }

        if (preg_match(self::VECTOR_REGEX, $str, $vectorMatch)) {
            return [
                'vector' => self::parseFromStr($vectorMatch[1], $normalizeAddress)
            ];
        }

        if (preg_match(self::STRUCT_REGEX, $str, $structMatch)) {
            $address = $normalizeAddress ? Utils::normalizeSuiAddress($structMatch[1]) : $structMatch[1];
            return [
                'struct' => [
                    'address' => $address,
                    'module' => $structMatch[2],
                    'name' => $structMatch[3],
                    'typeParams' => isset($structMatch[5]) && !empty($structMatch[5])
                        ? self::parseStructTypeArgs($structMatch[5], $normalizeAddress)
                        : []
                ]
            ];
        }

        throw new \Exception("Encountered unexpected token when parsing type args for {$str}");
    }

    /**
     * Parse struct type arguments from a string
     *
     * @param string $str The string to parse
     * @param bool $normalizeAddress Whether to normalize the address
     * @return array<mixed> The parsed type tags
     */
    public static function parseStructTypeArgs(string $str, bool $normalizeAddress = false): array
    {
        return array_map(
            fn($tok) => self::parseFromStr($tok, $normalizeAddress),
            Utils::splitGenericParameters($str)
        );
    }

    /**
     * Convert a type tag to its string representation
     *
     * @param array<mixed> $tag The type tag to convert
     * @return string The string representation
     */
    public static function tagToString(array $tag): string
    {
        if (isset($tag['bool'])) {
            return 'bool';
        }
        if (isset($tag['u8'])) {
            return 'u8';
        }
        if (isset($tag['u16'])) {
            return 'u16';
        }
        if (isset($tag['u32'])) {
            return 'u32';
        }
        if (isset($tag['u64'])) {
            return 'u64';
        }
        if (isset($tag['u128'])) {
            return 'u128';
        }
        if (isset($tag['u256'])) {
            return 'u256';
        }
        if (isset($tag['address'])) {
            return 'address';
        }
        if (isset($tag['signer'])) {
            return 'signer';
        }
        if (isset($tag['vector'])) {
            return 'vector<' . self::tagToString($tag['vector']) . '>';
        }
        if (isset($tag['struct'])) {
            $struct = $tag['struct'];
            $typeParams = array_map(
                fn($param) => self::tagToString($param),
                $struct['typeParams']
            );
            $typeParamsStr = implode(', ', $typeParams);
            return sprintf(
                '%s::%s::%s%s',
                $struct['address'],
                $struct['module'],
                $struct['name'],
                $typeParamsStr ? "<{$typeParamsStr}>" : ''
            );
        }

        throw new \Exception('Invalid TypeTag');
    }

    /**
     * @param string $name
     * @return Type
     */
    public static function pureBcsSchemaFromTypeName(string $name): Type
    {
        switch ($name) {
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
            case 'bool':
                return Bcs::bool();
            case 'string':
                return Bcs::string();
            case 'id':
            case 'address':
                return Map::address();
        }
        $generic = preg_match('/^(vector|option)<(.+)>$/', $name, $matches);
        if ($generic) {
            $kind = $matches[1];
            $inner = $matches[2];
            if ('vector' === $kind) {
                return Bcs::vector(self::pureBcsSchemaFromTypeName($inner));
            } else {
                return Bcs::option(self::pureBcsSchemaFromTypeName($inner));
            }
        }

        throw new \Exception("Invalid Pure type name: {$name}");
    }
}
