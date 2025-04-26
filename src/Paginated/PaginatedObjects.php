<?php

declare(strict_types=1);

namespace Sui\Paginated;

use Sui\Type\SuiObject;

class PaginatedObjects extends PaginatedBase
{
    /**
     * @param self &$instance
     * @param array<mixed> $data
     * @return void
     */
    public static function prepare(PaginatedBase &$instance, array $data): void
    {
        $instance->data = array_map(
            static fn(array $item) => new SuiObject($item['data']),
            $data['data'] ?? []
        );
    }
}
