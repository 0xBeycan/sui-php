<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class StructTypeParameter
{
    /**
     * @var array<mixed>
     */
    public array $constraints;

    public bool $isPhantom;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->constraints = $data['constraints'] ?? [];
        $this->isPhantom = (bool) ($data['isPhantom'] ?? false);
    }
}
