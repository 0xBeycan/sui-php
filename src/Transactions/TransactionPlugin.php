<?php

declare(strict_types=1);

namespace Sui\Transactions;

interface TransactionPlugin
{
    /**
     * Process the transaction data with the given options and proceed to the next step.
     *
     * @param TransactionDataBuilder $transactionData
     * @param BuildTransactionOptions $options
     * @param \Closure $next
     * @return void
     */
    public function run(
        TransactionDataBuilder $transactionData,
        BuildTransactionOptions $options,
        \Closure $next
    ): void;
}
