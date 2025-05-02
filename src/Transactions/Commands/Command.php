<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Command as CommandType;

abstract class Command
{
    /**
     * @return CommandType
     */
    public function toCommand(): CommandType
    {
        return new CommandType($this->getKind(), $this);
    }

    /**
     * @return string
     */
    private function getKind(): string
    {
        return basename(str_replace('\\', '/', static::class));
    }
}
