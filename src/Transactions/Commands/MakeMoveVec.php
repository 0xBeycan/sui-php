<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class MakeMoveVec
{
    /**
     * @param array<Argument> $elements
     * @param string|null $type
     */
    public function __construct(
        public array $elements,
        public ?string $type,
    ) {
    }
}
