<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class TransferObjects
{
    /**
     * @param array<Argument> $objects
     * @param Argument $address
     */
    public function __construct(
        public array $objects,
        public Argument $address,
    ) {
    }
}
