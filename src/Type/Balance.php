<?php

declare(strict_types=1);

namespace Sui\Type;

class Balance
{
    public string $coinType;

    public int $coinObjectCount;

    public string $totalBalance;

    /**
     * @var array<string, string>
     */
    public array $lockedBalance;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->coinType = $data['coinType'];
        $this->coinObjectCount = (int) $data['coinObjectCount'];
        $this->totalBalance = (string) $data['totalBalance'];
        $this->lockedBalance = (array) $data['lockedBalance'];
    }
}
