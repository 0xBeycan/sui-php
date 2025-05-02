<?php

declare(strict_types=1);

namespace Sui\Transactions\ObjectCache;

abstract class AsyncCache
{
    /**
     * Get a cache entry
     * @param string $type The type of cache entry
     * @param string $key The key to get
     * @return mixed|null
     */
    abstract protected function get(string $type, string $key): mixed;

    /**
     * Set a cache entry
     * @param string $type The type of cache entry
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @return void
     */
    abstract protected function set(string $type, string $key, mixed $value): void;

    /**
     * Delete a cache entry
     * @param string $type The type of cache entry
     * @param string $key The key to delete
     * @return void
     */
    abstract protected function delete(string $type, string $key): void;

    /**
     * Clear the cache
     * @param string|null $type The type of cache to clear, or null to clear all
     * @return void
     */
    abstract public function clear(?string $type = null): void;

    /**
     * Get an object from cache
     * @param string $id The object ID
     * @return ObjectCacheEntry|null
     */
    public function getObject(string $id): ?ObjectCacheEntry
    {
        $owned = $this->get('OwnedObject', $id);
        $shared = $this->get('SharedOrImmutableObject', $id);

        return $owned ?? $shared ?? null;
    }

    /**
     * Get multiple objects from cache
     * @param array<string> $ids The object IDs
     * @return array<ObjectCacheEntry|null>
     */
    public function getObjects(array $ids): array
    {
        return array_map(fn(string $id) => $this->getObject($id), $ids);
    }

    /**
     * Add an object to cache
     * @param ObjectCacheEntry $object The object to add
     * @return ObjectCacheEntry
     */
    public function addObject(ObjectCacheEntry $object): ObjectCacheEntry
    {
        if ($object->getOwner()) {
            $this->set('OwnedObject', $object->getObjectId(), $object);
        } else {
            $this->set('SharedOrImmutableObject', $object->getObjectId(), $object);
        }

        return $object;
    }

    /**
     * Add multiple objects to cache
     * @param array<ObjectCacheEntry> $objects The objects to add
     * @return void
     */
    public function addObjects(array $objects): void
    {
        foreach ($objects as $object) {
            $this->addObject($object);
        }
    }

    /**
     * Delete an object from cache
     * @param string $id The object ID
     * @return void
     */
    public function deleteObject(string $id): void
    {
        $this->delete('OwnedObject', $id);
        $this->delete('SharedOrImmutableObject', $id);
    }

    /**
     * Delete multiple objects from cache
     * @param array<string> $ids The object IDs
     * @return void
     */
    public function deleteObjects(array $ids): void
    {
        foreach ($ids as $id) {
            $this->deleteObject($id);
        }
    }

    /**
     * Get a move function definition from cache
     * @param array{package: string, module: string, function: string} $ref The function reference
     * @return MoveFunctionCacheEntry|null
     */
    public function getMoveFunctionDefinition(array $ref): ?MoveFunctionCacheEntry
    {
        $functionName = sprintf('%s::%s::%s', $ref['package'], $ref['module'], $ref['function']);
        return $this->get('MoveFunction', $functionName);
    }

    /**
     * Add a move function definition to cache
     * @param MoveFunctionCacheEntry $functionEntry The function entry to add
     * @return MoveFunctionCacheEntry
     */
    public function addMoveFunctionDefinition(MoveFunctionCacheEntry $functionEntry): MoveFunctionCacheEntry
    {
        $functionName = sprintf(
            '%s::%s::%s',
            $functionEntry->getPackage(),
            $functionEntry->getModule(),
            $functionEntry->getFunction()
        );

        $this->set('MoveFunction', $functionName, $functionEntry);
        return $functionEntry;
    }

    /**
     * Delete a move function definition from cache
     * @param array{package: string, module: string, function: string} $ref The function reference
     * @return void
     */
    public function deleteMoveFunctionDefinition(array $ref): void
    {
        $functionName = sprintf('%s::%s::%s', $ref['package'], $ref['module'], $ref['function']);
        $this->delete('MoveFunction', $functionName);
    }

    /**
     * Get a custom value from cache
     * @param string $key The key to get
     * @return mixed
     */
    public function getCustom(string $key): mixed
    {
        return $this->get('Custom', $key);
    }

    /**
     * Set a custom value in cache
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @return void
     */
    public function setCustom(string $key, mixed $value): void
    {
        $this->set('Custom', $key, $value);
    }

    /**
     * Delete a custom value from cache
     * @param string $key The key to delete
     * @return void
     */
    public function deleteCustom(string $key): void
    {
        $this->delete('Custom', $key);
    }
}
