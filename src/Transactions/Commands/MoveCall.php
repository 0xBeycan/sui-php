<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Argument;

class MoveCall extends Command
{
    private string $package;

    private string $module;

    private string $function;

    /**
     * @var array<string>
     */
    private array $typeArguments;

    /**
     * @var array<Argument>
     */
    private array $arguments;

    /**
     * @var array<TypeSignature>|null
     */
    private ?array $argumentTypes = null;

    /**
     * @param string $package
     * @param string $module
     * @param string $function
     * @param array<string> $typeArguments
     * @param array<Argument> $arguments
     * @param array<TypeSignature>|null $argumentTypes
     */
    public function __construct(
        string $package,
        string $module,
        string $function,
        array $typeArguments = [],
        array $arguments = [],
        ?array $argumentTypes = null
    ) {
        $this->package = $package;
        $this->module = $module;
        $this->function = $function;
        $this->typeArguments = $typeArguments;
        $this->arguments = $arguments;
        $this->argumentTypes = $argumentTypes;
    }

    /**
     * @return string
     */
    public function getPackage(): string
    {
        return $this->package;
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
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     * @param string $module
     * @return self
     */
    public function setModule(string $module): self
    {
        $this->module = $module;
        return $this;
    }

    /**
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * @param string $function
     * @return self
     */
    public function setFunction(string $function): self
    {
        $this->function = $function;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getTypeArguments(): array
    {
        return $this->typeArguments;
    }

    /**
     * @param array<string> $typeArguments
     * @return self
     */
    public function setTypeArguments(array $typeArguments): self
    {
        $this->typeArguments = $typeArguments;
        return $this;
    }

    /**
     * @return array<Argument>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array<Argument> $arguments
     * @return self
     */
    public function setArguments(array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return array<TypeSignature>|null
     */
    public function getArgumentTypes(): ?array
    {
        return $this->argumentTypes;
    }

    /**
     * @param array<TypeSignature>|null $argumentTypes
     * @return self
     */
    public function setArgumentTypes(?array $argumentTypes): self
    {
        $this->argumentTypes = $argumentTypes;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [
            'package' => $this->package,
            'module' => $this->module,
            'function' => $this->function,
            'typeArguments' => $this->typeArguments,
            'arguments' => array_map(fn(Argument $argument) => $argument->toArray(), $this->arguments),
        ];

        if (null !== $this->argumentTypes) {
            $result['argumentTypes'] = array_map(fn($type) => $type->toArray(), $this->argumentTypes);
        }

        return $result;
    }
}
