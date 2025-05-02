<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class SplitCoins
{
    /**
     * @param Argument $coin
     * @param array<Argument> $amounts
     */
    public function __construct(
        public Argument $coin,
        public array $amounts,
    ) {
    }
}
