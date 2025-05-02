<?php

declare(strict_types=1);

namespace Sui\Transactions;

class Transaction
{
    public TransactionData $data;

    /**
     * Undocumented function
     */
    public function __construct()
    {
    }

    /**
     * Build the transaction
     *
     * @param array<mixed> $options
     * @return array<mixed>
     */
    public function build(array $options = []): array
    {
        return [];
    }
}
