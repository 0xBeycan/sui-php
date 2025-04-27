<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiGasData
{
    public string $budget;

    public string $owner;

    /**
     * @var SuiObjectRef[]
     */
    public array $payment;

    public string $price;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->budget = $data['budget'];
        $this->owner = $data['owner'];
        $this->price = $data['price'];
        $this->payment = array_map(
            static fn(array $item) => new SuiObjectRef($item),
            $data['payment'] ?? []
        );
    }
}
