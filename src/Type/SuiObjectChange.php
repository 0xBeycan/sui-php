<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiObjectChange
{
    public string $type;

    public string $version;

    public string $sender;

    public ?string $digest = null;

    public ?string $objectId = null;

    public ?string $objectType = null;

    public ?string $packageId = null;

    /** @var string[]|null */

    public ?array $modules = null;

    public ?ObjectOwner $owner = null;

    public ?ObjectOwner $recipient = null;

    public ?string $previousVersion = null;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->type = $data['type'];
        $this->version = $data['version'];
        $this->sender = $data['sender'];
        $this->digest = $data['digest'] ?? null;
        $this->objectId = $data['objectId'] ?? null;
        $this->objectType = $data['objectType'] ?? null;
        $this->packageId = $data['packageId'] ?? null;
        $this->modules = $data['modules'] ?? null;
        $this->owner = isset($data['owner']) ? new ObjectOwner($data['owner']) : null;
        $this->recipient = isset($data['recipient']) ? new ObjectOwner($data['recipient']) : null;
        $this->previousVersion = $data['previousVersion'] ?? null;
    }
}
