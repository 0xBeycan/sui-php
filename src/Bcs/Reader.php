<?php

declare(strict_types=1);

namespace Sui\Bcs;

/**
 * Class used for reading BCS data chunk by chunk. Meant to be used
 * by some wrapper, which will make sure that data is valid and is
 * matching the desired format.
 */
class Reader
{
    private string $data;
    private int $bytePosition = 0;

    /**
     * @param string|array<int> $data Data to use as a buffer (hex string or byte array)
     */
    public function __construct(string|array $data)
    {
        if (is_array($data)) {
            $this->data = implode(array_map(fn($b) => str_pad(dechex($b), 2, '0', STR_PAD_LEFT), $data));
        } else {
            if (str_starts_with($data, '0x')) {
                $data = substr($data, 2);
            }
            $this->data = $data;
        }
    }

    /**
     * Shift current cursor position by `bytes`.
     *
     * @param int $bytes Number of bytes to shift
     * @return self Self for possible chaining
     */
    public function shift(int $bytes): self
    {
        $this->bytePosition += $bytes;
        return $this;
    }

    /**
     * Read U8 value from the buffer and shift cursor by 1.
     * @return int
     */
    public function read8(): int
    {
        if ($this->bytePosition * 2 >= strlen($this->data)) {
            return 0;
        }
        $value = hexdec(substr($this->data, $this->bytePosition * 2, 2));
        $this->shift(1);
        return (int) $value;
    }

    /**
     * Read U16 value from the buffer and shift cursor by 2.
     * @return int
     */
    public function read16(): int
    {
        if ($this->bytePosition * 2 >= strlen($this->data)) {
            return 0;
        }
        $value = 0;
        for ($i = 0; $i < 2; $i++) {
            $value |= $this->read8() << ($i * 8);
        }
        return $value;
    }

    /**
     * Read U32 value from the buffer and shift cursor by 4.
     * @return int
     */
    public function read32(): int
    {
        if ($this->bytePosition * 2 >= strlen($this->data)) {
            return 0;
        }
        $value = 0;
        for ($i = 0; $i < 4; $i++) {
            $value |= $this->read8() << ($i * 8);
        }
        return $value;
    }

    /**
     * Read U64 value from the buffer and shift cursor by 8.
     * @return string
     */
    public function read64(): string
    {
        if ($this->bytePosition * 2 >= strlen($this->data)) {
            return '0';
        }
        $value = '0';
        for ($i = 0; $i < 8; $i++) {
            $byte = $this->read8();
            $value = bcadd($value, bcmul((string)$byte, bcpow('256', (string)$i)));
        }
        return $value;
    }

    /**
     * Read U128 value from the buffer and shift cursor by 16.
     * @return string
     */
    public function read128(): string
    {
        if ($this->bytePosition * 2 >= strlen($this->data)) {
            return '0';
        }
        $value = '0';
        for ($i = 0; $i < 16; $i++) {
            $byte = $this->read8();
            $value = bcadd($value, bcmul((string)$byte, bcpow('256', (string)$i)));
        }
        return $value;
    }

    /**
     * Read U256 value from the buffer and shift cursor by 32.
     * @return string
     */
    public function read256(): string
    {
        if ($this->bytePosition * 2 >= strlen($this->data)) {
            return '0';
        }
        $value = '0';
        for ($i = 0; $i < 32; $i++) {
            $byte = $this->read8();
            $value = bcadd($value, bcmul((string)$byte, bcpow('256', (string)$i)));
        }
        return $value;
    }

    /**
     * Read `num` number of bytes from the buffer and shift cursor by `num`.
     * @param int $num Number of bytes to read
     * @return array<int> Array of the resulting values
     */
    public function readBytes(int $num): array
    {
        if ($this->bytePosition * 2 >= strlen($this->data)) {
            return array_fill(0, $num, 0);
        }
        $bytes = [];
        for ($i = 0; $i < $num; $i++) {
            $bytes[] = $this->read8();
        }
        return $bytes;
    }

    /**
     * Read ULEB value - an integer of varying size. Used for enum indexes and
     * vector lengths.
     * @return int
     */
    public function readULEB(): int
    {
        $value = 0;
        $shift = 0;

        while (true) {
            if ($this->bytePosition * 2 >= strlen($this->data)) {
                break;
            }
            $byte = $this->read8();
            $value |= ($byte & 0x7f) << $shift;
            if (0 === ($byte & 0x80)) {
                break;
            }
            $shift += 7;
        }

        return $value;
    }
}
