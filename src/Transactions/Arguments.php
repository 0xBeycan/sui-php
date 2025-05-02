<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Transactions\ObjectCache\ObjectFactory;

class Arguments
{
    /**
     * Creates a Pure argument
     *
     * @param mixed $value The value to create a Pure argument from
     * @return Pure A closure that takes a Transaction and returns an Argument
     */
    public static function pure(mixed $value): Pure
    {
        return new Pure(fn($value) => fn(Transaction $tx) => $tx->pure($value));
    }

    /**
     * Creates an Object argument
     *
     * @param mixed $value The value to create an Object argument from
     * @return mixed A closure that takes a Transaction and returns an Argument
     */
    public static function object(mixed $value): mixed
    {
        return new ObjectFactory(fn($value) => fn(Transaction $tx) => $tx->object($value));
    }

    /**
     * Creates a SharedObjectRef argument
     *
     * @param mixed ...$args The arguments to create a SharedObjectRef argument from
     * @return mixed A closure that takes a Transaction and returns an Argument
     */
    public static function sharedObjectRef(mixed ...$args): mixed
    {
        return fn(Transaction $tx) => $tx->sharedObjectRef(...$args);
    }


    /**
     * Creates an ObjectRef argument
     *
     * @param mixed ...$args The arguments to create an ObjectRef argument from
     * @return mixed A closure that takes a Transaction and returns an Argument
     */
    public static function objectRef(mixed ...$args): mixed
    {
        return fn(Transaction $tx) => $tx->objectRef(...$args);
    }

    /**
     * Creates a ReceivingRef argument
     *
     * @param mixed ...$args The arguments to create a ReceivingRef argument from
     * @return mixed A closure that takes a Transaction and returns an Argument
     */
    public static function receivingRef(mixed ...$args): mixed
    {
        return fn(Transaction $tx) => $tx->receivingRef(...$args);
    }
}
