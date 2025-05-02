<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Client;

/**
 * Options for serializing a transaction
 */
class SerializeTransactionOptions extends BuildTransactionOptions
{
    /** @var string[]|null */
    public ?array $supportedIntents = null;

    /**
     * Constructor for SerializeTransactionOptions
     *
     * @param Client|null $client The Sui client instance
     * @param bool|null $onlyTransactionKind Whether to only build the transaction kind
     * @param string[]|null $supportedIntents Array of supported intents
     */
    public function __construct(
        ?Client $client = null,
        ?bool $onlyTransactionKind = null,
        ?array $supportedIntents = null
    ) {
        parent::__construct($client, $onlyTransactionKind);
        $this->supportedIntents = $supportedIntents;
    }
}
