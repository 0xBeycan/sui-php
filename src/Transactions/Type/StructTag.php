<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class StructTag
{
    /**
     * @param string $address
     * @param string $module
     * @param string $name
     * @param array<mixed> $typeParams
     */
    public function __construct(
        public string $address,
        public string $module,
        public string $name,
        public array $typeParams,
    ) {
    }
}
