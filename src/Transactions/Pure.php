<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Bcs\Bcs;
use Sui\Bcs\Map;
use Sui\Bcs\Serializer;
use Sui\Bcs\Serialized;

class Pure
{
    private \Closure $makePure;

    /**
     * @param \Closure $makePure The closure that will be used to create pure values
     */
    public function __construct(\Closure $makePure)
    {
        $this->makePure = $makePure;
    }

    /**
     * @param string|Serialized $typeOrSerializedValue The type name or serialized value
     * @param mixed $value The value to serialize if type name is provided
     * @return mixed The result of applying makePure to the serialized value
     * @disregard
     */
    public function pure(string|Serialized $typeOrSerializedValue, mixed $value = null): mixed
    {
        if (is_string($typeOrSerializedValue)) {
            return ($this->makePure)(Serializer::pureBcsSchemaFromTypeName($typeOrSerializedValue)->serialize($value));
        }

        if ($typeOrSerializedValue instanceof Serialized) {
            return ($this->makePure)($typeOrSerializedValue);
        }
    }

    /**
     * @param int $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function u8(int $value): mixed
    {
        return ($this->makePure)(Bcs::u8()->serialize($value));
    }

    /**
     * @param int $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function u16(int $value): mixed
    {
        return ($this->makePure)(Bcs::u16()->serialize($value));
    }

    /**
     * @param int $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function u32(int $value): mixed
    {
        return ($this->makePure)(Bcs::u32()->serialize($value));
    }

    /**
     * @param int|string $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function u64(int|string $value): mixed
    {
        return ($this->makePure)(Bcs::u64()->serialize($value));
    }

    /**
     * @param int|string $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function u128(int|string $value): mixed
    {
        return ($this->makePure)(Bcs::u128()->serialize($value));
    }

    /**
     * @param int|string $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function u256(int|string $value): mixed
    {
        return ($this->makePure)(Bcs::u256()->serialize($value));
    }

    /**
     * @param bool $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function bool(bool $value): mixed
    {
        return ($this->makePure)(Bcs::bool()->serialize($value));
    }

    /**
     * @param string $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function string(string $value): mixed
    {
        return ($this->makePure)(Bcs::string()->serialize($value));
    }

    /**
     * @param string $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function address(string $value): mixed
    {
        return ($this->makePure)(Map::address()->serialize($value));
    }

    /**
     * @param string $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function id(string $value): mixed
    {
        return $this->address($value);
    }

    /**
     * @param string $type The type of elements in the vector
     * @param array<mixed> $value The array of values to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function vector(string $type, array $value): mixed
    {
        return ($this->makePure)(Bcs::vector(Serializer::pureBcsSchemaFromTypeName($type))->serialize($value));
    }

    /**
     * @param string $type The type of the optional value
     * @param mixed $value The value to serialize
     * @return mixed The result of applying makePure to the serialized value
     */
    public function option(string $type, mixed $value): mixed
    {
        return ($this->makePure)(Bcs::option(Serializer::pureBcsSchemaFromTypeName($type))->serialize($value));
    }
}
