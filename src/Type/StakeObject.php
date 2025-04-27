<?php

declare(strict_types=1);

namespace Sui\Type;

class StakeObject
{
    public string $principal;

    public string $stakeActiveEpoch;

    public string $stakeRequestEpoch;

    public string $stakedSuiId;

    public string $status;

    public ?string $estimatedReward = null;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->principal = $data['principal'];
        $this->stakeActiveEpoch = $data['stakeActiveEpoch'];
        $this->stakeRequestEpoch = $data['stakeRequestEpoch'];
        $this->stakedSuiId = $data['stakedSuiId'];
        $this->status = $data['status'];
        $this->estimatedReward = $data['estimatedReward'] ?? null;
    }
}
