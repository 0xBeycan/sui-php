<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiTransaction
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
                case 'MoveCall':
                    $this->value = new MoveCallSuiTransaction($value);
                    break;
                case 'TransferObjects':
                    $this->value = [
                        array_map(
                            fn($item) => new SuiArgument($item),
                            $value[0]
                        ),
                        new SuiArgument($value[1]),
                    ];
                    break;
                case 'SplitCoins':
                case 'MergeCoins':
                    $this->value = [
                        new SuiArgument($value[0]),
                        array_map(
                            fn($item) => new SuiArgument($item),
                            $value[1]
                        ),
                    ];
                    break;
                case 'Upgrade':
                    $this->value = [
                        $value[0],
                        $value[1],
                        new SuiArgument($value[2]),
                    ];
                    break;
                case 'MakeMoveVec':
                    $this->value = [
                        $value[0],
                        array_map(
                            fn($item) => new SuiArgument($item),
                            $value[1]
                        ),
                    ];
                    break;
                default:
                    $this->value = $value;
            }
        }
    }
}
