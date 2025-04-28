<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class NormalizedType
{
    public string|int|null $key;

    public string|Struct|float|NormalizedType $value;

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
            switch ($this->key) {
                case 'Struct':
                    $this->value = new Struct($data[$this->key]);
                    break;
                case 'Vector':
                case 'Reference':
                case 'MutableReference':
                    $this->value = new NormalizedType($data[$this->key]);
                    break;
                default:
                    $this->value = $data[$this->key];
            }
        }
    }
}
