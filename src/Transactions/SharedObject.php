<?php

declare(strict_types=1);

namespace Sui\Transactions;

class SharedObject
{
    private string $objectId;

    private string $initialSharedVersion;

    private bool $mutable;

    /**
     * @param string $objectId
     * @param string $initialSharedVersion
     * @param bool $mutable
     */
    public function __construct(string $objectId, string $initialSharedVersion, bool $mutable)
    {
        $this->objectId = $objectId;
        $this->initialSharedVersion = $initialSharedVersion;
        $this->mutable = $mutable;
    }

    /**
     * @return string
     */
    public function getObjectId(): string
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getInitialSharedVersion(): string
    {
        return $this->initialSharedVersion;
    }

    /**
     * @return bool
     */
    public function isMutable(): bool
    {
        return $this->mutable;
    }

    /**
     * @param string $objectId
     * @return self
     */
    public function setObjectId(string $objectId): self
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * @param string $initialSharedVersion
     * @return self
     */
    public function setInitialSharedVersion(string $initialSharedVersion): self
    {
        $this->initialSharedVersion = $initialSharedVersion;
        return $this;
    }

    /**
     * @param bool $mutable
     * @return self
     */
    public function setMutable(bool $mutable): self
    {
        $this->mutable = $mutable;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'objectId' => $this->objectId,
            'initialSharedVersion' => $this->initialSharedVersion,
            'mutable' => $this->mutable,
        ];
    }
}
