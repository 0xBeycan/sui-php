<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiChangeEpoch
{
    public string $computationCharge;

    public string $epoch;

    public string $epochStartTimestampMs;

    public string $storageCharge;

    public string $storageRebate;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->computationCharge = $data['computation_charge'];
        $this->epoch = $data['epoch'];
        $this->epochStartTimestampMs = $data['epoch_start_timestamp_ms'];
        $this->storageCharge = $data['storage_charge'];
        $this->storageRebate = $data['storage_rebate'];
    }
}
