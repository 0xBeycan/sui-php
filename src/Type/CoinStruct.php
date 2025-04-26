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
        $this->balance = (string) $data['balance'];
        $this->coinObjectId = (string) $data['coinObjectId'];
        $this->coinType = (string) $data['coinType'];
        $this->digest = (string) $data['digest'];
        $this->previousTransaction = (string) $data['previousTransaction'];
        $this->version = (string) $data['version'];
    }
}
