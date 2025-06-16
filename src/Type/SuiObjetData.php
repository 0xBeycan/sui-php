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

    public ?RawDataPackage $bcs;

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
        $this->bcs = isset($data['bcs']) ? new RawDataPackage($data['bcs']) : null;
        $this->owner = isset($data['owner']) ? new ObjectOwner($data['owner']) : null;
        $this->content = isset($data['content']) ? new ObjectContent($data['content']) : null;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'digest' => $this->digest,
            'version' => $this->version,
            'objectId' => $this->objectId,
            'type' => $this->type,
            'display' => $this->display,
            'storageRebate' => $this->storageRebate,
            'previousTransaction' => $this->previousTransaction,
            'bcs' => $this->bcs?->toArray() ?? null,
            'owner' => $this->owner?->toArray() ?? null,
            'content' => $this->content?->toArray() ?? null,
        ];
    }
}
