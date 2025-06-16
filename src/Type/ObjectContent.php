<?php

declare(strict_types=1);

namespace Sui\Type;

class ObjectContent
{
    public ?string $type;

    /**
     * @var array<mixed>|null
     */
    public ?array $fields;

    public string $dataType;

    /**
     * @var array<mixed>|null
     */
    public ?array $disassembled;

    public ?bool $hasPublicTransfer;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->type = $data['type'] ?? null;
        $this->fields = $data['fields'] ?? null;
        $this->dataType = $data['dataType'];
        $this->disassembled = $data['disassembled'] ?? null;
        $this->hasPublicTransfer = $data['hasPublicTransfer'] ?? null;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'fields' => $this->fields,
            'dataType' => $this->dataType,
            'disassembled' => $this->disassembled,
            'hasPublicTransfer' => $this->hasPublicTransfer,
        ];
    }
}
