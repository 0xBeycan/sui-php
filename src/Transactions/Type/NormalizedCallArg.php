<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class NormalizedCallArg extends SafeEnum
{
    /**
     * @var ObjectArg|Pure
     */
    public mixed $value;

    /**
     * @param string $kind
     * @param ObjectArg|Pure $value
     */
    public function __construct(
        public string $kind,
        ObjectArg|Pure $value,
    ) {
        parent::__construct($kind, $value);
    }
}
