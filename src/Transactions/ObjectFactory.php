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
     * @param string|CallArg|Argument $value
     * @return Argument
     */
    private function makeObject(string|CallArg|Argument $value): Argument
    {
        return ($this->makeObject)($value);
    }

    /**
     * Create an object value
     * @param string|CallArg|Argument $value The value to create
     * @return Argument The object value
     */
    public function object(string|CallArg|Argument $value): Argument
    {
        return $this->makeObject($value);
    }

    /**
     * @return Argument
     */
    public function system(): Argument
    {
        return $this->object('0x5');
    }

    /**
     * @return Argument
     */
    public function clock(): Argument
    {
        return $this->object('0x6');
    }

    /**
     * @return Argument
     */
    public function random(): Argument
    {
        return $this->object('0x8');
    }

    /**
     * @return Argument
     */
    public function denyList(): Argument
    {
        return $this->object('0x403');
    }
}
