<?php

declare(strict_types=1);

namespace Sui\Keypairs;

/**
 * the secretkey is 64-byte, where the first 32 bytes is the secret
 * key and the last 32 bytes is the public key.
 */
class KeypairData
{
    /** @var array<int> */
    public array $publicKey;
    /** @var array<int> */
    public array $secretKey;

    /**
     * @param array<int> $publicKey
     * @param array<int> $secretKey
     */
    public function __construct(array $publicKey, array $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }
}
