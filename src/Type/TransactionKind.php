<?php

declare(strict_types=1);

namespace Sui\Type;

class TransactionKind
{
    /**
     * ChangeEpoch | Genesis | ConsensusCommitPrologue | ProgrammableTransaction |
     * AuthenticatorStateUpdate | RandomnessStateUpdate | EndOfEpochTransaction |
     * ConsensusCommitPrologueV2 | ConsensusCommitPrologueV3 | ConsensusCommitPrologueV4
     * @var string
     */
    public string $kind;

    public ?string $epoch = null;

    public ?string $round = null;

    public ?string $commitTimestampMs = null;

    public ?string $consensusCommitDigest = null;

    public ?string $subDagIndex = null;

    public ?string $additionalStateDigest = null;

    public ?string $computationCharge = null;

    public ?string $storageCharge = null;

    public ?string $storageRebate = null;

    public ?string $epochStartTimestampMs = null;

    /** @var string[]|null */
    public ?array $objects = null;

    /** @var SuiCallArg[]|null */
    public ?array $inputs = null;

    /** @var SuiTransaction[]|null */
    public ?array $transactions = null;

    /** @var SuiActiveJwk[]|null */
    public ?array $newActiveJwks = null;

    /** @var int[]|null */
    public ?array $randomBytes = null;

    /** @var SuiEndOfEpoch[]|null */
    public ?array $endOfEpochTransactions = null;

    public mixed $consensusDeterminedVersionAssignments = null;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->kind = $data['kind'];
        $this->epoch = $data['epoch'] ?? null;
        $this->round = $data['round'] ?? null;
        $this->objects = $data['objects'] ?? null;
        $this->randomBytes = $data['random_bytes'] ?? null;
        $this->subDagIndex = $data['sub_dag_index'] ?? null;
        $this->storageCharge = $data['storage_charge'] ?? null;
        $this->storageRebate = $data['storage_rebate'] ?? null;
        $this->computationCharge = $data['computation_charge'] ?? null;
        $this->commitTimestampMs = $data['commit_timestamp_ms'] ?? null;
        $this->consensusCommitDigest = $data['consensus_commit_digest'] ?? null;
        $this->additionalStateDigest = $data['additional_state_digest'] ?? null;
        $this->epochStartTimestampMs = $data['epoch_start_timestamp_ms'] ?? null;
        $this->consensusDeterminedVersionAssignments = $data['consensus_determined_version_assignments'] ?? null;

        $this->inputs = isset($data['inputs']) ? array_map(
            static fn(array $item) => new SuiCallArg($item),
            $data['inputs']
        ) : null;
        $this->transactions = isset($data['transactions']) ? array_map(
            static fn(array $item) => new SuiTransaction($item),
            $data['transactions']
        ) : null;
        $this->newActiveJwks = isset($data['new_active_jwks']) ? array_map(
            static fn(array $item) => new SuiActiveJwk($item),
            $data['new_active_jwks']
        ) : null;
        $this->endOfEpochTransactions = isset($data['transactions']) ? array_map(
            static fn(array $item) => new SuiEndOfEpoch($item),
            $data['transactions']
        ) : null;
    }
}
