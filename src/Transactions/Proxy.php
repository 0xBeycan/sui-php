<?php

declare(strict_types=1);

namespace Sui\Transactions;

/**
 * @implements \IteratorAggregate<int, object>
 */
class Proxy implements \IteratorAggregate
{
    /**
     * @var object
     */
    private object $target;

    /**
     * @var object
     */
    private object $handler;

    /**
     * @param object $target
     * @param object $handler
     */
    public function __construct(object $target, object $handler)
    {
        $this->target = $target;
        $this->handler = $handler;
    }

    /**
     * @return \Iterator<int, object>
     */
    public function getIterator(): \Iterator
    {
        if (method_exists($this->handler, 'get')) {
            $iterator = $this->handler->get($this->target, '__iterator__');
            if (is_callable($iterator)) {
                return $iterator();
            }
        }
        return new \EmptyIterator();
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        if (method_exists($this->handler, 'get')) {
            return $this->handler->get($this->target, $name);
        }
        return $this->target->$name ?? null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        if (method_exists($this->handler, 'set')) {
            $this->handler->set($this->target, $name, $value);
            return;
        }
        $this->target->$name = $value;
    }

    /**
     * @param string $name
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists($this->handler, 'call')) {
            return $this->handler->call($this->target, $name, $arguments);
        }
        // @phpstan-ignore-next-line
        return call_user_func_array([$this->target, $name], $arguments);
    }

    /**
     * @param object $target
     * @param string $property
     * @return mixed
     */
    public static function reflectGet(object $target, string $property): mixed
    {
        return $target->$property ?? null;
    }

    /**
     * @param string $property
     * @return bool
     */
    public static function isSymbol(string $property): bool
    {
        $symbolLikeProperties = ['__iterator__', '__toStringTag', 'constructor', 'prototype'];
        return in_array($property, $symbolLikeProperties, true) || str_starts_with($property, 'Symbol(');
    }
}
