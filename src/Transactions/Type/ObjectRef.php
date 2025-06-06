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

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'objectId' => $this->objectId,
            'version' => $this->version,
            'digest' => $this->digest,
        ];
    }
}
