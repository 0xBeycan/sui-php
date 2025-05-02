<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

#[\AllowDynamicProperties]
abstract class SafeEnum
{
    /**
     * @param string $kind
     * @param mixed $value
     */
    public function __construct(
        public string $kind,
        public mixed &$value,
    ) {
        $this->{$this->kind} = &$value;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }
}
