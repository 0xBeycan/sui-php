<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiActiveJwk
{
    public string $epoch;

    public SuiJWK $jwk;

    public SuiJwkId $jwkId;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->epoch = $data['epoch'];
        $this->jwk = new SuiJWK($data['jwk']);
        $this->jwkId = new SuiJwkId($data['jwk_id']);
    }
}
