<?php

declare(strict_types=1);

namespace Sui\Transactions;

class Transaction
{
    public TransactionDataBuilder $data;

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

    /**
     * @param string $sender
     * @return void
     */
    public function setSender(string $sender): void
    {
        $this->data->sender = $sender;
    }

    /**
     * Sets the sender only if it has not already been set.
     * This is useful for sponsored transaction flows where the sender may not be the same as the signer address.
     *
     * @param string $sender
     * @return void
     */
    public function setSenderIfNotSet(string $sender): void
    {
        if (!$this->data->sender) {
            $this->data->sender = $sender;
        }
    }
}
