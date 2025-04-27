<?php

declare(strict_types=1);

namespace Sui\Type;

class TransactionBlock
{
    public string $digest;

    /**
     * @var BalanceChange[]|null
     */
    public ?array $balanceChanges = null;

    public ?string $checkpoint = null;

    public ?bool $confirmedLocalExecution = null;

    public ?TransactionEffects $effects = null;

    /**
     * @var string[]|null
     */
    public ?array $errors = null;

    /**
     * @var SuiEvent[]|null
     */
    public ?array $events = null;

    /**
     * @var SuiObjectChange[]|null
     */
    public ?array $objectChanges = null;

    /**
     * @var int[]|null
     */
    public ?array $rawEffects = null;

    /**
     * @var string|null
     */
    public ?string $rawTransaction = null;

    public ?string $timestampMs = null;

    /**
     * @var InnerTransaction|null
     */
    public ?InnerTransaction $transaction = null;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->digest = $data['digest'];
        $this->balanceChanges = isset($data['balanceChanges'])
            ? array_map(
                static fn(array $item) => new BalanceChange($item),
                $data['balanceChanges']
            )
            : null;
        $this->checkpoint = $data['checkpoint'] ?? null;
        $this->confirmedLocalExecution = $data['confirmedLocalExecution'] ?? null;
        $this->effects = isset($data['effects']) ? new TransactionEffects($data['effects']) : null;
        $this->errors = $data['errors'] ?? null;
        $this->events = isset($data['events'])
            ? array_map(
                static fn(array $item) => new SuiEvent($item),
                $data['events']
            )
            : null;
        $this->objectChanges = isset($data['objectChanges'])
            ? array_map(
                static fn(array $item) => new SuiObjectChange($item),
                $data['objectChanges']
            )
            : null;
        $this->rawEffects = $data['rawEffects'] ?? null;
        $this->rawTransaction = $data['rawTransaction'] ?? null;
        $this->timestampMs = $data['timestampMs'] ?? null;
        $this->transaction = isset($data['transaction']) ? new InnerTransaction($data['transaction']) : null;
    }
}
