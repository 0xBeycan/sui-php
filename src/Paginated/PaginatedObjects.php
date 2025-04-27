<?php

declare(strict_types=1);

namespace Sui\Paginated;

use Sui\Type\SuiObjetData;

class PaginatedObjects extends PaginatedBase
{
    /**
     * @param PaginatedBase &$instance
     * @param array<mixed> $data
     * @return void
     */
    public static function prepare(PaginatedBase &$instance, array $data): void
    {
        $instance->data = array_map(
            static fn(array $item) => new SuiObjetData($item['data']),
            $data['data'] ?? []
        );
    }
}
