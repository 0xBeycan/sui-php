<?php

declare(strict_types=1);

namespace Sui\Response;

use Sui\Type\ObjectOwner;
use Sui\Type\ObjectContent;

class ObjectResponse
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
     * @param array<mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();

        $instance->type = $data['type'] ?? null;
        $instance->digest = $data['digest'];
        $instance->version = $data['version'];
        $instance->objectId = $data['objectId'];
        $instance->storageRebate = $data['storageRebate'] ?? null;
        $instance->owner = isset($data['owner']) ? new ObjectOwner($data['owner']) : null;
        $instance->content = isset($data['content']) ? new ObjectContent($data['content']) : null;
        $instance->previousTransaction = $data['previousTransaction'] ?? null;

        return $instance;
    }
}
