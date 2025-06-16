<?php

declare(strict_types=1);

namespace Sui\Type;

class UpgradeInfo
{
    public string $upgradedId;

    public string $upgradedVersion;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->upgradedId = $data['upgraded_id'];
        $this->upgradedVersion = $data['upgraded_version'];
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'upgraded_id' => $this->upgradedId,
            'upgraded_version' => $this->upgradedVersion,
        ];
    }
}
