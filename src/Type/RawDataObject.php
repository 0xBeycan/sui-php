<?php

declare(strict_types=1);

namespace Sui\Type;

class RawDataObject
{
    public string $bcsBytes;

    public string $dataType;

    public bool $hasPublicTransfer;

    public string $type;

    public string $version;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->bcsBytes = $data['bcsBytes'];
        $this->dataType = $data['dataType'];
        $this->hasPublicTransfer = $data['hasPublicTransfer'];
        $this->type = $data['type'];
        $this->version = $data['version'];
    }
}
