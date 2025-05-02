<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class TypeSignature
{
    /**
     * @param mixed $body
     * @param string|null $ref
     */
    public function __construct(
        public mixed $body,
        public ?string $ref,
    ) {
    }
}
