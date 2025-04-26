<?php

declare(strict_types=1);

namespace Sui\Response;

use Sui\Type\CoinStruct;

class CoinsResponse
{
    /**
     * @var array<CoinStruct>
     */
    public array $data;

    public bool $hasNextPage;

    public ?string $nextCursor;

    /**
     * @param array<mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $instance = new self();

        $instance->data = array_map(
            static fn(array $item) => new CoinStruct($item),
            $data['data'] ?? []
        );

        $instance->nextCursor = $data['nextCursor'] ?? null;
        $instance->hasNextPage = (bool) ($data['hasNextPage'] ?? false);

        return $instance;
    }
}
