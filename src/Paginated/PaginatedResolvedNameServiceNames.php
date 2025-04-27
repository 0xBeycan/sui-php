<?php

declare(strict_types=1);

namespace Sui\Paginated;

class PaginatedResolvedNameServiceNames extends PaginatedBase
{
    /**
     * @var array<string>
     */
    public array $data;

    /**
     * @param PaginatedBase &$instance
     * @param array<mixed> $data
     * @return void
     */
    public static function prepare(PaginatedBase &$instance, array $data): void
    {
        $instance->data = $data;
    }
}
