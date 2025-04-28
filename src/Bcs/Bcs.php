<?php

declare(strict_types=1);

namespace Sui\Bcs;

use Sui\Utils;

class Bcs
{
    /**
     * Creates a Type that can be used to read and write an 8-bit unsigned integer.
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function u8(string $name = 'u8', ?\Closure $validate = null): Type
    {
        return Type::uInt($name, 1, 'read8', 'write8', 2 ** 8 - 1, $validate);
    }

    /**
     * Creates a Type that can be used to read and write a 16-bit unsigned integer.
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function u16(string $name = 'u16', ?\Closure $validate = null): Type
    {
        return Type::uInt($name, 2, 'read16', 'write16', 2 ** 16 - 1, $validate);
    }

    /**
     * Creates a Type that can be used to read and write a 32-bit unsigned integer.
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function u32(string $name = 'u32', ?\Closure $validate = null): Type
    {
        return Type::uInt($name, 4, 'read32', 'write32', 2 ** 32 - 1, $validate);
    }

    /**
     * Creates a Type that can be used to read and write a 64-bit unsigned integer.
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function u64(string $name = 'u64', ?\Closure $validate = null): Type
    {
        return Type::bigUInt($name, 8, 'read64', 'write64', (string)(2 ** 64 - 1), $validate);
    }

    /**
     * Creates a Type that can be used to read and write a 128-bit unsigned integer.
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function u128(string $name = 'u128', ?\Closure $validate = null): Type
    {
        return Type::bigUInt($name, 16, 'read128', 'write128', (string)(2 ** 128 - 1), $validate);
    }

    /**
     * Creates a Type that can be used to read and write a 256-bit unsigned integer.
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function u256(string $name = 'u256', ?\Closure $validate = null): Type
    {
        return Type::bigUInt($name, 32, 'read256', 'write256', (string)(2 ** 256 - 1), $validate);
    }

    /**
     * Creates a Type that can be used to read and write boolean values.
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function bool(string $name = 'bool', ?\Closure $validate = null): Type
    {
        return Type::fixedSize(
            $name,
            1,
            function (Reader $reader): bool {
                return 1 === $reader->read8();
            },
            function (bool $value, Writer $writer): void {
                $writer->write8($value ? 1 : 0);
            },
            function (mixed $value) use ($validate): void {
                if (!is_bool($value)) {
                    throw new \TypeError("Expected boolean, found " . gettype($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type that can be used to read and write unsigned LEB encoded integers
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function uleb128(string $name = 'uleb128', ?\Closure $validate = null): Type
    {
        return self::dynamicSize(
            $name,
            function (Reader $reader): int {
                return $reader->readULEB();
            },
            function (int $value, Writer $writer): void {
                $writer->writeULEB($value);
            },
            function (mixed $value) use ($validate): void {
                if (!is_int($value)) {
                    throw new \TypeError("Expected integer, found " . gettype($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a fixed length byte array
     *
     * @param int $size The number of bytes this type represents
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function bytes(int $size, string $name = null, ?\Closure $validate = null): Type
    {
        $name = $name ?? "bytes[{$size}]";
        return Type::fixedSize(
            $name,
            $size,
            function (Reader $reader) use ($size): array {
                return Utils::fromHex($reader->readBytes($size));
            },
            function (array $value, Writer $writer) use ($size): void {
                for ($i = 0; $i < $size; $i++) {
                    $writer->write8($value[$i] ?? 0);
                }
            },
            function (mixed $value) use ($size, $validate): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if (count($value) !== $size) {
                    throw new \TypeError("Expected array of length {$size}, found " . count($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a variable length byte array
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function byteVector(string $name = 'bytesVector', ?\Closure $validate = null): Type
    {
        return self::dynamicSize(
            $name,
            function (Reader $reader): array {
                $length = $reader->readULEB();
                $bytes = $reader->readBytes($length);
                return array_values(unpack('C*', $bytes) ?: []);
            },
            function (array $value, Writer $writer): void {
                $writer->writeULEB(count($value));
                foreach ($value as $byte) {
                    $writer->write8($byte ?? 0);
                }
            },
            function (mixed $value) use ($validate): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type that can ser/de string values. Strings will be UTF-8 encoded
     *
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function string(string $name = 'string', ?\Closure $validate = null): Type
    {
        return Type::stringLike(
            $name,
            function (string $value): array {
                return array_values(unpack('C*', $value) ?: []);
            },
            function (array $bytes): string {
                return implode(array_map('chr', $bytes));
            },
            function (mixed $value) use ($validate): void {
                if (!is_string($value)) {
                    throw new \TypeError("Expected string, found " . gettype($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type that represents a fixed length array of a given type
     *
     * @param int $size The number of elements in the array
     * @param Type $type The Type of each element in the array
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function fixedArray(int $size, Type $type, string $name = null, ?\Closure $validate = null): Type
    {
        $name = $name ?? "{$type->getName()}[{$size}]";
        return Type::fixedSize(
            $name,
            $size * $type->serializedSize([]),
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
            function (mixed $value) use ($size, $validate): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if (count($value) !== $size) {
                    throw new \TypeError("Expected array of length {$size}, found " . count($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing an optional value
     *
     * @param Type $type The Type of the optional value
     * @param string $name The name of the type
     * @return Type The created Type instance
     */
    public static function option(Type $type, string $name = null): Type
    {
        $name = $name ?? "Option<{$type->getName()}>";
        return self::enum($name, [
            'None' => null,
            'Some' => $type
        ])->transform(
            $name,
            function (mixed $value): array {
                if (null === $value) {
                    return ['None' => true];
                }
                return ['Some' => $value];
            },
            function (array $value): mixed {
                return 'Some' === $value['$kind'] ? $value['Some'] : null;
            }
        );
    }

    /**
     * Creates a Type representing a variable length vector of a given type
     *
     * @param Type $type The Type of each element in the vector
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function vector(Type $type, string $name = null, ?\Closure $validate = null): Type
    {
        $name = $name ?? "vector<{$type->getName()}>";
        return self::dynamicSize(
            $name,
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
            function (mixed $value) use ($validate): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a tuple of a given set of types
     *
     * @param array<Type> $types The Types for each element in the tuple
     * @param string $name The name of the type
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function tuple(array $types, string $name = null, ?\Closure $validate = null): Type
    {
        $name = $name ?? '(' . implode(', ', array_map(fn($t) => $t->getName(), $types)) . ')';
        return Type::fixedSize(
            $name,
            array_sum(array_map(fn($t) => $t->serializedSize([]), $types)),
            function (Reader $reader) use ($types): array {
                $result = [];
                foreach ($types as $type) {
                    $result[] = $type->read($reader);
                }
                return $result;
            },
            function (array $value, Writer $writer) use ($types): void {
                foreach ($types as $i => $type) {
                    $type->write($value[$i], $writer);
                }
            },
            function (mixed $value) use ($types, $validate): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if (count($value) !== count($types)) {
                    throw new \TypeError("Expected array of length " . count($types) . ", found " . count($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a struct of a given set of fields
     *
     * @param string $name The name of the struct
     * @param array<string,Type> $fields The fields of the struct
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function struct(string $name, array $fields, ?\Closure $validate = null): Type
    {
        $canonicalOrder = array_entries($fields);
        $size = array_sum(array_map(fn($t) => $t->serializedSize([]), $fields));

        return Type::fixedSize(
            $name,
            $size,
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
            function (mixed $value) use ($validate): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing an enum of a given set of options
     *
     * @param string $name The name of the enum
     * @param array<string,Type|null> $values The values of the enum
     * @param \Closure|null $validate Optional validation function
     * @return Type The created Type instance
     */
    public static function enum(string $name, array $values, ?\Closure $validate = null): Type
    {
        $canonicalOrder = array_entries($values);

        return self::dynamicSize(
            $name,
            function (Reader $reader) use ($name, $canonicalOrder): array {
                $index = $reader->readULEB();
                if (!isset($canonicalOrder[$index])) {
                    throw new \TypeError("Unknown value {$index} for enum {$name}");
                }

                [$kind, $type] = $canonicalOrder[$index];
                return [
                    $kind => $type?->read($reader) ?? true,
                    '$kind' => $kind
                ];
            },
            function (array $value, Writer $writer) use ($canonicalOrder, $values): void {
                $entry = array_filter(
                    array_entries($value),
                    fn($entry) => array_key_exists($entry[0], $values)
                )[0];

                foreach ($canonicalOrder as $i => [$optionName, $optionType]) {
                    if ($optionName === $entry[0]) {
                        $writer->writeULEB($i);
                        $optionType?->write($entry[1], $writer);
                        return;
                    }
                }
            },
            function (mixed $value) use ($name, $values, $validate): void {
                if (!is_array($value)) {
                    throw new \TypeError("Expected array, found " . gettype($value));
                }

                $keys = array_filter(
                    array_keys($value),
                    fn($k) => null !== $value[$k] && array_key_exists($k, $values)
                );

                if (1 === count($keys)) {
                    throw new \TypeError(
                        "Expected array with one key, but found " . count($keys) . " for type {$name}"
                    );
                }

                $variant = $keys[0];
                if (!array_key_exists($variant, $values)) {
                    throw new \TypeError("Invalid enum variant {$variant}");
                }

                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Creates a Type representing a map of a given key and value type
     *
     * @param Type $keyType The Type of the key
     * @param Type $valueType The Type of the value
     * @param string $name The name of the type
     * @return Type The created Type instance
     */
    public static function map(Type $keyType, Type $valueType, string $name = null): Type
    {
        $name = $name ?? "Map<{$keyType->getName()}, {$valueType->getName()}>";
        return self::vector(self::tuple([$keyType, $valueType]))->transform(
            $name,
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

    /**
     * Create a type with dynamic size
     *
     * @param string $name The name of the type
     * @param \Closure $read The read function
     * @param \Closure $write The write function
     * @param \Closure $validate The validate function
     * @return Type The type
     */
    public static function dynamicSize(string $name, \Closure $read, \Closure $write, \Closure $validate): Type
    {
        return new Type(
            $name,
            $read,
            $write,
            $validate,
            function (): int {
                return 0;
            },
            function (): int {
                return 0;
            }
        );
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
