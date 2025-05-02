<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class UnresolvedObject
{
    /**
     * @param string $objectId
     * @param string|null $version
     * @param string|null $digest
     * @param string|null $initialSharedVersion
     */
    public function __construct(
        public string $objectId,
        public ?string $version,
        public ?string $digest,
        public ?string $initialSharedVersion,
    ) {
    }
}
