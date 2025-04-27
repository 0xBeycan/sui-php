<?php

declare(strict_types=1);

namespace Sui\Type;

class ValidatorApy
{
    public string $address;

    public float $apy;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->address = $data['address'];
        $this->apy = (float)$data['apy'];
    }
}
