<?php

declare(strict_types=1);

namespace Sui\Cryptography;

use Sui\Utils;
use Sui\Bcs\Bcs;
use Sui\Constants;

/**
 * A public key
 */
abstract class PublicKey
{
    /**
     * Checks if two byte arrays are equal
     *
     * @param array<int> $a First byte array
     * @param array<int> $b Second byte array
     * @return bool True if the arrays are equal, false otherwise
     */
    protected static function bytesEqual(array $a, array $b): bool
    {
        if ($a === $b) {
            return true;
        }

        if (count($a) !== count($b)) {
            return false;
        }

        for ($i = 0; $i < count($a); $i++) {
            if ($a[$i] !== $b[$i]) {
                return false;
            }
        }
        return true;
    }

    /**
     * Parses a serialized keypair signature
     *
     * @param string $serializedSignature The serialized signature to parse
     * @return array{
     *     serializedSignature: string,
     *     signatureScheme: string,
     *     signature: array<int>,
     *     publicKey: array<int>,
     *     bytes: array<int>
     * } The parsed signature data
     * @throws \Exception If the signature scheme is not supported
     */
    public static function parseSerializedKeypairSignature(string $serializedSignature): array
    {
        $bytes = Utils::fromBase64($serializedSignature);

        $signatureScheme = Schema::SIGNATURE_FLAG_TO_SCHEME[$bytes[0]] ?? null;

        if (!$signatureScheme) {
            throw new \Exception('Unsupported signature scheme');
        }

        switch ($signatureScheme) {
            case 'ED25519':
            case 'Secp256k1':
            case 'Secp256r1':
                $size = Schema::SIGNATURE_SCHEME_TO_SIZE[$signatureScheme];
                $signature = array_slice($bytes, 1, count($bytes) - $size - 1);
                $publicKey = array_slice($bytes, 1 + count($signature));

                return [
                    'serializedSignature' => $serializedSignature,
                    'signatureScheme' => $signatureScheme,
                    'signature' => $signature,
                    'publicKey' => $publicKey,
                    'bytes' => $bytes,
                ];
            default:
                throw new \Exception('Unsupported signature scheme');
        }
    }

    /**
     * Checks if two public keys are equal
     *
     * @param PublicKey $publicKey The public key to compare with
     * @return bool True if the public keys are equal, false otherwise
     */
    public function equals(PublicKey $publicKey): bool
    {
        return self::bytesEqual($this->toRawBytes(), $publicKey->toRawBytes());
    }

    /**
     * Return the base-64 representation of the public key
     *
     * @return string The base-64 encoded public key
     */
    public function toBase64(): string
    {
        return Utils::toBase64($this->toRawBytes());
    }

    /**
     * @throws \Exception Always throws an exception as toString is not implemented
     * @return never
     */
    public function __toString(): never
    {
        throw new \Exception(
            '`toString` is not implemented on public keys. Use `toBase64()` or `toRawBytes()` instead.'
        );
    }

    /**
     * Return the Sui representation of the public key encoded in base-64
     *
     * @return string The base-64 encoded Sui public key
     */
    public function toSuiPublicKey(): string
    {
        $bytes = $this->toSuiBytes();
        return Utils::toBase64($bytes);
    }

    /**
     * Verifies a message with intent
     *
     * @param array<int> $bytes The message bytes to verify
     * @param array<int>|string $signature The signature to verify against
     * @param string $intent The intent scope
     * @return bool True if the signature is valid, false otherwise
     */
    public function verifyWithIntent(
        array $bytes,
        array|string $signature,
        string $intent
    ): bool {
        $intentMessage = Helpers::messageWithIntent($intent, $bytes);
        $digest = Utils::blake2b($intentMessage, 32);
        return $this->verify($digest, $signature);
    }

    /**
     * Verifies that the signature is valid for for the provided PersonalMessage
     *
     * @param array<int> $message The message to verify
     * @param array<int>|string $signature The signature to verify against
     * @return bool True if the signature is valid, false otherwise
     */
    public function verifyPersonalMessage(array $message, array|string $signature): bool
    {
        return $this->verifyWithIntent(
            Bcs::vector(Bcs::u8())->serialize($message)->toArray(),
            $signature,
            'PersonalMessage'
        );
    }

    /**
     * Verifies that the signature is valid for for the provided Transaction
     *
     * @param array<int> $transaction The transaction to verify
     * @param array<int>|string $signature The signature to verify against
     * @return bool True if the signature is valid, false otherwise
     */
    public function verifyTransaction(array $transaction, array|string $signature): bool
    {
        return $this->verifyWithIntent($transaction, $signature, 'TransactionData');
    }

    /**
     * Verifies that the public key is associated with the provided address
     *
     * @param string $address The address to verify
     * @return bool True if the address matches the public key, false otherwise
     */
    public function verifyAddress(string $address): bool
    {
        return $this->toSuiAddress() === $address;
    }

    /**
     * Returns the bytes representation of the public key prefixed with the signature scheme flag
     *
     * @return array<int> The Sui bytes representation of the public key
     */
    public function toSuiBytes(): array
    {
        $rawBytes = $this->toRawBytes();
        $suiBytes = array_merge([$this->flag()], $rawBytes);
        return $suiBytes;
    }

    /**
     * Return the Sui address associated with this public key
     *
     * @return string The Sui address
     */
    public function toSuiAddress(): string
    {
        $suiBytes = $this->toSuiBytes();
        $bytesString = implode('', array_map('chr', $suiBytes));
        $hash = Utils::blake2b($bytesString, 32);
        $hex = Utils::bytesToHex($hash);
        return Utils::normalizeSuiAddress(substr($hex, 0, Constants::SUI_ADDRESS_LENGTH * 2));
    }

    /**
     * Return the byte array representation of the public key
     *
     * @return array<int> The raw bytes of the public key
     */
    abstract public function toRawBytes(): array;

    /**
     * Return signature scheme flag of the public key
     *
     * @return int The signature scheme flag
     */
    abstract public function flag(): int;

    /**
     * Verifies that the signature is valid for for the provided message
     *
     * @param array<int> $data The data to verify
     * @param array<int>|string $signature The signature to verify against
     * @return bool True if the signature is valid, false otherwise
     */
    abstract public function verify(array $data, array|string $signature): bool;
}
