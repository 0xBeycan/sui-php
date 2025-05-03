<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Client;

class BuildTransactionOptions
{
    /**
     * @param ?Client $client
     * @param bool $onlyTransactionKind
     * @param ?array<string> $supportedIntents
     */
    public function __construct(
        public ?Client $client = null,
        public bool $onlyTransactionKind = false,
        public ?array $supportedIntents = null,
    ) {
    }
}
