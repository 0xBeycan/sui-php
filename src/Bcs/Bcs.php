<?php

declare(strict_types=1);

namespace Sui\Bcs;

use Sui\Utils;

class Bcs
{
    /**
     * Creates a Type that can be used to read and write an 8-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<int,int> The created Type instance
     */
    public static function u8(array $options = []): Type
    {
        return Type::uInt(
            $options['name'] ?? 'u8',
            1,
            'read8',
            'write8',
            2 ** 8 - 1,
            $options['validate'] ?? null
        );
    }

    /**
     * Creates a Type that can be used to read and write a 16-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<int,int> The created Type instance
     */
    public static function u16(array $options = []): Type
    {
        return Type::uInt(
            $options['name'] ?? 'u16',
            2,
            'read16',
            'write16',
            2 ** 16 - 1,
            $options['validate'] ?? null
        );
    }

    /**
     * Creates a Type that can be used to read and write a 32-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<int,int> The created Type instance
     */
    public static function u32(array $options = []): Type
    {
        return Type::uInt(
            $options['name'] ?? 'u32',
            4,
            'read32',
            'write32',
            2 ** 32 - 1,
            $options['validate'] ?? null
        );
    }

    /**
     * Creates a Type that can be used to read and write a 64-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<string,int|string> The created Type instance
     */
    public static function u64(array $options = []): Type
    {
        return Type::bigUInt(
            $options['name'] ?? 'u64',
            8,
            'read64',
            'write64',
            '18446744073709551615',
            $options['validate'] ?? null
        );
    }

    /**
     * Creates a Type that can be used to read and write a 128-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<string,int|string> The created Type instance
     */
    public static function u128(array $options = []): Type
    {
        return Type::bigUInt(
            $options['name'] ?? 'u128',
            16,
            'read128',
            'write128',
            (string)(2 ** 128 - 1),
            $options['validate'] ?? null
        );
    }

    /**
     * Creates a Type that can be used to read and write a 256-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<string,int|string> The created Type instance
     */
    public static function u256(array $options = []): Type
    {
        return Type::bigUInt(
            $options['name'] ?? 'u256',
            32,
            'read256',
            'write256',
            (string)(2 ** 256 - 1),
            $options['validate'] ?? null
        );
    }

    /**
     * Creates a Type that can be used to read and write boolean values.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<bool,bool> The created Type instance
     */
    public static function bool(array $options = []): Type
    {
        return Type::fixedSize(
            $options['name'] ?? 'bool',
            1,
            function (Reader $reader): bool {
                $value = $reader->read8();
                if ($value > 1) {
                    throw new \TypeError("Invalid boolean value: {$value}");
                }
                return 1 === $value;
            },
            function (bool $value, Writer $writer): void {
                $writer->write8($value ? 1 : 0);
            },
            function (mixed $value) use ($options): void {
                if (!is_bool($value)) {
                    throw new \TypeError("Expected boolean, found " . gettype($value));
                }
                if ($options['validate'] ?? null) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type that can be used to read and write unsigned LEB encoded integers
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<int,int> The created Type instance
     */
    public static function uleb128(array $options = []): Type
    {
        return Type::dynamicSize(
            $options['name'] ?? 'uleb128',
            function (Reader $reader): int {
                return $reader->readULEB();
            },
            function (int $value, Writer $writer): void {
                $writer->writeULEB($value);
            },
            function (mixed $value) use ($options): void {
                if (!is_int($value)) {
                    throw new \TypeError("Expected integer, found " . gettype($value));
                }
                if ($options['validate'] ?? null) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a fixed length byte array
     *
     * @param int $size The number of bytes this type represents
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<array<int>,array<int>> The created Type instance
     */
    public static function bytes(int $size, array $options = []): Type
    {
        $name = $options['name'] ?? "bytes[{$size}]";
        return Type::fixedSize(
            $name,
            $size,
            function (Reader $reader) use ($size): array {
                return $reader->readBytes($size);
            },
            function (array $value, Writer $writer) use ($size): void {
                for ($i = 0; $i < $size; $i++) {
                    $writer->write8($value[$i] ?? 0);
                }
            },
            function (mixed $value) use ($size, $options): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if (count($value) !== $size) {
                    throw new \TypeError("Expected array of length {$size}, found " . count($value));
                }
                if ($options['validate'] ?? null) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a variable length byte array
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<array<int>,array<int>> The created Type instance
     */
    public static function byteVector(array $options = []): Type
    {
        return Type::dynamicSize(
            $options['name'] ?? 'bytesVector',
            function (Reader $reader): array {
                $length = $reader->readULEB();
                return $reader->readBytes($length);
            },
            function (array $value, Writer $writer): void {
                $writer->writeULEB(count($value));
                $writer->writeBytes($value);
            },
            function (mixed $value) use ($options): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if ($options['validate'] ?? null) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type that can ser/de string values. Strings will be UTF-8 encoded
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<string,string> The created Type instance
     */
    public static function string(array $options = []): Type
    {
        return Type::stringLike(
            $options['name'] ?? 'string',
            function (string $value): array {
                return array_values(unpack('C*', $value) ?: []);
            },
            function (array $bytes): string {
                return pack('C*', ...$bytes);
            },
            function (mixed $value) use ($options): void {
                if (!is_string($value)) {
                    throw new \TypeError("Expected string, found " . gettype($value));
                }
                if ($options['validate'] ?? null) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a fixed length array of a given type
     *
     * @param int $size The number of elements in the array
     * @param Type $type The Type of each element in the array
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<array<mixed>,array<mixed>> The created Type instance
     */
    public static function fixedArray(int $size, Type $type, array $options = []): Type
    {
        $name = $options['name'] ?? "{$type->getName()}[{$size}]";
        return Type::fixedSize(
            $name,
            $size * $type->serializedSize((object)['_' => null]),
            function (Reader $reader) use ($size, $type): array {
                $result = [];
                for ($i = 0; $i < $size; $i++) {
                    $result[] = $type->read($reader);
                }
                return $result;
            },
            function (array $value, Writer $writer) use ($type): void {
                foreach ($value as $item) {
                    $type->write($item, $writer);
                }
            },
            function (mixed $value) use ($size, $options): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if (count($value) !== $size) {
                    throw new \TypeError("Expected array of length {$size}, found " . count($value));
                }
                if (null !== ($options['validate'] ?? null)) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing an optional value
     *
     * @param Type $type The Type of the optional value
     * @return Type<mixed,mixed> The created Type instance
     */
    public static function option(Type $type): Type
    {
        return Type::dynamicSize(
            "option<{$type->getName()}>",
            function (Reader $reader) use ($type): mixed {
                $hasValue = 1 === $reader->read8();
                if (!$hasValue) {
                    return null;
                }
                return $type->read($reader);
            },
            function (mixed $value, Writer $writer) use ($type): void {
                if (null === $value) {
                    $writer->write8(0);
                    return;
                }
                $writer->write8(1);
                $type->write($value, $writer);
            },
            function (mixed $value): void {
                if (null !== $value && !is_scalar($value) && !is_array($value)) {
                    throw new \TypeError("Expected scalar, array or null, found " . gettype($value));
                }
            }
        );
    }

    /**
     * Creates a Type representing a variable length vector of a given type
     *
     * @param Type $type The Type of each element in the vector
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<array<mixed>,array<mixed>> The created Type instance
     */
    public static function vector(Type $type, array $options = []): Type
    {
        return Type::dynamicSize(
            $options['name'] ?? 'vector',
            function (Reader $reader) use ($type): array {
                $length = $reader->readULEB();
                $result = [];
                for ($i = 0; $i < $length; $i++) {
                    $result[] = $type->read($reader);
                }
                return $result;
            },
            function (array $value, Writer $writer) use ($type): void {
                $writer->writeULEB(count($value));
                foreach ($value as $item) {
                    $type->write($item, $writer);
                }
            },
            function (mixed $value) use ($options): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if ($options['validate'] ?? null) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a tuple of a given set of types
     *
     * @param array<Type> $types The Types for each element in the tuple
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type<array<mixed>,array<mixed>> The created Type instance
     */
    public static function tuple(array $types, array $options = []): Type
    {
        $name = $options['name'] ?? '(' . implode(', ', array_map(fn($t) => $t->getName(), $types)) . ')';
        return Type::fixedSize(
            $name,
            array_sum(array_map(fn($t) => $t->serializedSize((object)['_' => null]), $types)),
            function (Reader $reader) use ($types): array {
                $result = [];
                foreach ($types as $type) {
                    $result[] = $type->read($reader);
                }
                return $result;
            },
            function (array $value, Writer $writer) use ($types): void {
                foreach ($types as $i => $type) {
                    $type->write($value[$i] ?? null, $writer);
                }
            },
            function (mixed $value) use ($types, $options): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if (count($value) !== count($types)) {
                    throw new \TypeError("Expected array of length " . count($types) . ", found " . count($value));
                }
                if (null !== ($options['validate'] ?? null)) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a struct of a given set of fields
     *
     * @param string $name The name of the struct
     * @param array<string,Type> $fields The fields of the struct
     * @param array{validate?: \Closure} $options Optional options
     * @return Type<array<string,mixed>,array<string,mixed>> The created Type instance
     */
    public static function struct(string $name, array $fields, array $options = []): Type
    {
        $canonicalOrder = array_entries($fields);

        return new Type(
            $name,
            function (Reader $reader) use ($canonicalOrder): array {
                $result = [];
                foreach ($canonicalOrder as [$field, $type]) {
                    $result[$field] = $type->read($reader);
                }
                return $result;
            },
            function (array $value, Writer $writer) use ($canonicalOrder): void {
                foreach ($canonicalOrder as [$field, $type]) {
                    $type->write($value[$field], $writer);
                }
            },
            function ($value, $options) use ($canonicalOrder): array {
                $writer = new Writer($options);
                foreach ($canonicalOrder as [$field, $type]) {
                    $type->write($value[$field], $writer);
                }
                return $writer->toBytes();
            },
            function (mixed $value) use ($options): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if ($options['validate'] ?? null) {
                    ($options['validate'])($value);
                }
            },
            function (mixed $value): ?int {
                return null; // Dynamic size for structs with dynamic fields
            }
        );
    }

    /**
     * Creates a Type representing an enum of a given set of options
     *
     * @param string $name The name of the enum
     * @param array<string,Type|null> $variants The variants of the enum
     * @param array{validate?: \Closure} $options Optional options
     * @return Type<array<string,mixed>,array<string,mixed>> The created Type instance
     */
    public static function enum(string $name, array $variants, array $options = []): Type
    {
        $canonicalOrder = array_entries($variants);

        return Type::dynamicSize(
            $name,
            function (Reader $reader) use ($canonicalOrder, $name): array {
                $index = $reader->readULEB();
                if (!isset($canonicalOrder[$index])) {
                    throw new \TypeError("Unknown value {$index} for enum {$name}");
                }

                [$kind, $type] = $canonicalOrder[$index];
                return [
                    $kind => $type?->read($reader) ?? true,
                    '$kind' => $kind,
                ];
            },
            function (array $value, Writer $writer) use ($canonicalOrder): void {
                $variant = array_filter($value, fn($k) => '$kind' !== $k, ARRAY_FILTER_USE_KEY);
                $variantName = key($variant);
                $variantValue = current($variant);

                foreach ($canonicalOrder as $i => [$kind, $type]) {
                    if ($kind === $variantName) {
                        $writer->writeULEB($i);
                        $type?->write($variantValue, $writer);
                        return;
                    }
                }
            },
            function (mixed $value) use ($name, $variants, $options): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }

                $keys = array_filter(array_keys($value), fn($k) => '$kind' !== $k && null !== ($value[$k] ?? null));
                if (1 !== count($keys)) {
                    throw new \TypeError(
                        "Expected object with one key, but found " . count($keys) . " for type {$name}"
                    );
                }

                $variant = $keys[0];
                if (!isset($variants[$variant])) {
                    throw new \TypeError("Invalid enum variant {$variant}");
                }

                if (null !== ($options['validate'] ?? null)) {
                    ($options['validate'])($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a map of a given key and value type
     *
     * @param Type $keyType The Type of the key
     * @param Type $valueType The Type of the value
     * @return Type<array<mixed,mixed>,array<mixed,mixed>> The created Type instance
     */
    public static function map(Type $keyType, Type $valueType): Type
    {
        return self::vector(self::tuple([$keyType, $valueType]))->transform(
            "Map<{$keyType->getName()}, {$valueType->getName()}>",
            function (array $value): array {
                return array_map(
                    fn($k, $v) => [$k, $v],
                    array_keys($value),
                    array_values($value)
                );
            },
            function (array $value): array {
                $result = [];
                foreach ($value as [$key, $val]) {
                    $result[$key] = $val;
                }
                return $result;
            }
        );
    }

    /**
     * Creates a Type that wraps another Type which is lazily evaluated
     *
     * @param \Closure $cb A callback that returns the Type
     * @return Type The created Type instance
     */
    public static function lazy(\Closure $cb): Type
    {
        return Type::lazy($cb);
    }
}

/**
 * Helper function to get array entries with keys
 *
 * @param array<mixed,mixed> $array The array to get entries from
 * @return array<array{0:mixed,1:mixed}> An array of [key, value] pairs
 */
function array_entries(array $array): array
{
    return array_map(
        fn($key) => [$key, $array[$key]],
        array_keys($array)
    );
}
