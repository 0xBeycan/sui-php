<?php

declare(strict_types=1);

namespace Sui;

use Sui\Utils;
use Sui\Cryptography\Schema;
use Sui\Cryptography\Helpers;
use Sui\Cryptography\PublicKey;
use Sui\Keypairs\Ed25519\PublicKey as Ed25519PublicKey;

class Verify
{
    /**
     * @param string $signatureScheme
     * @param array<int> $bytes
     * @param array<string, mixed> $options
     * @return PublicKey
     * @throws \Exception
     */
    public static function publicKeyFromRawBytes(
        string $signatureScheme,
        array $bytes,
        array $options = [],
    ): PublicKey {
        switch ($signatureScheme) {
            case 'ED25519':
                return new Ed25519PublicKey($bytes);
            case 'Secp256k1':
                // TODO: Implement
                throw new \Exception('Secp256k1 signatures are not supported');
            case 'Secp256r1':
                // TODO: Implement
                throw new \Exception('Secp256r1 signatures are not supported');
            case 'MultiSig':
                // TODO: Implement
                throw new \Exception('MultiSig signatures are not supported');
            case 'ZkLogin':
                // TODO: Implement
                throw new \Exception('ZkLogin signatures are not supported');
            case 'Passkey':
                // TODO: Implement
                throw new \Exception('Passkey signatures are not supported');
            default:
                throw new \Exception("Unsupported signature scheme {$signatureScheme}");
        }
    }

    /**
     * @param string|array<int> $publicKey
     * @param array<string, mixed> $options
     * @return PublicKey
     * @throws \Exception
     */
    public static function publicKeyFromSuiBytes(
        string|array $publicKey,
        array $options = [],
    ): PublicKey {
        $bytes = is_string($publicKey) ? Utils::fromBase64($publicKey) : $publicKey;

        $signatureScheme = Schema::SIGNATURE_FLAG_TO_SCHEME[$bytes[0]];

        return self::publicKeyFromRawBytes($signatureScheme, array_slice($bytes, 1), $options);
    }

    /**
     * @param string $signature
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     * @throws \Exception
     */
    public static function parseSignature(
        string $signature,
        array $options = [],
    ): array {
        $parsedSignature = Helpers::parseSerializedSignature($signature);

        if ('MultiSig' === $parsedSignature['signatureScheme']) {
            throw new \Exception('MultiSig signatures are not supported');
        }

        $publicKey = self::publicKeyFromRawBytes(
            $parsedSignature['signatureScheme'],
            $parsedSignature['publicKey'],
            $options,
        );
        return [
            ...$parsedSignature,
            'publicKey' => $publicKey,
        ];
    }

    /**
     * @param array<int> $transaction
     * @param string $signature
     * @param array<string, mixed> $options
     * @return PublicKey
     * @throws \Exception
     */
    public static function verifyTransactionSignature(
        array $transaction,
        string $signature,
        array $options = [],
    ): PublicKey {
        $parsedSignature = self::parseSignature($signature, $options);
        /** @var PublicKey $publicKey */
        $publicKey = $parsedSignature['publicKey'];

        if (
            !$publicKey->verifyTransaction(
                $transaction,
                $parsedSignature['serializedSignature'],
            )
        ) {
            throw new \Exception('Signature is not valid for the provided Transaction');
        }

        if (
            isset($options['address']) &&
            !$publicKey->verifyAddress($options['address'])
        ) {
            throw new \Exception('Signature is not valid for the provided address');
        }

        return $parsedSignature['publicKey'];
    }

    /**
     * @param array<int> $message
     * @param string $signature
     * @param array<string, mixed> $options
     * @return PublicKey
     * @throws \Exception
     */
    public static function verifyPersonalMessageSignature(
        array $message,
        string $signature,
        array $options = [],
    ): PublicKey {
        $parsedSignature = self::parseSignature($signature, $options);
        /** @var PublicKey $publicKey */
        $publicKey = $parsedSignature['publicKey'];

        if (
            !$publicKey->verifyPersonalMessage(
                $message,
                $parsedSignature['serializedSignature'],
            )
        ) {
            throw new \Exception('Signature is not valid for the provided message');
        }

        if (
            isset($options['address']) &&
            !$publicKey->verifyAddress($options['address'])
        ) {
            throw new \Exception('Signature is not valid for the provided address');
        }

        return $parsedSignature['publicKey'];
    }

    /**
     * @param array<int> $bytes
     * @param string $signature
     * @param array<string, mixed> $options
     * @return PublicKey
     * @throws \Exception
     */
    public static function verifySignature(
        array $bytes,
        string $signature,
        array $options = [],
    ): PublicKey {
        $parsedSignature = self::parseSignature($signature);
        /** @var PublicKey $publicKey */
        $publicKey = $parsedSignature['publicKey'];

        if (
            !$publicKey->verify(
                $bytes,
                $parsedSignature['serializedSignature'],
            )
        ) {
            throw new \Exception('Signature is not valid for the provided data');
        }

        if (
            isset($options['address']) &&
            !$publicKey->verifyAddress($options['address'])
        ) {
            throw new \Exception('Signature is not valid for the provided address');
        }

        return $parsedSignature['publicKey'];
    }
}
