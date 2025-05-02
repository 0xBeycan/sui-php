<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class GasData
{
    /**
     * @param string|null $budget
     * @param string|null $price
     * @param string|null $owner
     * @param array<mixed>|null $payment
     */
    public function __construct(
        public ?string $budget,
        public ?string $price,
        public ?string $owner,
        public ?array $payment
    ) {
    }
}
