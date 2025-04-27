<?php

declare(strict_types=1);

namespace Sui\Type;

class DynamicFieldInfo
{
    public string $digest;

    public DynamicFieldName $name;

    public string $objectId;

    public string $objectType;

    public string $type;

    public string $version;

    public string $bcsEncoding;

    public string $bcsName;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->digest = $data['digest'];
        $this->name = new DynamicFieldName($data['name']);
        $this->objectId = $data['objectId'];
        $this->objectType = $data['objectType'];
        $this->type = $data['type'];
        $this->version = $data['version'];
        $this->bcsEncoding = $data['bcsEncoding'];
        $this->bcsName = $data['bcsName'];
    }
}
