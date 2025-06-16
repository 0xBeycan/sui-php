<?php

declare(strict_types=1);

namespace Sui\Transactions\Plugins;

use Sui\Transactions\BuildTransactionOptions;
use Sui\Transactions\TransactionDataBuilder;

class ResolveTransactionData extends BasePlugin implements TransactionPlugin
{
    /**
     * @param TransactionDataBuilder $transactionData
     * @param BuildTransactionOptions $options
     * @param \Closure $next
     * @return void
     */
    public function run(
        TransactionDataBuilder $transactionData,
        BuildTransactionOptions $options,
        \Closure $next
    ): void {
        $this->init(
            $options,
            $transactionData
        );
        $this->normalizeInputs();
        $this->resolveObjectReferences();

        if (!$options->onlyTransactionKind) {
            $this->setGasPrice();
            $this->setGasBudget();
            $this->setGasPayment();
        }

        $this->validate();

        $next();
    }
}
