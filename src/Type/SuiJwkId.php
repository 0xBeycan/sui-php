<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiJwkId
{
    public string $iss;

    public string $kid;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->iss = $data['iss'];
        $this->kid = $data['kid'];
    }
}
