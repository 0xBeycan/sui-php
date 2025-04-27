<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiObjectRef
{
    public string $digest;

    public string $objectId;

    public string $version;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->digest = $data['digest'];
        $this->objectId = $data['objectId'];
        $this->version = $data['version'];
    }
}
