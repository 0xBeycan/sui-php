<?php

declare(strict_types=1);

namespace Sui\Type;

class BalanceChange
{
    public string $amount;

    public string $coinType;

    public ObjectOwner $owner;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->amount = (string) $data['amount'];
        $this->coinType = (string) $data['coinType'];
        $this->owner = new ObjectOwner($data['owner']);
    }
}
