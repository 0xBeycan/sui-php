<?php

declare(strict_types=1);

namespace Sui\Type;

class MoveCallSuiTransaction
{
    /**
     * @var array<SuiArgument>|null
     */
    public ?array $arguments;

    public string $function;

    public string $module;

    public string $package;

    /**
     * @var array<string>|null
     */
    public ?array $typeArguments;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->arguments = array_map(
            static fn(array $item) => new SuiArgument($item),
            $data['arguments'] ?? []
        );
        $this->function = $data['function'];
        $this->module = $data['module'];
        $this->package = $data['package'];
        $this->typeArguments = $data['type_arguments'] ?? null;
    }
}
