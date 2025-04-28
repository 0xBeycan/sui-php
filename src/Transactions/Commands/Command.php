<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

abstract class Command
{
    /**
     * @return array<mixed>
     */
    abstract public function toArray(): array;

    /**
     * @return string
     */
    public function getKind(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }
}
