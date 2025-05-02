<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

class Publish extends Command
{
    /**
     * @param array<string> $modules
     * @param array<string> $dependencies
     */
    public function __construct(
        public array $modules,
        public array $dependencies,
    ) {
    }
}
