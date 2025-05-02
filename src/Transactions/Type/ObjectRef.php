<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class ObjectRef
{
    private string $objectId;

    private string $version;

    private string $digest;

    /**
     * @param string $objectId
     * @param string $version
     * @param string $digest
     */
    public function __construct(string $objectId, string $version, string $digest)
    {
        $this->objectId = $objectId;
        $this->version = $version;
        $this->digest = $digest;
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
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getDigest(): string
    {
        return $this->digest;
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
     * @param string $version
     * @return self
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param string $digest
     * @return self
     */
    public function setDigest(string $digest): self
    {
        $this->digest = $digest;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'objectId' => $this->objectId,
            'version' => $this->version,
            'digest' => $this->digest,
        ];
    }
}
