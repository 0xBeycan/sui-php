<?php

declare(strict_types=1);

namespace Sui\Paginated;

use Sui\Type\CoinStruct;

class PaginatedCoins extends PaginatedBase
{
    /**
     * @param PaginatedBase &$instance
     * @param array<mixed> $data
     * @return void
     */
    public static function prepare(PaginatedBase &$instance, array $data): void
    {
        $instance->data = array_map(
            static fn(array $item) => new CoinStruct($item),
            $data['data'] ?? []
        );
    }
}
