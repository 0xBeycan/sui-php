<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class ModuleId
{
    public string $address;

    public string $name;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->address = $data['address'];
        $this->name = $data['name'];
    }
}
