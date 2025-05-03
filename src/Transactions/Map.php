<?php

declare(strict_types=1);

namespace Sui\Transactions;

/**
 * Map class that provides similar functionality to JavaScript's Map
 * @template K
 * @template V
 */
class Map
{
    /**
     * @var array<K, V>
     */
    private array $data = [];

    /**
     * Create a new Map instance
     * @param array<K, V> $initialData
     */
    public function __construct(array $initialData = [])
    {
        $this->data = $initialData;
    }

    /**
     * Set a key-value pair in the map
     * @param K $key
     * @param V $value
     * @return void
     */
    public function set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get the value associated with a key
     * @param K $key
     * @return V|null
     */
    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    /**
     * Check if a key exists in the map
     * @param K $key
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Delete a key-value pair from the map
     * @param K $key
     * @return bool
     */
    public function delete($key): bool
    {
        if ($this->has($key)) {
            unset($this->data[$key]);
            return true;
        }
        return false;
    }

    /**
     * Clear all entries from the map
     * @return void
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Get the number of entries in the map
     * @return int
     */
    public function size(): int
    {
        return count($this->data);
    }

    /**
     * Get all keys in the map
     * @return array<K>
     */
    public function keys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Get all values in the map
     * @return array<V>
     */
    public function values(): array
    {
        return array_values($this->data);
    }

    /**
     * Get all entries in the map
     * @return array<array{0: K, 1: V}>
     */
    public function entries(): array
    {
        $entries = [];
        foreach ($this->data as $key => $value) {
            $entries[] = [$key, $value];
        }
        return $entries;
    }

    /**
     * Execute a callback for each entry in the map
     * @param callable(V, K): void $callback
     * @return void
     */
    public function forEach(callable $callback): void
    {
        foreach ($this->data as $key => $value) {
            $callback($value, $key);
        }
    }

    /**
     * Convert the map to an array
     * @return array<K, V>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
