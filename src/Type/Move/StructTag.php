<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class StructTag
{
    public string $address;

    public string $module;

    public string $name;

    /**
     * @var array<StructTag|string>
     */
    public array $typeParams;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->address = $data['address'];
        $this->module = $data['module'];
        $this->name = $data['name'];
        $this->typeParams = array_map(
            fn (array|string $item) => is_string($item) ? $item : new StructTag($item),
            $data['typeParams'] ?? []
        );
    }
}
