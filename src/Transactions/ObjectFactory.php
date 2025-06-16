<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\CallArg;

class ObjectFactory
{
    private \Closure $makeObject;

    /**
     * Create an object function that can be used to handle object inputs
     * @param \Closure $makeObject The function to create the object value
     * @return ObjectFactory function that can create object values
     */
    public static function create(\Closure $makeObject): ObjectFactory
    {
        $object = new ObjectFactory();
        $object->makeObject = $makeObject;
        return $object;
    }

    /**
     * @param mixed $value
     * @return \Closure|Argument
     */
    private function makeObject(mixed $value): \Closure|Argument
    {
        return ($this->makeObject)($value);
    }

    /**
     * Create an object value
     * @param mixed $value The value to create
     * @return \Closure|Argument The object value
     */
    public function object(mixed $value): \Closure|Argument
    {
        return $this->makeObject($value);
    }

    /**
     * @return \Closure|Argument
     */
    public function system(): \Closure|Argument
    {
        return $this->object('0x5');
    }

    /**
     * @return \Closure|Argument
     */
    public function clock(): \Closure|Argument
    {
        return $this->object('0x6');
    }

    /**
     * @return \Closure|Argument
     */
    public function random(): \Closure|Argument
    {
        return $this->object('0x8');
    }

    /**
     * @return \Closure|Argument
     */
    public function denyList(): \Closure|Argument
    {
        return $this->object('0x403');
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return \Closure
     */
    public function option(string $type, mixed $value): \Closure
    {
        return function (Transaction $transaction) use ($type, $value): Argument {
            return $transaction->moveCall([
                'typeArguments' => [$type],
                'target' => sprintf('0x1::option::%s', null === $value ? 'none' : 'some'),
                'arguments' => null === $value ? [] : [$transaction->object($value)],
            ]);
        };
    }
}
