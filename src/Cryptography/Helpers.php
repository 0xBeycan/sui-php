<?php

declare(strict_types=1);

namespace Sui\Cryptography;

use Sui\Bcs\Map;
use Sui\Bcs\Bcs;
use Sui\Utils;

class Helpers
{
    /**
     * Inserts a domain separator for a message that is being signed
     *
     * @param string $scope
     * @param array<int> $message
     * @return string
     */
    public static function messageWithIntent(string $scope, array $message): string
    {
        return Map::intentMessage(Bcs::fixedArray(count($message), Bcs::u8()))
            ->serialize([
                'intent' => [
                    'scope' => [
                        $scope => true,
                    ],
                    'version' => [
                        'V0' => true,
                    ],
                    'appId' => [
                        'Sui' => true,
                    ],
                ],
                'value' => $message,
            ])
            ->toBytes();
    }

    /**
     * Serializes a signature into a base64 encoded string
     *
     * @param string $signatureScheme The signature scheme
     * @param array<int> $signature The signature
     * @param PublicKey $publicKey The public key
     * @return string The serialized signature
     */
    public static function toSerializedSignature(
        string $signatureScheme,
        array $signature,
        PublicKey $publicKey
    ): string {
        $pubKeyBytes = $publicKey->toRawBytes();
        $serializedSignature = [];
        $serializedSignature[] = Schema::SIGNATURE_SCHEME_TO_FLAG[$signatureScheme];
        $serializedSignature = array_merge($serializedSignature, $signature);
        $serializedSignature = array_merge($serializedSignature, $pubKeyBytes);
        return Utils::toBase64($serializedSignature);
    }

    /**
     * Parses a serialized signature into an array
     *
     * @param string $serializedSignature The serialized signature
     * @return array<string, mixed> The parsed signature
     */
    public static function parseSerializedSignature(string $serializedSignature): array
    {
        $bytes = Utils::fromBase64($serializedSignature);
        $signatureScheme = Schema::SIGNATURE_FLAG_TO_SCHEME[$bytes[0]];
        switch ($signatureScheme) {
            case 'Passkey':
                return []; // TODO: Implement
            case 'MultiSig':
                $multisig = Map::multiSig()->parse(array_slice($bytes, 1));
                return [
                    'serializedSignature' => $serializedSignature,
                    'signatureScheme' => $signatureScheme,
                    'multisig' => $multisig,
                    'bytes' => $bytes,
                    'signature' => null,
                ];
            case 'ZkLogin':
                return []; // TODO: Implement
            case 'ED25519':
            case 'Secp256k1':
            case 'Secp256r1':
                return PublicKey::parseSerializedKeypairSignature($serializedSignature);
            default:
                throw new \Exception('Unsupported signature scheme');
        }
    }
}
