<?php

declare(strict_types=1);

namespace Sui\Transactions;

class CallArg
{
    private ObjectArg $object;

    private string $pureBytes;

    private mixed $unresolvedPure;

    private UnresolvedObject $unresolvedObject;

    /**
     * @param ObjectArg $object
     * @param string $pureBytes
     * @param mixed $unresolvedPure
     * @param UnresolvedObject $unresolvedObject
     */
    public function __construct(
        ObjectArg $object,
        string $pureBytes,
        mixed $unresolvedPure,
        UnresolvedObject $unresolvedObject
    ) {
        $this->object = $object;
        $this->pureBytes = $pureBytes;
        $this->unresolvedPure = $unresolvedPure;
        $this->unresolvedObject = $unresolvedObject;
    }

    /**
     * @return ObjectArg
     */
    public function getObject(): ObjectArg
    {
        return $this->object;
    }

    /**
     * @param ObjectArg $object
     * @return self
     */
    public function setObject(ObjectArg $object): self
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return string
     */
    public function getPureBytes(): string
    {
        return $this->pureBytes;
    }

    /**
     * @param string $pureBytes
     * @return self
     */
    public function setPureBytes(string $pureBytes): self
    {
        $this->pureBytes = $pureBytes;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnresolvedPure(): mixed
    {
        return $this->unresolvedPure;
    }

    /**
     * @param mixed $unresolvedPure
     * @return self
     */
    public function setUnresolvedPure(mixed $unresolvedPure): self
    {
        $this->unresolvedPure = $unresolvedPure;
        return $this;
    }

    /**
     * @return UnresolvedObject
     */
    public function getUnresolvedObject(): UnresolvedObject
    {
        return $this->unresolvedObject;
    }

    /**
     * @param UnresolvedObject $unresolvedObject
     * @return self
     */
    public function setUnresolvedObject(UnresolvedObject $unresolvedObject): self
    {
        $this->unresolvedObject = $unresolvedObject;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'object' => $this->object->toArray(),
            'pureBytes' => $this->pureBytes,
            'unresolvedPure' => $this->unresolvedPure,
            'unresolvedObject' => $this->unresolvedObject->toArray(),
        ];
    }
}
