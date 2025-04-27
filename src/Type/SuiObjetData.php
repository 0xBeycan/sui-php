<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiObjetData
{
    public ?string $type;

    public string $digest;

    public string $version;

    public string $objectId;

    /**
     * @var array<string,mixed>|null
     */
    public ?array $display;

    public ?string $storageRebate;

    public ?RawDataObject $bcs;

    public ?ObjectOwner $owner;

    public ?ObjectContent $content;

    public ?string $previousTransaction;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->digest = $data['digest'];
        $this->version = $data['version'];
        $this->objectId = $data['objectId'];
        $this->type = $data['type'] ?? null;
        $this->display = $data['display'] ?? null;
        $this->storageRebate = $data['storageRebate'] ?? null;
        $this->previousTransaction = $data['previousTransaction'] ?? null;
        $this->bcs = isset($data['bcs']) ? new RawDataObject($data['bcs']) : null;
        $this->owner = isset($data['owner']) ? new ObjectOwner($data['owner']) : null;
        $this->content = isset($data['content']) ? new ObjectContent($data['content']) : null;
    }
}
