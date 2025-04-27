<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class Struct
{
    public string $address;

    public string $module;

    public string $name;

    /**
     * @var array<NormalizedType>
     */
    public array $typeArguments;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->address = $data['address'];
        $this->module = $data['module'];
        $this->name = $data['name'];
        $this->typeArguments = array_map(
            fn (array|string $item) => new NormalizedType($item),
            $data['typeArguments'] ?? []
        );
    }
}
