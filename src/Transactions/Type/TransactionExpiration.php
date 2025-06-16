<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class TransactionExpiration extends SafeEnum
{
    /**
     * @param string $kind
     * @param bool|null $none
     * @param string|int|float|null $epoch
     */
    public function __construct(
        public string $kind,
        public bool|null $none,
        string|int|float|null $epoch,
    ) {
        $epoch = $epoch ? (string) $epoch : null;
        $ref = $epoch ?? $none ?? null;
        parent::__construct($kind, $ref);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            '$kind' => $this->kind,
            'None' => $this->none,
            $this->kind => (int) $this->value,
        ]);
    }
}
