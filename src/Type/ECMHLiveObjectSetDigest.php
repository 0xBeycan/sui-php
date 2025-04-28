<?php

declare(strict_types=1);

namespace Sui\Type;

class ECMHLiveObjectSetDigest
{
    /**
     * @var array<int>
     */
    public array $digest;

    /**
     * @param array<mixed> $digest
     */
    public function __construct(array $digest)
    {
        $this->digest = $digest;
    }
}
