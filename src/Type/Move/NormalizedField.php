<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class NormalizedField
{
    public string $name;

    public NormalizedType $type;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->type = new NormalizedType($data['type']);
    }
}
