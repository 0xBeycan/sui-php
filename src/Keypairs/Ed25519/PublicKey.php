<?php

declare(strict_types=1);

namespace Sui\Keypairs\Ed25519;

use Sui\Cryptography\PublicKey as BasePublicKey;
use ParagonIE_Sodium_Compat;
use Sui\Cryptography\Schema;
use Sui\Utils;

/**
 * An Ed25519 public key
 */
class PublicKey extends BasePublicKey
{
    /** @var int Size of the Ed25519 public key in bytes */
    public const SIZE = 32;

    /** @var array<int> The raw bytes of the public key */
    private array $data;

    /**
     * Create a new Ed25519PublicKey object
     *
     * @param array<int>|string $value Ed25519 public key as array of bytes or base-64 encoded string
     * @throws \Exception If the public key size is invalid
     */
    public function __construct(array|string $value)
    {
        if (is_string($value)) {
            $this->data = Utils::fromBase64($value);
        } else {
            $this->data = $value;
        }

        if (self::SIZE !== count($this->data)) {
            throw new \Exception(
                sprintf(
                    'Invalid public key input. Expected %d bytes, got %d',
                    self::SIZE,
                    count($this->data)
                )
            );
        }
    }

    /**
     * Return the byte array representation of the Ed25519 public key
     *
     * @return array<int> The raw bytes of the public key
     */
    public function toRawBytes(): array
    {
        return $this->data;
    }

    /**
     * Return the Sui address associated with this Ed25519 public key
     *
     * @return int The signature scheme flag
     */
    public function flag(): int
    {
        return Schema::SIGNATURE_SCHEME_TO_FLAG['ED25519'];
    }

    /**
     * Verifies that the signature is valid for for the provided message
     *
     * @param array<int> $message The message to verify
     * @param array<int>|string $signature The signature to verify against
     * @return bool True if the signature is valid, false otherwise
     * @throws \Exception If the signature scheme is invalid or signature does not match public key
     */
    public function verify(array $message, array|string $signature): bool
    {
        $bytes = $signature;
        if (is_string($signature)) {
            $parsed = self::parseSerializedKeypairSignature($signature);
            if ('ED25519' !== $parsed['signatureScheme']) {
                throw new \Exception('Invalid signature scheme');
            }

            if (!self::bytesEqual($this->toRawBytes(), $parsed['publicKey'])) {
                throw new \Exception('Signature does not match public key');
            }

            $bytes = $parsed['signature'];
        }


        // @phpstan-ignore-next-line
        $bytesStr = implode('', array_map('chr', $bytes));
        $messageBytes = implode('', array_map('chr', $message));
        $publicKeyBytes = implode('', array_map('chr', $this->toRawBytes()));

        return ParagonIE_Sodium_Compat::crypto_sign_verify_detached($bytesStr, $messageBytes, $publicKeyBytes);
    }
}
