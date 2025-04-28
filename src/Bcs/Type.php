<?php

declare(strict_types=1);

namespace Sui\Bcs;

use Sui\Utils;

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
     * @return mixed The read value
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
     */
    public function serialize(mixed $value, ?array $options = null): Serialized
    {
        $this->validate($value);
        $bytes = ($this->serialize)($value, $options ?? []);
        $byteString = pack('C*', ...$bytes);
        return new Serialized($this, $byteString);
    }

    /**
     * Parse bytes into a value
     * @param array<int>|string $bytes The bytes to parse
     * @return mixed The parsed value
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
     * @return mixed The parsed value
     */
    public function fromHex(string $hex): mixed
    {
        return $this->parse(Utils::fromHex($hex));
    }

    /**
     * Parse a base58 string into a value
     * @param string $base58 The base58 string to parse
     * @return mixed The parsed value
     */
    public function fromBase58(string $base58): mixed
    {
        return $this->parse(Utils::fromBase58($base58));
    }

    /**
     * Parse a base64 string into a value
     * @param string $base64 The base64 string to parse
     * @return mixed The parsed value
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
     */
    public function validate(mixed $value): void
    {
        ($this->validate)($value);
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
     * @param string $name The name of the type
     * @param int $size The fixed size
     * @param \Closure $read Function to read the type
     * @param \Closure $write Function to write the type
     * @param \Closure|null $validate Optional validation function
     * @return self The created type
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
     * @return self The created type
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
     * @return self The created type
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
            function ($value, Writer $writer) use ($writeMethod): void {
                $writer->$writeMethod($value);
            },
            function ($value) use ($name, $maxValue, $validate): void {
                $value = is_string($value) ? $value : (string)$value;
                if (bccomp($value, '0') < 0 || bccomp($value, (string)$maxValue) > 0) {
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
     * Create a string-like type
     * @param string $name The name of the type
     * @param \Closure $toBytes Function to convert string to bytes
     * @param \Closure $fromBytes Function to convert bytes to string
     * @param \Closure|null $validate Optional validation function
     * @return self The created type
     */
    public static function stringLike(
        string $name,
        \Closure $toBytes,
        \Closure $fromBytes,
        ?\Closure $validate = null
    ): self {
        return new self(
            $name,
            function (Reader $reader) use ($fromBytes): string {
                $length = $reader->readULEB();
                $bytes = $reader->readBytes($length);
                return $fromBytes($bytes);
            },
            function ($value, Writer $writer) use ($toBytes): void {
                $bytes = $toBytes($value);
                $writer->writeULEB(count($bytes));
                foreach ($bytes as $byte) {
                    $writer->write8($byte);
                }
            },
            function ($value, $options) use ($toBytes): array {
                $bytes = $toBytes($value);
                $size = Utils::ulebEncode(count($bytes));
                return array_merge($size, $bytes);
            },
            $validate ?? function (mixed $value): void {
                if (!is_string($value)) {
                    throw new \TypeError("Expected string, found " . gettype($value));
                }
            },
            function (mixed $value) use ($toBytes): ?int {
                return null; // Dynamic size
            }
        );
    }

    /**
     * Create a lazy type
     * @param \Closure $cb Function to create the type
     * @return self The created type
     */
    public static function lazy(\Closure $cb): self
    {
        $lazyType = null;
        return new self(
            'lazy',
            function (Reader $reader) use ($cb, &$lazyType): mixed {
                if (!$lazyType) {
                    $lazyType = $cb();
                }
                return $lazyType->read($reader);
            },
            function ($value, Writer $writer) use ($cb, &$lazyType): void {
                if (!$lazyType) {
                    $lazyType = $cb();
                }
                $lazyType->write($value, $writer);
            },
            function ($value, $options) use ($cb, &$lazyType): array {
                if (!$lazyType) {
                    $lazyType = $cb();
                }
                return $lazyType->serialize($value, $options)->toBytes();
            },
            function ($value) use ($cb, &$lazyType): void {
                if (!$lazyType) {
                    $lazyType = $cb();
                }
                $lazyType->validate($value);
            },
            function ($value) use ($cb, &$lazyType): ?int {
                if (!$lazyType) {
                    $lazyType = $cb();
                }
                return $lazyType->serializedSize($value);
            }
        );
    }

    /**
     * Transform the type into another type with different input/output types
     * @param string|null $name Optional new name for the type
     * @param \Closure|null $input Function to transform input values
     * @param \Closure|null $output Function to transform output values
     * @param \Closure|null $validate Optional validation function
     * @return self The transformed type
     */
    public function transform(
        ?string $name = null,
        ?\Closure $input = null,
        ?\Closure $output = null,
        ?\Closure $validate = null
    ): self {
        return new self(
            $name ?? $this->name,
            function (Reader $reader) use ($output): mixed {
                $value = $this->read($reader);
                return $output ? $output($value) : $value;
            },
            function ($value, Writer $writer) use ($input): void {
                $transformedValue = $input ? $input($value) : $value;
                $this->write($transformedValue, $writer);
            },
            function ($value, $options) use ($input): array {
                $transformedValue = $input ? $input($value) : $value;
                $serialized = $this->serialize($transformedValue, $options);
                return array_values(unpack('C*', $serialized->toBytes()) ?: []);
            },
            $validate ?? function ($value): void {
                $this->validate($value);
            },
            function ($value) use ($input): ?int {
                $transformedValue = $input ? $input($value) : $value;
                return $this->serializedSize($transformedValue);
            }
        );
    }

    /**
     * Create a dynamic size type
     * @param string $name The name of the type
     * @param \Closure $read Function to read the type
     * @param \Closure $write Function to write the type
     * @param \Closure|null $validate Optional validation function
     * @return self The created type
     */
    public static function dynamicSize(string $name, \Closure $read, \Closure $write, ?\Closure $validate = null): self
    {
        return new self(
            $name,
            $read,
            $write,
            function ($value, $options) use ($write): array {
                $writer = new Writer($options ?? []);
                $write($value, $writer);
                return $writer->toBytes();
            },
            $validate ?? function ($value): void {
            },
            function (): ?int {
                return null;
            }
        );
    }
}
