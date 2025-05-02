<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

class Intent extends Command
{
    /**
     * @param string $name
     * @param array<string,mixed> $inputs
     * @param array<string,mixed> $data
     */
    public function __construct(
        public string $name,
        public array $inputs,
        public array $data,
    ) {
    }
}
