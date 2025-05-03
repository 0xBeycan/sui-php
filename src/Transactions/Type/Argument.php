<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class Argument extends SafeEnum
{
    /**
     * @param string $kind
     * @param mixed $value
     * @param string|null $type
     */
    public function __construct(
        public string $kind,
        public mixed $value,
        public ?string $type = null,
    ) {
        parent::__construct($kind, $value);
    }
}
