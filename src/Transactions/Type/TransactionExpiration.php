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
        public string|int|float|null $epoch,
    ) {
        $epoch = $epoch ? (string) $epoch : null;
        parent::__construct($kind, $epoch ?? $none);
    }
}
