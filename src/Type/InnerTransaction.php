<?php

declare(strict_types=1);

namespace Sui\Type;

class InnerTransaction
{
    public TransactionBlockData $data;

    /**
     * @var array<string> $txSignatures
     */
    public array $txSignatures;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->txSignatures = $data['txSignatures'];
        $this->data = new TransactionBlockData($data['data']);
    }
}
