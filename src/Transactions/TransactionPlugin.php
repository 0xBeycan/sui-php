<?php

declare(strict_types=1);

namespace Sui\Transactions;

/**
 * Base class for transaction plugins
 */
class TransactionPlugin
{
    public TransactionData $transactionData;

    public BuildTransactionOptions $options;

    public \Closure $next;

    /**
     * Constructor for TransactionPlugin
     *
     * @param TransactionData $transactionData The transaction data to process
     * @param BuildTransactionOptions $options The build options
     * @param \Closure $next The next plugin in the chain
     */
    public function __construct(TransactionData $transactionData, BuildTransactionOptions $options, \Closure $next)
    {
        $this->transactionData = $transactionData;
        $this->options = $options;
        $this->next = $next;
    }
}
