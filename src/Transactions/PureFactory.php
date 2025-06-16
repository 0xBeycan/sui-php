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
     * @return \Closure|Argument
     */
    public function makePure(mixed $value): \Closure|Argument
    {
        return ($this->makePure)($value);
    }

    /**
     * Create a pure value
     * @param string|Serialized|array<mixed> $typeOrSerializedValue The type or serialized value to create
     * @param mixed $value The value to create
     * @return \Closure|Argument The serialized value
     */
    public function pure(
        string|Serialized|array $typeOrSerializedValue = null,
        mixed $value = null,
    ): \Closure|Argument {
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
     * @return \Closure|Argument
     */
    public function u8(int $value): \Closure|Argument
    {
        return $this->makePure(Bcs::u8()->serialize($value));
    }

    /**
     * @param int $value
     * @return \Closure|Argument
     */
    public function u16(int $value): \Closure|Argument
    {
        return $this->makePure(Bcs::u16()->serialize($value));
    }

    /**
     * @param int $value
     * @return \Closure|Argument
     */
    public function u32(int $value): \Closure|Argument
    {
        return $this->makePure(Bcs::u32()->serialize($value));
    }

    /**
     * @param int|string $value
     * @return \Closure|Argument
     */
    public function u64(int|string $value): \Closure|Argument
    {
        return $this->makePure(Bcs::u64()->serialize($value));
    }

    /**
     * @param int|string $value
     * @return \Closure|Argument
     */
    public function u256(int|string $value): \Closure|Argument
    {
        return $this->makePure(Bcs::u256()->serialize($value));
    }

    /**
     * @param bool $value
     * @return \Closure|Argument
     */
    public function bool(bool $value): \Closure|Argument
    {
        return $this->makePure(Bcs::bool()->serialize($value));
    }

    /**
     * @param string $value
     * @return \Closure|Argument
     */
    public function string(string $value): \Closure|Argument
    {
        return $this->makePure(Bcs::string()->serialize($value));
    }

    /**
     * @param string $value
     * @return \Closure|Argument
     */
    public function address(string $value): \Closure|Argument
    {
        return $this->makePure(Map::address()->serialize($value));
    }

    /**
     * @param string $value
     * @return \Closure|Argument
     */
    public function id(string $value): \Closure|Argument
    {
        return $this->address($value);
    }

    /**
     * @param string $type
     * @param array<mixed> $value
     * @return \Closure|Argument
     */
    public function vector(string $type, array $value): \Closure|Argument
    {
        return $this->makePure(Bcs::vector(Serializer::pureBcsSchemaFromTypeName($type))->serialize($value));
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return \Closure|Argument
     */
    public function option(string $type, mixed $value): \Closure|Argument
    {
        return $this->makePure(Bcs::option(Serializer::pureBcsSchemaFromTypeName($type))->serialize($value));
    }
}
