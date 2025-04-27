<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class NormalizedStruct
{
    /**
     * @var array<mixed>
     */
    public array $abilities;

    /**
     * @var array<string,NormalizedField>
     */
    public array $fields;

    /**
     * @var array<string,StructTypeParameter>
     */
    public array $typeParameters;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->abilities = $data['abilities'] ?? [];
        $this->fields = array_map(
            static fn(array $field) => new NormalizedField($field),
            $data['fields']
        );
        $this->typeParameters = array_map(
            static fn(array $typeParameter) => new StructTypeParameter($typeParameter),
            $data['typeParameters']
        );
    }
}
