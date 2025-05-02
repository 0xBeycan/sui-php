<?php

declare(strict_types=1);

namespace Sui\Transactions\ObjectCache;

class InMemoryCache extends AsyncCache
{
    /** @var array<string, array<string, mixed>> */
    private array $caches = [
        'OwnedObject' => [],
        'SharedOrImmutableObject' => [],
        'MoveFunction' => [],
        'Custom' => [],
    ];

    /**
     * Get a cache entry
     * @param string $type The type of cache entry
     * @param string $key The key to get
     * @return mixed|null
     */
    protected function get(string $type, string $key): mixed
    {
        return $this->caches[$type][$key] ?? null;
    }

    /**
     * Set a cache entry
     * @param string $type The type of cache entry
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @return void
     */
    protected function set(string $type, string $key, mixed $value): void
    {
        $this->caches[$type][$key] = $value;
    }

    /**
     * Delete a cache entry
     * @param string $type The type of cache entry
     * @param string $key The key to delete
     * @return void
     */
    protected function delete(string $type, string $key): void
    {
        unset($this->caches[$type][$key]);
    }

    /**
     * Clear the cache
     * @param string|null $type The type of cache to clear, or null to clear all
     * @return void
     */
    public function clear(?string $type = null): void
    {
        if ($type) {
            $this->caches[$type] = [];
        } else {
            foreach ($this->caches as $type => $_s) {
                $this->caches[$type] = [];
            }
        }
    }
}
