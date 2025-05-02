<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class Pure
{
    /**
     * @param string $bytes
     */
    public function __construct(
        public string $bytes,
    ) {
    }
}
