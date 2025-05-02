<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class TransactionData
{
    /**
     * @param int $version
     * @param GasData $gasData
     * @param array<CallArg> $inputs
     * @param array<Command> $commands
     * @param string|null $sender
     * @param TransactionExpiration|null $expiration
     */
    public function __construct(
        public int $version,
        public GasData $gasData,
        public array $inputs,
        public array $commands,
        public ?string $sender = null,
        public ?TransactionExpiration $expiration = null,
    ) {
    }
}
