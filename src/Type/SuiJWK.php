<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiJWK
{
    public string $alg;

    public string $e;

    public string $kty;

    public string $n;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->alg = $data['alg'];
        $this->e = $data['e'];
        $this->kty = $data['kty'];
        $this->n = $data['n'];
    }
}
