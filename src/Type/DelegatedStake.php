<?php

declare(strict_types=1);

namespace Sui\Type;

class DelegatedStake
{
    /**
     * @var StakeObject[]
     */
    public array $stakes;

    public string $stakingPool;

    public string $validatorAddress;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->stakingPool = $data['stakingPool'];
        $this->validatorAddress = $data['validatorAddress'];
        $this->stakes = array_map(fn ($item) => new StakeObject($item), $data['stakes']);
    }
}
