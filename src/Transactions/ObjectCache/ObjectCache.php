<?php

declare(strict_types=1);

namespace Sui\Transactions\ObjectCache;

class ObjectCache
{
    private AsyncCache $cache;
    private ?\Closure $onEffects;

    /**
     * @param AsyncCache $cache The cache implementation to use
     * @param \Closure|null $onEffects Optional callback to handle effects
     */
    public function __construct(AsyncCache $cache = new InMemoryCache(), ?\Closure $onEffects = null)
    {
        $this->cache = $cache;
        $this->onEffects = $onEffects;
    }

    /**
     * Clear the cache
     * @return void
     */
    public function clear(): void
    {
        $this->cache->clear();
    }

    /**
     * Get a move function definition from cache
     * @param array{package: string, module: string, function: string} $ref The function reference
     * @return MoveFunctionCacheEntry|null
     */
    public function getMoveFunctionDefinition(array $ref): ?MoveFunctionCacheEntry
    {
        return $this->cache->getMoveFunctionDefinition($ref);
    }

    /**
     * Get multiple objects from cache
     * @param array<string> $ids The object IDs
     * @return array<ObjectCacheEntry|null>
     */
    public function getObjects(array $ids): array
    {
        return $this->cache->getObjects($ids);
    }

    /**
     * Delete multiple objects from cache
     * @param array<string> $ids The object IDs
     * @return void
     */
    public function deleteObjects(array $ids): void
    {
        $this->cache->deleteObjects($ids);
    }

    /**
     * Clear owned objects from cache
     * @return void
     */
    public function clearOwnedObjects(): void
    {
        $this->cache->clear('OwnedObject');
    }

    /**
     * Clear custom cache
     * @return void
     */
    public function clearCustom(): void
    {
        $this->cache->clear('Custom');
    }

    /**
     * Get a custom value from cache
     * @param string $key The key to get
     * @return mixed
     */
    public function getCustom(string $key): mixed
    {
        return $this->cache->getCustom($key);
    }

    /**
     * Set a custom value in cache
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @return void
     */
    public function setCustom(string $key, mixed $value): void
    {
        $this->cache->setCustom($key, $value);
    }

    /**
     * Delete a custom value from cache
     * @param string $key The key to delete
     * @return void
     */
    public function deleteCustom(string $key): void
    {
        $this->cache->deleteCustom($key);
    }

    /**
     * Apply transaction effects to the cache
     * @param array<string, mixed> $effects The transaction effects
     * @return void
     */
    public function applyEffects(array $effects): void
    {
        if (!isset($effects['V2'])) {
            throw new \RuntimeException(
                sprintf('Unsupported transaction effects version %s', $effects['$kind'] ?? 'unknown')
            );
        }

        $lamportVersion = $effects['V2']['lamportVersion'];
        $changedObjects = $effects['V2']['changedObjects'];

        $deletedIds = [];
        $addedObjects = [];

        foreach ($changedObjects as [$id, $change]) {
            if (isset($change['outputState']['NotExist'])) {
                $deletedIds[] = $id;
            } elseif (isset($change['outputState']['ObjectWrite'])) {
                [$digest, $owner] = $change['outputState']['ObjectWrite'];

                $addedObjects[] = new class ($id, $digest, $lamportVersion, $owner) implements ObjectCacheEntry {
                    private string $objectId;
                    private string $digest;
                    private string $version;
                    private ?string $owner;
                    private ?string $initialSharedVersion;

                    /**
                     * @param string $objectId
                     * @param string $digest
                     * @param string $version
                     * @param array<string, mixed> $owner
                     */
                    public function __construct(
                        string $objectId,
                        string $digest,
                        string $version,
                        array $owner
                    ) {
                        $this->objectId = $objectId;
                        $this->digest = $digest;
                        $this->version = $version;
                        $this->owner = $owner['AddressOwner'] ?? $owner['ObjectOwner'] ?? null;
                        $this->initialSharedVersion = $owner['Shared']['initialSharedVersion'] ?? null;
                    }

                    /**
                     * Get the object ID
                     * @return string
                     */
                    public function getObjectId(): string
                    {
                        return $this->objectId;
                    }

                    /**
                     * Get the version
                     * @return string
                     */
                    public function getVersion(): string
                    {
                        return $this->version;
                    }

                    /**
                     * Get the digest
                     * @return string
                     */
                    public function getDigest(): string
                    {
                        return $this->digest;
                    }

                    /**
                     * Get the owner
                     * @return string|null
                     */
                    public function getOwner(): ?string
                    {
                        return $this->owner;
                    }

                    /**
                     * Get the initial shared version
                     * @return string|null
                     */
                    public function getInitialSharedVersion(): ?string
                    {
                        return $this->initialSharedVersion;
                    }
                };
            }
        }

        $this->cache->addObjects($addedObjects);
        $this->cache->deleteObjects($deletedIds);

        if ($this->onEffects) {
            ($this->onEffects)($effects);
        }
    }
}
