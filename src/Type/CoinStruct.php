<?php

declare(strict_types=1);

namespace Sui\Type;

class CoinStruct
{
    public string $balance;

    public string $coinObjectId;

    public string $coinType;

    public string $digest;

    public string $previousTransaction;

    public string $version;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->balance = $data['balance'];
        $this->coinObjectId = $data['coinObjectId'];
        $this->coinType = $data['coinType'];
        $this->digest = $data['digest'];
        $this->previousTransaction = $data['previousTransaction'];
        $this->version = $data['version'];
    }
}
