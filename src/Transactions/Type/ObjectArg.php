<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class ObjectArg
{
    private ObjectRef $immOrOwnedObject;

    private SharedObject $sharedObject;

    private ObjectRef $receiving;

    /**
     * @param ObjectRef $immOrOwnedObject
     * @param SharedObject $sharedObject
     * @param ObjectRef $receiving
     */
    public function __construct(ObjectRef $immOrOwnedObject, SharedObject $sharedObject, ObjectRef $receiving)
    {
        $this->immOrOwnedObject = $immOrOwnedObject;
        $this->sharedObject = $sharedObject;
        $this->receiving = $receiving;
    }

    /**
     * @return ObjectRef
     */
    public function getImmOrOwnedObject(): ObjectRef
    {
        return $this->immOrOwnedObject;
    }

    /**
     * @return SharedObject
     */
    public function getSharedObject(): SharedObject
    {
        return $this->sharedObject;
    }

    /**
     * @return ObjectRef
     */
    public function getReceiving(): ObjectRef
    {
        return $this->receiving;
    }

    /**
     * @param ObjectRef $immOrOwnedObject
     * @return self
     */
    public function setImmOrOwnedObject(ObjectRef $immOrOwnedObject): self
    {
        $this->immOrOwnedObject = $immOrOwnedObject;
        return $this;
    }

    /**
     * @param SharedObject $sharedObject
     * @return self
     */
    public function setSharedObject(SharedObject $sharedObject): self
    {
        $this->sharedObject = $sharedObject;
        return $this;
    }

    /**
     * @param ObjectRef $receiving
     * @return self
     */
    public function setReceiving(ObjectRef $receiving): self
    {
        $this->receiving = $receiving;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'immOrOwnedObject' => $this->immOrOwnedObject->toArray(),
            'sharedObject' => $this->sharedObject->toArray(),
            'receiving' => $this->receiving->toArray(),
        ];
    }
}
