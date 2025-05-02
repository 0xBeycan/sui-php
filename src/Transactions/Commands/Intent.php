<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class Intent extends Command
{
    /**
     * @var string
     */
    private string $name;

    /**
     * @var array<string, Argument|array<Argument>>
     */
    private array $inputs;

    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @param string $name
     * @param array<string, Argument|array<Argument>> $inputs
     * @param array<string, mixed> $data
     */
    public function __construct(string $name, array $inputs, array $data)
    {
        $this->name = $name;
        $this->inputs = $inputs;
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array<string, Argument|array<Argument>>
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @param array<string, Argument|array<Argument>> $inputs
     * @return void
     */
    public function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'inputs' => $this->inputs,
            'data' => $this->data,
        ];
    }
}
