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

    public ?string $nextCursor;

    abstract public static function prepare(self &$instance, array $data): void;

    /**
     * @param array<mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new static();

        $instance->nextCursor = $data['nextCursor'] ?? null;
        $instance->hasNextPage = (bool) ($data['hasNextPage'] ?? false);

        static::prepare($instance, $data);

        return $instance;
    }
}
