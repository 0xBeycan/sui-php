<?php

declare(strict_types=1);

namespace Sui\Transactions\ObjectCache;

class ObjectFactory
{
    /**
     * @var callable
     */
    private $makeObject;

    /**
     * @param callable $makeObject
     */
    public function __construct(callable $makeObject)
    {
        $this->makeObject = $makeObject;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function create(mixed $value): mixed
    {
        return ($this->makeObject)($value);
    }

    /**
     * @return mixed
     */
    public function system(): mixed
    {
        return $this->create('0x5');
    }

    /**
     * @return mixed
     */
    public function clock(): mixed
    {
        return $this->create('0x6');
    }

    /**
     * @return mixed
     */
    public function random(): mixed
    {
        return $this->create('0x8');
    }

    /**
     * @return mixed
     */
    public function denyList(): mixed
    {
        return $this->create('0x403');
    }

    /**
     * @param array<string,mixed> $args
     * @return callable
     */
    public function option(array $args): callable
    {
        return function ($tx) use ($args) {
            $type = $args['type'];
            $value = $args['value'];

            return $tx->moveCall([
                'typeArguments' => [$type],
                'target' => '0x1::option::' . (null === $value ? 'none' : 'some'),
                'arguments' => null === $value ? [] : [$tx->object($value)],
            ]);
        };
    }
}
