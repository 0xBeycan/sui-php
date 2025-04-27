<?php

declare(strict_types=1);

namespace Sui\Type\Move;

class StructTypeParameter
{
    public AbilitySet $constraints;

    public bool $isPhantom;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->constraints = new AbilitySet($data['constraints'] ?? []);
        $this->isPhantom = (bool) ($data['isPhantom'] ?? false);
    }
}
