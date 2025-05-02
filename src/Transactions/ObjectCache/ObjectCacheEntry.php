<?php

declare(strict_types=1);

namespace Sui\Transactions\ObjectCache;

interface ObjectCacheEntry
{
    /**
     * Get the object ID
     * @return string
     */
    public function getObjectId(): string;

    /**
     * Get the version
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get the digest
     * @return string
     */
    public function getDigest(): string;

    /**
     * Get the owner
     * @return string|null
     */
    public function getOwner(): ?string;

    /**
     * Get the initial shared version
     * @return string|null
     */
    public function getInitialSharedVersion(): ?string;
}
