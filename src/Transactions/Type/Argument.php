<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class Argument
{
    private string $kind;

    private bool $gasCoin = false;

    private int $input;

    private string|object $type;

    private int $result;

    /**
     * @var array<int>
     */
    private array $nestedResult;

    /**
     * @param int $input
     * @param string $kind
     * @param string|object $type
     * @param int $result
     * @param array<int> $nestedResult
     * @param bool $gasCoin
     */
    public function __construct(
        int $input,
        string $kind,
        string|object $type,
        int $result,
        array $nestedResult = [],
        bool $gasCoin = false
    ) {
        $this->input = $input;
        $this->kind = $kind;
        $this->type = $type;
        $this->result = $result;
        $this->nestedResult = $nestedResult;
        $this->gasCoin = $gasCoin;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     * @return self
     */
    public function setKind(string $kind): self
    {
        $this->kind = $kind;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGasCoin(): bool
    {
        return $this->gasCoin;
    }

    /**
     * @param bool $gasCoin
     * @return self
     */
    public function setGasCoin(bool $gasCoin): self
    {
        $this->gasCoin = $gasCoin;
        return $this;
    }

    /**
     * @return int
     */
    public function getInput(): int
    {
        return $this->input;
    }

    /**
     * @param int $input
     * @return self
     */
    public function setInput(int $input): self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * @return string|object
     */
    public function getType(): string|object
    {
        return $this->type;
    }

    /**
     * @param string|object $type
     * @return self
     */
    public function setType(string|object $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getResult(): int
    {
        return $this->result;
    }

    /**
     * @param int $result
     * @return self
     */
    public function setResult(int $result): self
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return array<int>
     */
    public function getNestedResult(): array
    {
        return $this->nestedResult;
    }

    /**
     * @param array<int> $nestedResult
     * @return self
     */
    public function setNestedResult(array $nestedResult): self
    {
        $this->nestedResult = $nestedResult;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'gasCoin' => $this->gasCoin,
            'input' => $this->input,
            'type' => $this->type,
            'result' => $this->result,
            'nestedResult' => $this->nestedResult,
        ];
    }
}
