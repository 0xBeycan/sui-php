<?php

declare(strict_types=1);

namespace Sui\Response;

class BalanceResponse
{
    public string $coinType;

    public int $coinObjectCount;

    public string $totalBalance;

    /**
     * @var array<string, string>
     */
    public array $lockedBalance;

    /**
     * @param array<mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();

        $instance->coinType = $data['coinType'];
        $instance->coinObjectCount = (int) $data['coinObjectCount'];
        $instance->totalBalance = (string) $data['totalBalance'];
        $instance->lockedBalance = (array) $data['lockedBalance'];

        return $instance;
    }
}
