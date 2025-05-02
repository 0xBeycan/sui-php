<?php

declare(strict_types=1);

namespace Sui\Transactions;

class TransactionData
{
    private string $sender;

    /**
     * Undocumented function
     */
    public function __construct()
    {
    }

    /**
     * Set the sender of the transaction
     *
     * @param string $sender
     * @return self
     */
    public function setSender(string $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Get the sender of the transaction
     *
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * Set the sender of the transaction if it is not already set
     *
     * @param string $sender
     * @return self
     */
    public function setSenderIfNotSet(string $sender): self
    {
        if (!$this->sender) {
            $this->sender = $sender;
        }
        return $this;
    }
}
