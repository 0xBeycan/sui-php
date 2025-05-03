<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class CallArg extends SafeEnum
{
    /**
     * @var ObjectArg|Pure|UnresolvedPure|UnresolvedObject
     */
    public mixed $value;

    /**
     * @param string $kind
     * @param ObjectArg|Pure|UnresolvedPure|UnresolvedObject $value
     */
    public function __construct(
        public string $kind,
        ObjectArg|Pure|UnresolvedPure|UnresolvedObject $value,
    ) {
        parent::__construct($kind, $value);
    }
}
