<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Bcs\Bcs;
use Sui\Bcs\Map;
use Sui\Bcs\Serialized;
use Sui\Bcs\Serializer;
use Sui\Transactions\Type\Argument;

class PureFactory
{
    private \Closure $makePure;

    /**
     * Create a pure function that can be used to serialize values
     * @param \Closure $makePure The function to create the pure value
     * @return PureFactory function that can create pure values
     */
    public static function create(\Closure $makePure): PureFactory
    {
        $pure = new PureFactory();
        $pure->makePure = $makePure;
        return $pure;
    }

    /**
     * @param mixed $value
     * @return Argument
     */
    public function makePure(mixed $value): Argument
    {
        return ($this->makePure)($value);
    }

    /**
     * Create a pure value
     * @param string|Serialized|array<mixed> $typeOrSerializedValue The type or serialized value to create
     * @param mixed $value The value to create
     * @return Argument The serialized value
     */
    public function pure(
        string|Serialized|array $typeOrSerializedValue = null,
        mixed $value = null,
    ): Argument {
        if (is_string($typeOrSerializedValue)) {
            return $this->makePure(Serializer::pureBcsSchemaFromTypeName($typeOrSerializedValue)->serialize($value));
        }

        if ($typeOrSerializedValue instanceof Serialized || is_array($typeOrSerializedValue)) {
            return $this->makePure($typeOrSerializedValue);
        }

        throw new \Exception('tx.pure must be called either a bcs type name, or a serialized bcs value');
    }

    /**
     * @param int $value
     * @return Argument
     */
    public function u8(int $value): Argument
    {
        return $this->makePure(Bcs::u8()->serialize($value));
    }

    /**
     * @param int $value
     * @return Argument
     */
    public function u16(int $value): Argument
    {
        return $this->makePure(Bcs::u16()->serialize($value));
    }

    /**
     * @param int $value
     * @return Argument
     */
    public function u32(int $value): Argument
    {
        return $this->makePure(Bcs::u32()->serialize($value));
    }

    /**
     * @param int|string $value
     * @return Argument
     */
    public function u64(int|string $value): Argument
    {
        return $this->makePure(Bcs::u64()->serialize($value));
    }

    /**
     * @param int|string $value
     * @return Argument
     */
    public function u256(int|string $value): Argument
    {
        return $this->makePure(Bcs::u256()->serialize($value));
    }

    /**
     * @param bool $value
     * @return Argument
     */
    public function bool(bool $value): Argument
    {
        return $this->makePure(Bcs::bool()->serialize($value));
    }

    /**
     * @param string $value
     * @return Argument
     */
    public function string(string $value): Argument
    {
        return $this->makePure(Bcs::string()->serialize($value));
    }

    /**
     * @param string $value
     * @return Argument
     */
    public function address(string $value): Argument
    {
        return $this->makePure(Map::address()->serialize($value));
    }

    /**
     * @param string $value
     * @return Argument
     */
    public function id(string $value): Argument
    {
        return $this->address($value);
    }

    /**
     * @param string $type
     * @param array<mixed> $value
     * @return Argument
     */
    public function vector(string $type, array $value): Argument
    {
        return $this->makePure(Bcs::vector(Serializer::pureBcsSchemaFromTypeName($type))->serialize($value));
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return Argument
     */
    public function option(string $type, mixed $value): Argument
    {
        return $this->makePure(Bcs::option(Serializer::pureBcsSchemaFromTypeName($type))->serialize($value));
    }
}
