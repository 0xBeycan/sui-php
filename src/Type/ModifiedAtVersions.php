<?php

declare(strict_types=1);

namespace Sui\Type;

class ModifiedAtVersions
{
    public string $objectId;

    public string $sequenceNumber;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->objectId = $data['objectId'];
        $this->sequenceNumber = $data['sequenceNumber'];
    }
}
