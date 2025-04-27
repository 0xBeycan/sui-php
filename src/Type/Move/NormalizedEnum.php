<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class NormalizedEnum
{
    public AbilitySet $abilities;

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
        $this->abilities = new AbilitySet($data['abilities']);
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
