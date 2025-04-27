<?php

declare(strict_types=1);

namespace Sui\Transactions;

class Transaction
{
    private string $sender;

    /**
     * @param string $sender
     * @return void
     */
    public function setSenderIfNotSet(string $sender): void
    {
        if (!isset($this->sender)) {
            $this->sender = $sender;
        }
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param array<mixed> $options
     * @return array<int>
     */
    public function build(array $options): array
    {
        return [];
    }
}
