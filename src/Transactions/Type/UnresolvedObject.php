<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class UnresolvedObject
{
    private string $objectId;

    private ?string $version = null;

    private ?string $digest = null;

    private ?string $initialSharedVersion = null;

    /**
     * @param string $objectId
     * @param string|null $version
     * @param string|null $digest
     * @param string|null $initialSharedVersion
     */
    public function __construct(
        string $objectId,
        ?string $version = null,
        ?string $digest = null,
        ?string $initialSharedVersion = null
    ) {
        $this->objectId = $objectId;
        $this->version = $version;
        $this->digest = $digest;
        $this->initialSharedVersion = $initialSharedVersion;
    }

    /**
     * @return string
     */
    public function getObjectId(): string
    {
        return $this->objectId;
    }

    /**
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getDigest(): ?string
    {
        return $this->digest;
    }

    /**
     * @return string|null
     */
    public function getInitialSharedVersion(): ?string
    {
        return $this->initialSharedVersion;
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
     * @param string|null $version
     * @return self
     */
    public function setVersion(?string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param string|null $digest
     * @return self
     */
    public function setDigest(?string $digest): self
    {
        $this->digest = $digest;
        return $this;
    }

    /**
     * @param string|null $initialSharedVersion
     * @return self
     */
    public function setInitialSharedVersion(?string $initialSharedVersion): self
    {
        $this->initialSharedVersion = $initialSharedVersion;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'objectId' => $this->objectId,
            'version' => $this->version,
            'digest' => $this->digest,
            'initialSharedVersion' => $this->initialSharedVersion,
        ];
    }
}
