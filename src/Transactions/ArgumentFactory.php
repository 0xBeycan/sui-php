<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Transactions\Type\Argument;

class ArgumentFactory
{
    public ObjectFactory $object;

    public PureFactory $pure;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->object = ObjectFactory::create(function (mixed $value): \Closure {
            return function (Transaction $transaction) use ($value): \Closure|Argument {
                return $transaction->object($value);
            };
        });

        $this->pure = PureFactory::create(function (mixed $value): \Closure {
            return function (Transaction $transaction) use ($value): Argument {
                return $transaction->pure($value);
            };
        });
    }

    /**
     * @param string $objectId
     * @param bool $mutable
     * @param int|string $initialSharedVersion
     * @return \Closure
     */
    public function sharedObjectRef(string $objectId, bool $mutable, int|string $initialSharedVersion): \Closure
    {
        return function (Transaction $transaction) use ($objectId, $mutable, $initialSharedVersion): Argument {
            return $transaction->sharedObjectRef($objectId, $mutable, $initialSharedVersion);
        };
    }

    /**
     * @param string $objectId
     * @param string $digest
     * @param string $version
     * @return \Closure
     */
    public function receivingRef(string $objectId, string $digest, string $version): \Closure
    {
        return function (Transaction $transaction) use ($objectId, $digest, $version): Argument {
            return $transaction->receivingRef($objectId, $digest, $version);
        };
    }

    /**
     * @param string $objectId
     * @param string $digest
     * @param string $version
     * @return \Closure
     */
    public function objectRef(string $objectId, string $digest, string $version): \Closure
    {
        return function (Transaction $transaction) use ($objectId, $digest, $version): Argument {
            return $transaction->objectRef($objectId, $digest, $version);
        };
    }
}
