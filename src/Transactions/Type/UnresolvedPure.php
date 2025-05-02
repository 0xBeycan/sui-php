<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class UnresolvedPure
{
    /**
     * @param mixed $value
     */
    public function __construct(
        public mixed $value,
    ) {
    }
}
