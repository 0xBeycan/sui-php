<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiArgument
{
    public string|int|null $key;

    public string|float $value;

    /**
     * @param array<mixed>|string $data
     */
    public function __construct(array|string $data)
    {
        if (is_string($data)) {
            $this->key = null;
            $this->value = $data;
        } else {
            $this->key = array_key_first($data);
            $this->value = $data[$this->key];
        }
    }
}
