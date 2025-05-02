<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class MergeCoins extends Command
{
    /**
     * @param Argument $destination
     * @param array<Argument> $sources
     */
    public function __construct(
        public Argument $destination,
        public array $sources,
    ) {
    }
}
