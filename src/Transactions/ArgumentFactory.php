<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\CallArg;

class ArgumentFactory
{
    private Transaction $transaction;

    /**
     * @param Transaction $transaction
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * @param Transaction $transaction
     * @return ArgumentFactory
     */
    public static function create(Transaction $transaction): ArgumentFactory
    {
        return new ArgumentFactory($transaction);
    }

    /**
     * @param mixed $value
     * @return Argument
     */
    public function pure(mixed $value): Argument
    {
        return PureFactory::create(function (mixed $value): Argument {
            return $this->transaction->pure($value);
        })->pure($value);
    }

    /**
     * @param mixed $value
     * @return Argument
     */
    public function object(mixed $value): Argument
    {
        return ObjectFactory::create(function (mixed $value): Argument {
            return $this->transaction->object($value);
        })->object($value);
    }

    /**
     * @param string $objectId
     * @param bool $mutable
     * @param int|string $initialSharedVersion
     * @return Argument
     */
    public function sharedObjectRef(string $objectId, bool $mutable, int|string $initialSharedVersion): Argument
    {
        return $this->transaction->sharedObjectRef($objectId, $mutable, $initialSharedVersion);
    }

    /**
     * @param string $objectId
     * @param string $digest
     * @param string $version
     * @return Argument
     */
    public function receivingRef(string $objectId, string $digest, string $version): Argument
    {
        return $this->transaction->receivingRef($objectId, $digest, $version);
    }

    /**
     * @param string $objectId
     * @param string $digest
     * @param string $version
     * @return Argument
     */
    public function objectRef(string $objectId, string $digest, string $version): Argument
    {
        return $this->transaction->objectRef($objectId, $digest, $version);
    }
}
