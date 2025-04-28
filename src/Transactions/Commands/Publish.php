<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

class Publish extends Command
{
    /**
     * @var array<string>
     */
    private array $modules;

    /**
     * @var array<string>
     */
    private array $dependencies;

    /**
     * @param array<string> $modules The Move modules to publish
     * @param array<string> $dependencies The dependencies required by the modules
     */
    public function __construct(array $modules, array $dependencies = [])
    {
        $this->modules = $modules;
        $this->dependencies = $dependencies;
    }

    /**
     * @return array<string>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @param array<string> $modules
     * @return self
     */
    public function setModules(array $modules): self
    {
        $this->modules = $modules;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @param array<string> $dependencies
     * @return self
     */
    public function setDependencies(array $dependencies): self
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'modules' => $this->modules,
            'dependencies' => $this->dependencies,
        ];
    }
}
