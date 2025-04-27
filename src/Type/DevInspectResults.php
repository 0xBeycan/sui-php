<?php

declare(strict_types=1);

namespace Sui\Type;

class DevInspectResults
{
    public TransactionEffects $effects;

    public ?string $error = null;

    /**
     * @var SuiEvent[]
     */
    public array $events = [];

    /**
     * @var int[]
     */
    public ?array $rawEffects = null;

    /**
     * @var int[]
     */
    public ?array $rawTxnData = null;

    /**
     * @var SuiExecutionResult[]|null
     */
    public ?array $results = null;

    /**
     * @param array<mixed,> $data
     */
    public function __construct(array $data)
    {
        $this->effects = new TransactionEffects($data['effects']);
        $this->error = $data['error'] ?? null;
        $this->events = array_map(fn($event) => new SuiEvent($event), $data['events'] ?? []);
        $this->rawEffects = $data['rawEffects'] ?? null;
        $this->rawTxnData = $data['rawTxnData'] ?? null;
        $this->results = isset($data['results']) ? array_map(
            fn($result) => new SuiExecutionResult($result),
            $data['results']
        ) : null;
    }
}
