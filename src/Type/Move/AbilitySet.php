<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class AbilitySet
{
    /**
     * @var array<string> 'Copy' | 'Drop' | 'Store' | 'Key'
     */
    public array $abilities;

    /**
     * @param array<string> $abilities
     */
    public function __construct(array $abilities)
    {
        $this->abilities = $abilities;
    }
}
