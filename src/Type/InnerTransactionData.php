<?php

declare(strict_types=1);

namespace Sui\Type;

class InnerTransactionData
{
    public SuiGasData $gasData;

    public string $messageVersion;

    public string $sender;

    public TransactionKind $transaction;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->sender = $data['sender'];
        $this->messageVersion = $data['messageVersion'];
        $this->gasData = new SuiGasData($data['gasData']);
        $this->transaction = new TransactionKind($data['transaction']);
    }
}
