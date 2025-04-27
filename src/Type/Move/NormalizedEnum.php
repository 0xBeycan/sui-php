<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class NormalizedEnum
{
    /**
     * @var array<mixed>
     */
    public array $abilities;

    /**
     * @var array<string,StructTypeParameter>
     */
    public array $typeParameters;

    /**
     * @var array<string,array<NormalizedField>>
     */
    public array $variants;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->abilities = $data['abilities'] ?? [];
        $this->typeParameters = array_map(
            static fn (array $item) => new StructTypeParameter($item),
            $data['typeParameters'] ?? []
        );
        $this->variants = array_map(
            static fn (array $item) => array_map(
                static fn (array $variant) => new NormalizedField($variant),
                $item
            ),
            $data['variants'] ?? []
        );
    }
}
