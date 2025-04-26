<?php

declare(strict_types=1);

namespace Sui\Type;

class CoinMetadata
{
    public int $decimals;

    public string $description;

    public ?string $iconUrl;

    public ?string $id;

    public string $name;

    public string $symbol;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->symbol = $data['symbol'];
        $this->id = ($data['id'] ?? null);
        $this->decimals = (int) $data['decimals'];
        $this->description = $data['description'];
        $this->iconUrl = ($data['iconUrl'] ?? null);
    }
}
