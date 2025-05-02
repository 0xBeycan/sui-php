<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class SharedObject
{
    /**
     * @param string $objectId
     * @param string $initialSharedVersion
     * @param bool $mutable
     */
    public function __construct(
        public string $objectId,
        public string $initialSharedVersion,
        public bool $mutable,
    ) {
    }
}
