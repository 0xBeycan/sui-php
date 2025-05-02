<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class ObjectRef
{
    /**
     * @param string $objectId
     * @param string $version
     * @param string $digest
     */
    public function __construct(
        public string $objectId,
        public string $version,
        public string $digest,
    ) {
    }
}
