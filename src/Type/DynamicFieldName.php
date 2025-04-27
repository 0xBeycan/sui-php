<?php

declare(strict_types=1);

namespace Sui\Type;

class DynamicFieldName
{
    public string $type;

    public mixed $value;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->type = $data['type'];
        $this->value = $data['value'];
    }
}
