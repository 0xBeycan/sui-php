<?php

declare(strict_types=1);

namespace Sui\Transactions;

class Transaction
{
    public TransactionData $data;

    /**
     * @param array<mixed> $options
     * @return array<int>
     */
    public function build(array $options): array
    {
        return [];
    }
}
