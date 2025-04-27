<?php

declare(strict_types=1);

namespace Sui\Paginated;

use Sui\Type\Checkpoint;

class PaginatedCheckpoints extends PaginatedBase
{
    /**
     * @var array<Checkpoint>
     */
    public array $data;

    /**
     * @param PaginatedBase &$instance
     * @param array<mixed> $data
     * @return void
     */
    public static function prepare(PaginatedBase &$instance, array $data): void
    {
        $instance->data = array_map(
            fn(array $item) => new Checkpoint($item),
            $data
        );
    }
}
