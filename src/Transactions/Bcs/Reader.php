<?php

declare(strict_types=1);

namespace Sui\Transactions\Bcs;

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
     * @param string $data Data to use as a buffer (hex string)
     */
    public function __construct(string $data)
    {
        $this->data = $data;
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
        $value = hexdec(substr($this->data, $this->bytePosition * 2, 4));
        $this->shift(2);
        return (int) $value;
    }

    /**
     * Read U32 value from the buffer and shift cursor by 4.
     * @return int
     */
    public function read32(): int
    {
        $value = hexdec(substr($this->data, $this->bytePosition * 2, 8));
        $this->shift(4);
        return (int) $value;
    }

    /**
     * Read U64 value from the buffer and shift cursor by 8.
     * @return string
     */
    public function read64(): string
    {
        $value1 = $this->read32();
        $value2 = $this->read32();

        $result = dechex($value2) . str_pad(dechex($value1), 8, '0', STR_PAD_LEFT);
        return $this->hexToDec($result);
    }

    /**
     * Read U128 value from the buffer and shift cursor by 16.
     * @return string
     */
    public function read128(): string
    {
        $value1 = $this->read64();
        $value2 = $this->read64();
        $result = $this->decToHex($value2) . str_pad($this->decToHex($value1), 16, '0', STR_PAD_LEFT);

        return $this->hexToDec($result);
    }

    /**
     * Read U256 value from the buffer and shift cursor by 32.
     * @return string
     */
    public function read256(): string
    {
        $value1 = $this->read128();
        $value2 = $this->read128();
        $result = $this->decToHex($value2) . str_pad($this->decToHex($value1), 32, '0', STR_PAD_LEFT);

        return $this->hexToDec($result);
    }

    /**
     * Read `num` number of bytes from the buffer and shift cursor by `num`.
     * @param int $num Number of bytes to read
     * @return string
     */
    public function readBytes(int $num): string
    {
        $value = substr($this->data, $this->bytePosition * 2, $num * 2);
        $this->shift($num);
        return $value;
    }

    /**
     * Read ULEB value - an integer of varying size. Used for enum indexes and
     * vector lengths.
     * @return int
     */
    public function readULEB(): int
    {
        $bytes = array_values(unpack('C*', substr($this->data, $this->bytePosition * 2)) ?: []);
        $result = \Sui\Utils::ulebDecode($bytes);
        $this->shift($result['length']);
        return $result['value'];
    }

    /**
     * Read a BCS vector: read a length and then apply function `cb` X times
     * where X is the length of the vector, defined as ULEB in BCS bytes.
     * @param callable $cb Callback to process elements of vector
     * @return array<mixed> Array of the resulting values, returned by callback
     */
    public function readVec(callable $cb): array
    {
        $length = $this->readULEB();
        $result = [];

        for ($i = 0; $i < $length; $i++) {
            $result[] = $cb($this, $i, $length);
        }

        return $result;
    }

    /**
     * Convert hexadecimal string to decimal string using bcmath
     * @param string $hex Hexadecimal string
     * @return string Decimal string
     */
    private function hexToDec(string $hex): string
    {
        $dec = '0';
        $len = strlen($hex);
        for ($i = 0; $i < $len; $i++) {
            $dec = bcmul($dec, '16');
            $dec = bcadd($dec, strval(hexdec($hex[$i])));
        }
        return $dec;
    }

    /**
     * Convert decimal string to hexadecimal string using bcmath
     * @param string $dec Decimal string
     * @return string Hexadecimal string
     */
    private function decToHex(string $dec): string
    {
        $hex = '';
        $zero = '0';
        while (bccomp($dec, $zero) > 0) {
            $rem = bcmod($dec, '16');
            $hex = dechex(intval($rem)) . $hex;
            $dec = bcdiv($dec, '16', 0);
        }
        return $hex ?: '0';
    }
}
