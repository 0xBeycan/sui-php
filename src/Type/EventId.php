<?php

declare(strict_types=1);

namespace Sui\Type;

class EventId
{
    public string $eventSeq;

    public string $txDigest;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->eventSeq = (string) $data['eventSeq'];
        $this->txDigest = (string) $data['txDigest'];
    }
}
