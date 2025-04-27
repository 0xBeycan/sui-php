<?php

declare(strict_types=1);

namespace Sui\Paginated;

use Sui\Type\Transaction;

class PaginatedTransactions extends PaginatedBase
{
    /**
     * @var array<Transaction>
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
            static fn(array $item) => new Transaction($item),
            $data['data'] ?? []
        );
    }
}
