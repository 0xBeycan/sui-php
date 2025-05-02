<?php

declare(strict_types=1);

namespace Sui\Bcs;

class Serialized
{
    private Type $schema;
    private string $bytes;

    /**
     * @param Type $type The BCS type schema
     * @param string $bytes The serialized bytes
     */
    public function __construct(Type $type, string $bytes)
    {
        $this->schema = $type;
        $this->bytes = $bytes;
    }

    /**
     * Returns the raw bytes
     * @return string The serialized bytes
     */
    public function toBytes(): string
    {
        return $this->bytes;
    }

    /**
     * Returns the bytes as an array of integers
     * @return array<int> The bytes as an array of integers
     */
    public function toArray(): array
    {
        return array_values(unpack('C*', $this->bytes) ?: []);
    }

    /**
     * Converts the bytes to a hexadecimal string
     * @return string The hexadecimal representation of the bytes
     */
    public function toHex(): string
    {
        return bin2hex($this->bytes);
    }

    /**
     * Converts the bytes to a base64 string
     * @return string The base64 representation of the bytes
     */
    public function toBase64(): string
    {
        return base64_encode($this->bytes);
    }

    /**
     * Converts the bytes to a base58 string
     * @return string The base58 representation of the bytes
     */
    public function toBase58(): string
    {
        return \Sui\Utils::toBase58($this->bytes);
    }

    /**
     * Parses the bytes according to the schema
     * @return mixed The parsed value
     */
    public function parse(): mixed
    {
        return $this->schema->parse(\Sui\Utils::fromHex($this->bytes));
    }
}
