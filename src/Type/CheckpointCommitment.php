<?php

declare(strict_types=1);

namespace Sui\Type;

class CheckpointCommitment
{
    public ECMHLiveObjectSetDigest $ECMHLiveObjectSetDigest;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->ECMHLiveObjectSetDigest = new ECMHLiveObjectSetDigest($data['ECMHLiveObjectSetDigest']);
    }
}
