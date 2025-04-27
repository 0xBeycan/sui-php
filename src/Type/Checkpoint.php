<?php

declare(strict_types=1);

namespace Sui\Type;

class Checkpoint
{
    /**
     * @var array<CheckpointCommitment>
     */
    public array $checkpointCommitments;

    public string $digest;

    public ?EndOfEpochData $endOfEpochData;

    public string $epoch;

    public GasCostSummary $epochRollingGasCostSummary;

    public string $networkTotalTransactions;

    public ?string $previousDigest;

    public string $sequenceNumber;

    public string $timestampMs;

    /**
     * @var array<string>
     */
    public array $transactions;

    public string $validatorSignature;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->epoch = $data['epoch'];
        $this->digest = $data['digest'];
        $this->timestampMs = $data['timestampMs'];
        $this->transactions = $data['transactions'];
        $this->sequenceNumber = $data['sequenceNumber'];
        $this->validatorSignature = $data['validatorSignature'];
        $this->previousDigest = $data['previousDigest'] ?? null;
        $this->networkTotalTransactions = $data['networkTotalTransactions'];
        $this->checkpointCommitments = array_map(
            static fn(array $item) => new CheckpointCommitment($item),
            $data['checkpointCommitments']
        );
        $this->epochRollingGasCostSummary = new GasCostSummary($data['epochRollingGasCostSummary']);
        $this->endOfEpochData = isset($data['endOfEpochData']) ? new EndOfEpochData($data['endOfEpochData']) : null;
    }
}
