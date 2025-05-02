<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\TypeSignature;

// phpcs:disable

class MoveCall extends Command
{
    /**
     * @param string $package
     * @param string $module
     * @param string $function
     * @param array<string> $typeArguments
     * @param array<Argument> $arguments
     * @param array<TypeSignature>|null $_argumentTypes
     */
    public function __construct(
        public string $package,
        public string $module,
        public string $function,
        public array $typeArguments,
        public array $arguments,
        public ?array $_argumentTypes = null
    ) {
    }
}
