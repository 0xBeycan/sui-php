<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Client;

/**
 * Options for building a transaction
 */
class BuildTransactionOptions
{
    public ?Client $client = null;
    public ?bool $onlyTransactionKind = null;

    /**
     * Constructor for BuildTransactionOptions
     *
     * @param Client|null $client The Sui client instance
     * @param bool|null $onlyTransactionKind Whether to only build the transaction kind
     */
    public function __construct(?Client $client = null, ?bool $onlyTransactionKind = null)
    {
        $this->client = $client;
        $this->onlyTransactionKind = $onlyTransactionKind;
    }

    /**
     * @param array<string, mixed> $options
     * @return self
     */
    public static function fromArray(array $options): self
    {
        return new self(
            $options['client'] ?? null,
            $options['onlyTransactionKind'] ?? null
        );
    }
}
