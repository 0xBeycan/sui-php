<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiObject
{
    public ?string $type;

    public string $digest;

    public string $version;

    public string $objectId;

    public ?string $storageRebate;

    public ?ObjectOwner $owner;

    public ?ObjectContent $content;

    public ?string $previousTransaction;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->type = $data['type'] ?? null;
        $this->digest = $data['digest'];
        $this->version = $data['version'];
        $this->objectId = $data['objectId'];
        $this->storageRebate = $data['storageRebate'] ?? null;
        $this->owner = isset($data['owner']) ? new ObjectOwner($data['owner']) : null;
        $this->content = isset($data['content']) ? new ObjectContent($data['content']) : null;
        $this->previousTransaction = $data['previousTransaction'] ?? null;
    }
}
