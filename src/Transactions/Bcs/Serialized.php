<?php

declare(strict_types=1);

namespace Sui\Transactions\Bcs;

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
     * @throws \RuntimeException When base58 conversion is not implemented
     */
    public function toBase58(): string
    {
        // Note: You'll need to implement or use a base58 library
        // This is a placeholder for the base58 conversion
        throw new \RuntimeException('Base58 conversion not implemented');
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
