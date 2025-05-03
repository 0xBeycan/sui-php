<?php

declare(strict_types=1);

namespace Sui\Transactions;

/**
 * @template T
 */
class Set
{
    /**
     * @var array<T>
     */
    private array $items = [];

    /**
     * @param array<T>|Set<T> $items
     */
    public function __construct(array|Set $items = [])
    {
        if ($items instanceof Set) {
            $this->items = $items->all();
        } else {
            $this->items = $items;
        }
    }

    /**
     * @param T $item
     * @return void
     */
    // phpcs:ignore
    public function add(mixed $item): void
    {
        if (!in_array($item, $this->items, true)) {
            $this->items[] = $item;
        }
    }

    /**
     * @param T $item
     * @return bool
     */
    // phpcs:ignore
    public function has(mixed $item): bool
    {
        return in_array($item, $this->items, true);
    }

    /**
     * @return array<T>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return 0 === count($this->items);
    }

    /**
     * @return array<T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @return Set<T>
     */
    public function clone(): Set
    {
        return new Set($this->items);
    }
}
