<?php

declare(strict_types=1);

namespace Sui\Bcs;

/**
 * Class used to write BCS data into a buffer.
 * Most methods are chainable, so it is possible to write them in one go.
 */
class Writer
{
    /** @var array<int> */
    private array $data = [];

    /**
     * Write a byte into the buffer.
     * @param int $value Value to write
     * @return self Self for possible chaining
     * @throws \TypeError If value is not a valid byte
     */
    public function write8(int $value): self
    {
        if ($value < 0 || $value > 255) {
            throw new \TypeError("Value must be a valid byte (0-255)");
        }
        $this->data[] = $value;
        return $this;
    }

    /**
     * Write U16 value into the buffer.
     * @param int $value Value to write
     * @return self Self for possible chaining
     * @throws \TypeError If value is not a valid U16
     */
    public function write16(int $value): self
    {
        if ($value < 0 || $value > 65535) {
            throw new \TypeError("Value must be a valid U16 (0-65535)");
        }
        for ($i = 0; $i < 2; $i++) {
            $this->write8(($value >> ($i * 8)) & 0xFF);
        }
        return $this;
    }

    /**
     * Write U32 value into the buffer.
     * @param int $value Value to write
     * @return self Self for possible chaining
     * @throws \TypeError If value is not a valid U32
     */
    public function write32(int $value): self
    {
        if ($value < 0 || $value > 4294967295) {
            throw new \TypeError("Value must be a valid U32 (0-4294967295)");
        }
        for ($i = 0; $i < 4; $i++) {
            $this->write8(($value >> ($i * 8)) & 0xFF);
        }
        return $this;
    }

    /**
     * Write U64 value into the buffer.
     * @param string $value Value to write as string due to size limitations
     * @return self Self for possible chaining
     * @throws \TypeError If value is not a valid U64
     */
    public function write64(string $value): self
    {
        if (bccomp($value, '0') < 0 || bccomp($value, '18446744073709551615') > 0) {
            throw new \TypeError("Value must be a valid U64 (0-18446744073709551615)");
        }
        for ($i = 0; $i < 8; $i++) {
            $byte = bcmod($value, '256');
            $this->write8((int)$byte);
            $value = bcdiv($value, '256');
        }
        return $this;
    }

    /**
     * Write U128 value into the buffer.
     * @param string $value Value to write as string due to size limitations
     * @return self Self for possible chaining
     * @throws \TypeError If value is not a valid U128
     */
    public function write128(string $value): self
    {
        if (bccomp($value, '0') < 0 || bccomp($value, '340282366920938463463374607431768211455') > 0) {
            throw new \TypeError("Value must be a valid U128 (0-340282366920938463463374607431768211455)");
        }
        for ($i = 0; $i < 16; $i++) {
            $byte = bcmod($value, '256');
            $this->write8((int)$byte);
            $value = bcdiv($value, '256');
        }
        return $this;
    }

    /**
     * Write U256 value into the buffer.
     * @param string $value Value to write as string due to size limitations
     * @return self Self for possible chaining
     * @throws \TypeError If value is not a valid U256
     */
    public function write256(string $value): self
    {
        if (bccomp($value, '0') < 0 || bccomp($value, '115792089237316195423570985008687907853269984665640564039457584007913129639935') > 0) { // @phpcs:ignore
            throw new \TypeError("Value must be a valid U256 (0-115792089237316195423570985008687907853269984665640564039457584007913129639935)"); // @phpcs:ignore
        }
        for ($i = 0; $i < 32; $i++) {
            $byte = bcmod($value, '256');
            $this->write8((int)$byte);
            $value = bcdiv($value, '256');
        }
        return $this;
    }

    /**
     * Write bytes into the buffer.
     * @param array<int> $bytes Array of bytes to write
     * @return self Self for possible chaining
     * @throws \TypeError If any value is not a valid byte
     */
    public function writeBytes(array $bytes): self
    {
        foreach ($bytes as $byte) {
            $this->write8($byte);
        }
        return $this;
    }

    /**
     * Write ULEB value - an integer of varying size. Used for enum indexes and
     * vector lengths.
     * @param int $value Value to write
     * @return self Self for possible chaining
     * @throws \TypeError If value is negative
     */
    public function writeULEB(int $value): self
    {
        if ($value < 0) {
            throw new \TypeError("ULEB value cannot be negative");
        }

        do {
            $byte = $value & 0x7f;
            $value >>= 7;
            if (0 !== $value) {
                $byte |= 0x80;
            }
            $this->write8($byte);
        } while (0 !== $value);

        return $this;
    }

    /**
     * Write a vector into the buffer: first write the length of the vector as ULEB,
     * then call the callback `cb` X times where X is the length of the vector.
     * @param array<mixed> $vector Array of values to write
     * @param callable $cb Callback to process elements of vector
     * @return self Self for possible chaining
     */
    public function writeVec(array $vector, callable $cb): self
    {
        $this->writeULEB(count($vector));
        foreach ($vector as $i => $item) {
            $cb($this, $item, $i, count($vector));
        }
        return $this;
    }

    /**
     * Get the buffer as a hex string.
     * @param bool $withPrefix Whether to include '0x' prefix
     * @return string Hex string representation of the buffer
     */
    public function toHex(bool $withPrefix = false): string
    {
        $hex = implode(array_map(fn($b) => str_pad(dechex($b), 2, '0', STR_PAD_LEFT), $this->data));
        return $withPrefix ? '0x' . $hex : $hex;
    }

    /**
     * Get the buffer as a byte array.
     * @return array<int> Array of bytes
     */
    public function toBytes(): array
    {
        return $this->data;
    }

    /**
     * Represent data as 'hex' or 'base64'.
     *
     * @param string $encoding Encoding to use: 'base64' or 'hex'
     * @return string Encoded string
     * @throws \InvalidArgumentException If unsupported encoding is provided
     */
    public function toString(string $encoding = 'hex'): string
    {
        return \Sui\Utils::encodeStr($this->toBytes(), $encoding);
    }
}
