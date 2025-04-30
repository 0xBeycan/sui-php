<?php

declare(strict_types=1);

namespace Sui\Bcs;

class Bcs
{
    /**
     * Creates a Type that can be used to read and write an 8-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
     */
    public static function u128(array $options = []): Type
    {
        return Type::bigUInt(
            $options['name'] ?? 'u128',
            16,
            'read128',
            'write128',
            '340282366920938463463374607431768211455',
            $options['validate'] ?? null
        );
    }

    /**
     * Creates a Type that can be used to read and write a 256-bit unsigned integer.
     *
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
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
     * @return Type The created Type instance
     */
    public static function vector(Type $type, array $options = []): Type
    {
        return Type::dynamicSize(
            "vector<{$type->getName()}>",
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
                if (isset($options['validate'])) {
                    ($options['validate'])($value);
                }
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
            }
        );
    }

    /**
     * Creates a Type representing a tuple of a given set of types
     *
     * @param array<Type> $types The Types for each element in the tuple
     * @param array{name?: string, validate?: \Closure} $options Optional options
     * @return Type The created Type instance
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
     * @return Type The created Type instance
     */
    public static function struct(string $name, array $fields, array $options = []): Type
    {
        return new Type(
            $name,
            function (Reader $reader) use ($fields): array {
                $result = [];
                foreach ($fields as $field => $type) {
                    $result[$field] = $type->read($reader);
                }
                return $result;
            },
            function (array $value, Writer $writer) use ($fields): void {
                foreach ($fields as $field => $type) {
                    $type->write($value[$field], $writer);
                }
            },
            function (mixed $value) use ($options): void {
                if (isset($options['validate'])) {
                    ($options['validate'])($value);
                }
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
            },
            $options['serialize'] ?? null,
            function (mixed $values) use ($fields): ?int {
                $total = 0;
                foreach ($fields as $field => $type) {
                    $size = $type->serializedSize($values[$field]);
                    if (null === $size) {
                        return null;
                    }

                    $total += $size;
                }

                return $total;
            }
        );
    }

    /**
     * Creates a Type representing an enum of a given set of options
     *
     * @param string $name The name of the enum
     * @param array<string,Type> $variants The variants of the enum
     * @param array{validate?: \Closure} $options Optional options
     * @return Type The created Type instance
     */
    public static function enum(string $name, array $variants, array $options = []): Type
    {
        $variantKeys = array_keys($variants);
        return Type::dynamicSize(
            $name,
            function (Reader $reader) use ($variants, $variantKeys, $name): array {
                $index = $reader->readULEB();
                if (!isset($variantKeys[$index])) {
                    throw new \TypeError("Unknown value {$index} for enum {$name}");
                }

                $kind = $variantKeys[$index];
                $type = $variants[$kind];
                return [
                    $kind => $type?->read($reader) ?? true,
                    '$kind' => $kind,
                ];
            },
            function (array $value, Writer $writer) use ($variants, $variantKeys): void {
                $variant = array_filter($value, fn($k) => '$kind' !== $k, ARRAY_FILTER_USE_KEY);
                $variantName = key($variant);
                $variantValue = current($variant);

                foreach ($variantKeys as $i => $kind) {
                    $type = $variants[$kind];
                    if ($kind === $variantName) {
                        $writer->writeULEB($i);
                        $type?->write($variantValue, $writer);
                        return;
                    }
                }
            },
            function (mixed $value) use ($name, $variants, $options): void {
                if (isset($options['validate'])) {
                    ($options['validate'])($value);
                }
                if (!is_array($value) || null === $value) {
                    throw new \TypeError("Expected object, found " . gettype($value));
                }

                $keys = array_filter(array_keys($value), function ($k) use ($value, $variants) {
                    return isset($value[$k]) && array_key_exists($k, $variants);
                });

                if (1 !== count($keys)) {
                    throw new \TypeError(
                        "Expected object with one key, but found " . count($keys) . " for type {$name}"
                    );
                }

                $variant = array_values($keys)[0];

                if (!array_key_exists($variant, $variants)) {
                    throw new \TypeError("Invalid enum variant {$variant}");
                }
            }
        );
    }

    /**
     * Creates a Type representing a map of a given key and value type
     *
     * @param Type $keyType The Type of the key
     * @param Type $valueType The Type of the value
     * @return Type The created Type instance
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
