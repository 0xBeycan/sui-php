<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class NormalizedType
{
    public ?string $key;

    public string|Struct|float $type;

    /**
     * @param array<mixed>|string $data
     */
    public function __construct(array|string $data)
    {
        if (is_string($data)) {
            $this->key = null;
            $this->type = $data;
        } else {
            $this->key = array_key_first($data);
            switch ($data[$this->key]) {
                case 'Struct':
                    $this->type = new Struct($data[$this->key]);
                    break;
                default:
                    $this->type = $data[$this->key];
            }
        }
    }
}
