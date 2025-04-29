<?php

declare(strict_types=1);

namespace Sui\Bcs;

/**
 * Class used to write BCS data into a buffer.
 * Most methods are chainable, so it is possible to write them in one go.
 */
class Writer
{
    /** @var array<int> Array of bytes representing the buffer */
    private array $bytes;
    private int $bytePosition = 0;
    private int $size;
    private int $maxSize;
    private int $allocateSize;

    /**
     * Create a new BCS Writer instance.
     *
     * @param array{initialSize?: int, maxSize?: int, allocateSize?: int} $options Configuration options for the writer
     */
    public function __construct(array $options = [])
    {
        $this->size = $options['initialSize'] ?? 1024;
        $this->maxSize = $options['maxSize'] ?? PHP_INT_MAX;
        $this->allocateSize = $options['allocateSize'] ?? 1024;
        $this->bytes = array_fill(0, $this->size, 0);
    }

    /**
     * Ensures the buffer has enough space for the given number of bytes.
     * If not, grows the buffer according to allocation rules.
     *
     * @param int $bytes Number of bytes to ensure space for
     * @throws \Exception If the required size exceeds maxSize
     * @return void
     */
    private function ensureSizeOrGrow(int $bytes): void
    {
        $requiredSize = $this->bytePosition + $bytes;
        if ($requiredSize > $this->size) {
            $nextSize = min($this->maxSize, $this->size + $this->allocateSize);
            if ($requiredSize > $nextSize) {
                throw new \Exception(
                    "Attempting to serialize to BCS, but buffer does not have enough size. " .
                        "Allocated size: {$this->size}, Max size: {$this->maxSize}, Required size: {$requiredSize}"
                );
            }

            $this->size = $nextSize;
            $this->bytes = array_pad($this->bytes, $this->size, 0);
        }
    }

    /**
     * Shift current cursor position by the given number of bytes.
     *
     * @param int $bytes Number of bytes to shift
     * @return self
     */
    public function shift(int $bytes): self
    {
        $this->bytePosition += $bytes;
        return $this;
    }

    /**
     * Write a U8 value into the buffer and shift cursor position by 1.
     *
     * @param int|string $value Value to write
     * @return self
     */
    public function write8(int|string $value): self
    {
        $this->ensureSizeOrGrow(1);
        $this->bytes[$this->bytePosition] = (int)$value & 0xFF;
        return $this->shift(1);
    }

    /**
     * Write a U16 value into the buffer and shift cursor position by 2.
     *
     * @param int|string $value Value to write
     * @return self
     */
    public function write16(int|string $value): self
    {
        $this->ensureSizeOrGrow(2);
        $value = (int)$value;
        $this->bytes[$this->bytePosition] = $value & 0xFF;
        $this->bytes[$this->bytePosition + 1] = ($value >> 8) & 0xFF;
        return $this->shift(2);
    }

    /**
     * Write a U32 value into the buffer and shift cursor position by 4.
     *
     * @param int|string $value Value to write
     * @return self
     */
    public function write32(int|string $value): self
    {
        $this->ensureSizeOrGrow(4);
        $value = (int)$value;
        $this->bytes[$this->bytePosition] = $value & 0xFF;
        $this->bytes[$this->bytePosition + 1] = ($value >> 8) & 0xFF;
        $this->bytes[$this->bytePosition + 2] = ($value >> 16) & 0xFF;
        $this->bytes[$this->bytePosition + 3] = ($value >> 24) & 0xFF;
        return $this->shift(4);
    }

    /**
     * Write a U64 value into the buffer and shift cursor position by 8.
     *
     * @param int|string $value Value to write
     * @return self
     */
    public function write64(int|string $value): self
    {
        $this->ensureSizeOrGrow(8);
        $value = (string)$value;
        for ($i = 0; $i < 8; $i++) {
            $byte = bcmod($value, '256');
            $this->bytes[$this->bytePosition + $i] = (int)$byte;
            $value = bcdiv($value, '256', 0);
        }
        return $this->shift(8);
    }

    /**
     * Write a U128 value into the buffer and shift cursor position by 16.
     *
     * @param int|string $value Value to write
     * @return self
     */
    public function write128(int|string $value): self
    {
        $this->ensureSizeOrGrow(16);
        $value = (string)$value;
        for ($i = 0; $i < 16; $i++) {
            $byte = bcmod($value, '256');
            $this->bytes[$this->bytePosition + $i] = (int)$byte;
            $value = bcdiv($value, '256', 0);
        }
        return $this->shift(16);
    }

    /**
     * Write a U256 value into the buffer and shift cursor position by 32.
     *
     * @param int|string $value Value to write
     * @return self
     */
    public function write256(int|string $value): self
    {
        $this->ensureSizeOrGrow(32);
        $value = (string)$value;
        for ($i = 0; $i < 32; $i++) {
            $byte = bcmod($value, '256');
            $this->bytes[$this->bytePosition + $i] = (int)$byte;
            $value = bcdiv($value, '256', 0);
        }
        return $this->shift(32);
    }

    /**
     * Write a ULEB value into the buffer and shift cursor position by number of bytes written.
     *
     * @param int $value Value to write
     * @return self
     */
    public function writeULEB(int $value): self
    {
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
     * Write a vector into the buffer by first writing the vector length and then calling
     * a callback on each passed value.
     *
     * @param array<mixed> $vector Array of elements to write
     * @param callable $callback Callback to call on each element of the vector
     * @return self
     */
    public function writeVec(array $vector, callable $callback): self
    {
        $this->writeULEB(count($vector));
        foreach ($vector as $i => $element) {
            $callback($this, $element, $i, count($vector));
        }
        return $this;
    }

    /**
     * Get the underlying buffer taking only value bytes.
     *
     * @return array<int>
     */
    public function toBytes(): array
    {
        return array_slice($this->bytes, 0, $this->bytePosition);
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
