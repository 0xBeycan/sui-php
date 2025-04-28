<?php

declare(strict_types=1);

namespace Sui\Paginated;

abstract class PaginatedBase
{
    /**
     * @var array<mixed>
     */
    public array $data;

    public bool $hasNextPage;

    /**
     * @var mixed null|string|array
     */
    public mixed $nextCursor;

    /**
     * @param PaginatedBase &$instance
     * @param array<mixed> $data
     * @return void
     */
    abstract public static function prepare(PaginatedBase &$instance, array $data): void;

    /**
     * @param array<mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        // @phpstan-ignore-next-line
        $instance = new static();

        $instance->nextCursor = $data['nextCursor'] ?? null;
        $instance->hasNextPage = (bool) ($data['hasNextPage'] ?? false);

        static::prepare($instance, $data['data'] ?? []);

        return $instance;
    }
}
