<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class Upgrade extends Command
{
    /**
     * @param array<string> $modules
     * @param array<string> $dependencies
     * @param string $package
     * @param Argument $ticket
     */
    public function __construct(
        public array $modules,
        public array $dependencies,
        public string $package,
        public Argument $ticket,
    ) {
    }
}
