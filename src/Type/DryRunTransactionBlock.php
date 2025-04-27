<?php

declare(strict_types=1);

namespace Sui\Type;

class DryRunTransactionBlock
{
    /**
     * @var array<BalanceChange>
     */
    public array $balanceChanges;

    /**
     * @var TransactionEffects
     */
    public TransactionEffects $effects;

    /**
     * @var array<SuiEvent>
     */
    public array $events;

    /**
     * @var string|null
     */
    public ?string $executionErrorSource;

    /**
     * @var TransactionBlockData
     */
    public TransactionBlockData $input;

    /**
     * @var array<SuiObjectChange>
     */
    public array $objectChanges;

    /**
     * @var string|null
     */
    public ?string $suggestedGasPrice;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->balanceChanges = array_map(
            fn($item) => new BalanceChange($item),
            $data['balanceChanges']
        );
        $this->effects = new TransactionEffects($data['effects']);
        $this->events = array_map(
            fn($item) => new SuiEvent($item),
            $data['events']
        );
        $this->executionErrorSource = $data['executionErrorSource'] ?? null;
        $this->input = new TransactionBlockData($data['input']);
        $this->objectChanges = array_map(
            fn($item) => new SuiObjectChange($item),
            $data['objectChanges']
        );
        $this->suggestedGasPrice = $data['suggestedGasPrice'] ?? null;
    }
}
