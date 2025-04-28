<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Argument;

class MakeMoveVec extends Command
{
    private ?string $type;

    /**
     * @var array<Argument>
     */
    private array $elements;

    /**
     * @param array<Argument> $elements
     * @param string|null $type
     */
    public function __construct(array $elements, ?string $type = null)
    {
        $this->elements = $elements;
        $this->type = $type;
    }

    /**
     * @return array<Argument>
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * @param array<Argument> $elements
     * @return self
     */
    public function setElements(array $elements): self
    {
        $this->elements = $elements;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return self
     */
    public function setType(?string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'elements' => array_map(fn(Argument $element) => $element->toArray(), $this->elements),
            'type' => $this->type,
        ];
    }
}
