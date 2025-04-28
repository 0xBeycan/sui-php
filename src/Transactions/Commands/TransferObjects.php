<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Argument;

class TransferObjects extends Command
{
    /**
     * @var array<Argument>
     */
    private array $objects;

    /**
     * @var Argument
     */
    private Argument $address;

    /**
     * @param array<Argument> $objects
     * @param Argument $address
     */
    public function __construct(array $objects, Argument $address)
    {
        $this->objects = $objects;
        $this->address = $address;
    }

    /**
     * @return array<Argument>
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    /**
     * @param array<Argument> $objects
     * @return self
     */
    public function setObjects(array $objects): self
    {
        $this->objects = $objects;
        return $this;
    }

    /**
     * @return Argument
     */
    public function getAddress(): Argument
    {
        return $this->address;
    }

    /**
     * @param Argument $address
     * @return self
     */
    public function setAddress(Argument $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'objects' => array_map(fn(Argument $object) => $object->toArray(), $this->objects),
            'address' => $this->address->toArray(),
        ];
    }
}
