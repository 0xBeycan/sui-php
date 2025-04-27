<?php

declare(strict_types=1);

namespace Sui\Type;

class EndOfEpochData
{
    /**
     * @var array<CheckpointCommitment>
     */
    public array $epochCommitments;

    /**
     * @var array<array<string,string>>
     */
    public array $nextEpochCommittee;

    public string $nextEpochProtocolVersion;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->epochCommitments = array_map(
            static fn(array $item) => new CheckpointCommitment($item),
            $data['epochCommitments']
        );
        $this->nextEpochCommittee = $data['nextEpochCommittee'];
        $this->nextEpochProtocolVersion = $data['nextEpochProtocolVersion'];
    }
}
