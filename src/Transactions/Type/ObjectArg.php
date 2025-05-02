<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class ObjectArg extends SafeEnum
{
    /**
     * @param string $kind
     * @param ObjectRef|SharedObject $value
     */
    public function __construct(
        public string $kind,
        ObjectRef|SharedObject $value,
    ) {
        parent::__construct($kind, $value);
    }
}
