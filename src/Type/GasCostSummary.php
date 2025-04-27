<?php

declare(strict_types=1);

namespace Sui\Type;

class GasCostSummary
{
    public string $computationCost;

    public string $nonRefundableStorageFee;

    public string $storageCost;

    public string $storageRebate;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->storageCost = $data['storageCost'];
        $this->storageRebate = $data['storageRebate'];
        $this->computationCost = $data['computationCost'];
        $this->nonRefundableStorageFee = $data['nonRefundableStorageFee'];
    }
}
