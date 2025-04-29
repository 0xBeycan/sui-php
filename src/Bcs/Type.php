<?php

declare(strict_types=1);

namespace Sui\Bcs;

use Sui\Utils;

/**
 * @template T
 * @template Input of T
 */
class Type
{
    private string $name;
    private \Closure $read;
    private \Closure $write;
    private \Closure $serialize;
    private \Closure $validate;
    private \Closure $serializedSize;

    /**
     * @param string $name The name of the type
     * @param \Closure $read Function to read the type from a reader
     * @param \Closure $write Function to write the type to a writer
     * @param \Closure $serialize Function to serialize the type
     * @param \Closure $validate Function to validate the type
     * @param \Closure $serializedSize Function to get the serialized size
     */
    public function __construct(
        string $name,
        \Closure $read,
        \Closure $write,
        \Closure $serialize,
        \Closure $validate,
        \Closure $serializedSize
    ) {
        $this->name = $name;
        $this->read = $read;
        $this->write = $write;
        $this->serialize = $serialize;
        $this->validate = $validate;
        $this->serializedSize = $serializedSize;
    }

    /**
     * Get the name of the type
     * @return string The name of the type
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Read the type from a reader
     * @param Reader $reader The reader to read from
     * @return T The read value
     */
    public function read(Reader $reader): mixed
    {
        return ($this->read)($reader);
    }

    /**
     * Write the type to a writer
     * @param mixed $value The value to write
     * @param Writer $writer The writer to write to
     * @return void
     * @throws \TypeError If validation fails
     */
    public function write(mixed $value, Writer $writer): void
    {
        $this->validate($value);
        ($this->write)($value, $writer);
    }

    /**
     * Serialize the type
     * @param mixed $value The value to serialize
     * @param array<string,mixed>|null $options Serialization options
     * @return Serialized The serialized value
     * @throws \TypeError If validation fails
     */
    public function serialize(mixed $value, ?array $options = null): Serialized
    {
        $this->validate($value);
        $bytes = ($this->serialize)($value, $options ?? []);
        if (!is_array($bytes)) {
            throw new \TypeError('Serialized value must be an array of bytes');
        }
        $byteString = pack('C*', ...$bytes);
        return new Serialized($this, $byteString);
    }

    /**
     * Parse bytes into a value
     * @param array<int>|string $bytes The bytes to parse
     * @return T The parsed value
     */
    public function parse(array|string $bytes): mixed
    {
        if (is_string($bytes)) {
            if (str_starts_with($bytes, '0x')) {
                $bytes = substr($bytes, 2);
            }
            if (ctype_xdigit($bytes)) {
                if (0 !== strlen($bytes) % 2) {
                    $bytes = '0' . $bytes;
                }
                $reader = new Reader($bytes);
            } else {
                $bytes = array_values(unpack('C*', $bytes) ?: []);
                $reader = new Reader(Utils::toHex($bytes));
            }
        } else {
            $reader = new Reader(Utils::toHex($bytes));
        }
        return $this->read($reader);
    }

    /**
     * Parse a hex string into a value
     * @param string $hex The hex string to parse
     * @return T The parsed value
     */
    public function fromHex(string $hex): mixed
    {
        return $this->parse(Utils::fromHex($hex));
    }

    /**
     * Parse a base58 string into a value
     * @param string $base58 The base58 string to parse
     * @return T The parsed value
     */
    public function fromBase58(string $base58): mixed
    {
        return $this->parse(Utils::fromBase58($base58));
    }

    /**
     * Parse a base64 string into a value
     * @param string $base64 The base64 string to parse
     * @return T The parsed value
     */
    public function fromBase64(string $base64): mixed
    {
        $bytes = base64_decode($base64, true);
        if (false === $bytes) {
            throw new \TypeError('Invalid base64 string');
        }
        $byteArray = array_values(unpack('C*', $bytes));
        return $this->parse($byteArray);
    }

    /**
     * Validate a value
     * @param mixed $value The value to validate
     * @return void
     * @throws \TypeError If validation fails
     */
    public function validate(mixed $value): void
    {
        try {
            ($this->validate)($value);
        } catch (\Throwable $e) {
            throw new \TypeError(sprintf(
                'Invalid %s value: %s. %s',
                $this->name,
                is_scalar($value) ? (string)$value : gettype($value),
                $e->getMessage()
            ));
        }
    }

    /**
     * Get the serialized size of a value
     * @param mixed $value The value to get the size of
     * @return int|null The size or null if dynamic
     */
    public function serializedSize(mixed $value): ?int
    {
        return ($this->serializedSize)($value);
    }

    /**
     * Create a fixed size type
     * @template T2
     * @template Input2 of T2
     * @param string $name The name of the type
     * @param int $size The fixed size
     * @param \Closure $read Function to read the type
     * @param \Closure $write Function to write the type
     * @param \Closure|null $validate Optional validation function
     * @return self<T2,Input2> The created type
     */
    public static function fixedSize(
        string $name,
        int $size,
        \Closure $read,
        \Closure $write,
        ?\Closure $validate = null
    ): self {
        return new self(
            $name,
            $read,
            $write,
            function ($value, $options) use ($write): array {
                $writer = new Writer($options);
                $write($value, $writer);
                return $writer->toBytes();
            },
            $validate ?? function (): void {
            },
            function () use ($size): int {
                return $size;
            }
        );
    }

    /**
     * Create an unsigned integer type
     * @param string $name The name of the type
     * @param int $size The size in bytes
     * @param string $readMethod The reader method name
     * @param string $writeMethod The writer method name
     * @param int $maxValue The maximum value
     * @param \Closure|null $validate Optional validation function
     * @return self<int,int> The created type
     */
    public static function uInt(
        string $name,
        int $size,
        string $readMethod,
        string $writeMethod,
        int $maxValue,
        ?\Closure $validate = null
    ): self {
        return self::fixedSize(
            $name,
            $size,
            function (Reader $reader) use ($readMethod): int {
                return $reader->$readMethod();
            },
            function ($value, Writer $writer) use ($writeMethod): void {
                $writer->$writeMethod($value);
            },
            function ($value) use ($name, $maxValue, $validate): void {
                if ($value < 0 || $value > $maxValue) {
                    throw new \TypeError(
                        "Invalid {$name} value: {$value}. Expected value in range 0-{$maxValue}"
                    );
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Create a big unsigned integer type
     * @param string $name The name of the type
     * @param int $size The size in bytes
     * @param string $readMethod The reader method name
     * @param string $writeMethod The writer method name
     * @param string $maxValue The maximum value
     * @param \Closure|null $validate Optional validation function
     * @return self<string,string|int|float> The created type
     */
    public static function bigUInt(
        string $name,
        int $size,
        string $readMethod,
        string $writeMethod,
        string $maxValue,
        ?\Closure $validate = null
    ): self {
        return self::fixedSize(
            $name,
            $size,
            function (Reader $reader) use ($readMethod): string {
                return $reader->$readMethod();
            },
            function (mixed $value, Writer $writer) use ($writeMethod): void {
                $writer->$writeMethod((string)$value);
            },
            function (mixed $value) use ($name, $maxValue, $validate): void {
                if (!is_string($value) && !is_int($value)) {
                    throw new \TypeError("Expected string or integer, found " . gettype($value));
                }
                $value = (string)$value;
                if (bccomp($value, '0') < 0 || bccomp($value, $maxValue) > 0) {
                    throw new \TypeError("Value out of range for {$name}");
                }
                if ($validate) {
                    $validate($value);
                }
            }
        );
    }

    /**
     * Create a string-like type
     * @param string $name The name of the type
     * @param \Closure $toBytes Function to convert to bytes
     * @param \Closure $fromBytes Function to convert from bytes
     * @param \Closure|null $validate Optional validation function
     * @return self<string,string> The created type
     */
    public static function stringLike(
        string $name,
        \Closure $toBytes,
        \Closure $fromBytes,
        ?\Closure $validate = null
    ): self {
        return self::dynamicSize(
            $name,
            function (Reader $reader) use ($fromBytes): mixed {
                $length = $reader->readULEB();
                $bytes = $reader->readBytes($length);
                return $fromBytes($bytes);
            },
            function (mixed $value, Writer $writer) use ($toBytes): void {
                $bytes = $toBytes($value);
                $writer->writeULEB(count($bytes));
                foreach ($bytes as $byte) {
                    $writer->write8($byte);
                }
            },
            $validate
        );
    }

    /**
     * Create a lazy type
     * @template T2
     * @template Input2 of T2
     * @param \Closure $cb Function to create the type
     * @return self<T2,Input2> The created type
     */
    public static function lazy(\Closure $cb): self
    {
        $type = null;
        $init = function () use ($cb, &$type): Type {
            if (null === $type) {
                $type = $cb();
            }
            return $type;
        };

        return new self(
            'lazy',
            function (Reader $reader) use ($init): mixed {
                return $init()->read($reader);
            },
            function (mixed $value, Writer $writer) use ($init): void {
                $init()->write($value, $writer);
            },
            function (mixed $value, array $options) use ($init): array {
                $serialized = $init()->serialize($value, $options);
                return array_values(unpack('C*', $serialized->toBytes()) ?: []);
            },
            function (mixed $value) use ($init): void {
                $init()->validate($value);
            },
            function (mixed $value) use ($init): ?int {
                return $init()->serializedSize($value);
            }
        );
    }

    /**
     * Create a new type that transforms values during serialization and deserialization
     *
     * @param string|null $name Optional name for the new type
     * @param \Closure|null $input Optional function to transform input values
     * @param \Closure|null $output Optional function to transform output values
     * @param \Closure|null $validate Optional function to validate values
     * @return self The transformed type
     */
    public function transform(
        string $name,
        ?\Closure $input = null,
        ?\Closure $output = null,
        ?\Closure $validate = null
    ): self {
        $this->name = $name;
        $this->validate = $validate;

        if ($input) {
            $this->read = function (Reader $reader) use ($input) {
                $value = $this->read($reader);
                return $input($value);
            };
        }

        if ($output) {
            $this->write = function (mixed $value, Writer $writer) use ($output): void {
                $transformed = $output($value);
                $this->write($transformed, $writer);
            };
        }

        return $this;
    }

    /**
     * Create a dynamic size type
     * @template T2
     * @template Input2 of T2
     * @param string $name The name of the type
     * @param \Closure $read Function to read the type
     * @param \Closure $write Function to write the type
     * @param \Closure|null $validate Optional validation function
     * @return self<T2,Input2> The created type
     */
    public static function dynamicSize(string $name, \Closure $read, \Closure $write, ?\Closure $validate = null): self
    {
        return new self(
            $name,
            $read,
            $write,
            function ($value, $options) use ($write): array {
                $writer = new Writer($options);
                $write($value, $writer);
                return $writer->toBytes();
            },
            $validate ?? function (): void {
            },
            function (): ?int {
                return null;
            }
        );
    }
}
