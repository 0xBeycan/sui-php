<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class Upgrade extends Command
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
     * @var string
     */
    private string $package;

    /**
     * @var Argument
     */
    private Argument $ticket;

    /**
     * @param array<string> $modules
     * @param array<string> $dependencies
     * @param string $package
     * @param Argument $ticket
     */
    public function __construct(
        array $modules,
        array $dependencies,
        string $package,
        Argument $ticket
    ) {
        $this->modules = $modules;
        $this->dependencies = $dependencies;
        $this->package = $package;
        $this->ticket = $ticket;
    }

    /**
     * @return array<string>
     */
    public function getModules(): array
    {
        return $this->modules;
    }

    /**
     * @return array<string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @return string
     */
    public function getPackage(): string
    {
        return $this->package;
    }

    /**
     * @return Argument
     */
    public function getTicket(): Argument
    {
        return $this->ticket;
    }

    /**
     * @param Argument $ticket
     * @return self
     */
    public function setTicket(Argument $ticket): self
    {
        $this->ticket = $ticket;
        return $this;
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
     * @param array<string> $dependencies
     * @return self
     */
    public function setDependencies(array $dependencies): self
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    /**
     * @param string $package
     * @return self
     */
    public function setPackage(string $package): self
    {
        $this->package = $package;
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
            'package' => $this->package,
            'ticket' => $this->ticket->toArray(),
        ];
    }
}
