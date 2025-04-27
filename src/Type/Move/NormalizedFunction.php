<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class NormalizedFunction
{
    public bool $isEntry;

    /**
     * @var array<string,NormalizedType>
     */
    public array $parameters;

    /**
     * @var array<string,NormalizedType>
     */
    public array $return;

    /**
     * @var array<string,AbilitySet>
     */
    public array $typeParameters;

    public string $visibility;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->isEntry = (bool) ($data['isEntry'] ?? false);
        $this->parameters = array_map(
            fn(array $parameter) => new NormalizedType($parameter),
            $data['parameters']
        );
        $this->return = array_map(
            fn(array $return) => new NormalizedType($return),
            $data['return']
        );
        $this->typeParameters = array_map(
            fn(array $typeParameter) => new AbilitySet($typeParameter),
            $data['typeParameters']
        );
        $this->visibility = (string) ($data['visibility'] ?? '');
    }
}
