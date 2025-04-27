<?php

declare(strict_types=1);

namespace Sui\Paginated;

use Sui\Type\CoinStruct;

class PaginatedCoins extends PaginatedBase
{
    /**
     * @var array<CoinStruct>
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
            static fn(array $item) => new CoinStruct($item),
            $data
        );
    }
}
