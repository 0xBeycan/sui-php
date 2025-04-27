<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiCallArg
{
    public string $type;

    public ?string $objectId = null;

    public ?string $objectType = null;

    public ?string $digest = null;

    public ?string $version = null;

    public ?string $initialSharedVersion = null;

    public ?bool $mutable = null;

    public mixed $value = null;

    public ?string $valueType = null;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->type = $data['type'] ?? '';
        $this->objectId = $data['objectId'] ?? null;
        $this->objectType = $data['objectType'] ?? null;
        $this->digest = $data['digest'] ?? null;
        $this->version = $data['version'] ?? null;
        $this->initialSharedVersion = $data['initialSharedVersion'] ?? null;
        $this->mutable = $data['mutable'] ?? null;
        $this->value = $data['value'] ?? null;
        $this->valueType = $data['valueType'] ?? null;
    }
}
