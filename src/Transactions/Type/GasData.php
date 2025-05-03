<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class GasData
{
    /**
     * @param string|null $budget
     * @param string|null $price
     * @param string|null $owner
     * @param array<ObjectRef>|null $payment
     */
    public function __construct(
        public ?string $budget = null,
        public ?string $price = null,
        public ?string $owner = null,
        public ?array $payment = null
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'budget' => $this->budget,
            'price' => $this->price,
            'owner' => $this->owner,
            'payment' => array_map(fn(ObjectRef $objectRef) => $objectRef->toArray(), $this->payment ?? []),
        ];
    }
}
