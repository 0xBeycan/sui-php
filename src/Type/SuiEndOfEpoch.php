<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiEndOfEpoch
{
    public string|int|null $key;

    public mixed $value;

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
            $value = $data[$this->key];

            switch ($this->key) {
                case 'ChangeEpoch':
                    $this->value = new SuiChangeEpoch($value);
                    break;
                default:
                    $this->value = $value;
            }
        }
    }
}
