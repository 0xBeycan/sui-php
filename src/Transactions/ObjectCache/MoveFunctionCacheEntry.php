<?php

declare(strict_types=1);

namespace Sui\Transactions\ObjectCache;

interface MoveFunctionCacheEntry
{
    /**
     * Get the package
     * @return string
     */
    public function getPackage(): string;

    /**
     * Get the module
     * @return string
     */
    public function getModule(): string;

    /**
     * Get the function
     * @return string
     */
    public function getFunction(): string;

    /**
     * Get the parameters
     * @return array<string, mixed>
     */
    public function getParameters(): array;
}
