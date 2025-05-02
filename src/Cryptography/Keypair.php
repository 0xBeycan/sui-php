<?php

declare(strict_types=1);

namespace Sui\Cryptography;

use Sui\Utils;
use Sui\Bcs\Bcs;

use function BitWasp\Bech32\{
    encode,
    decode,
    convertBits
};

abstract class Keypair
{
    public const PRIVATE_KEY_SIZE = 32;
    public const LEGACY_PRIVATE_KEY_SIZE = 64;
    public const SUI_PRIVATE_KEY_PREFIX = 'suiprivkey';

    /**
     * @param array<int> $bytes
     * @return array<int>
     */
    abstract public function sign(array $bytes): array;


    /**
     * Get the key scheme of the keypair: Secp256k1 or ED25519
     * @return string
     */
    abstract public function getKeyScheme(): string;

    /**
     * The public key for this keypair
     * @return PublicKey
     */
    abstract public function getPublicKey(): PublicKey;

    /**
     * This returns the Bech32 secret key string for this keypair.
     * @return string
     */
    abstract public function getSecretKey(): string;

    /**
     * @param array<int> $bytes
     * @param string $intent
     * @return array<string, string>
     */
    public function signWithIntent(array $bytes, string $intent): array
    {
        $intentMessage = Helpers::messageWithIntent($intent, $bytes);
        $digest = Utils::blake2b($intentMessage, 32);

        $signature = Helpers::toSerializedSignature(
            $this->getKeyScheme(),
            $this->sign($digest),
            $this->getPublicKey()
        );

        return [
            'signature' => $signature,
            'bytes' => Utils::toBase64($bytes),
        ];
    }


    /**
     * Signs provided transaction by calling `signWithIntent()` with a `TransactionData` provided as intent scope
     * @param array<int> $bytes
     * @return array<string, string>
     */
    public function signTransaction(array $bytes): array
    {
        return $this->signWithIntent($bytes, 'TransactionData');
    }

    /**
     * Signs provided personal message by calling `signWithIntent()` with a `PersonalMessage` provided as intent scope
     * @param array<int> $bytes
     * @return array<string, string>
     */
    public function signPersonalMessage(array $bytes): array
    {
        $result = $this->signWithIntent(
            Bcs::vector(Bcs::u8())->serialize($bytes)->toArray(),
            'PersonalMessage',
        );

        return [
            'bytes' => Utils::toBase64($bytes),
            'signature' => $result['signature'],
        ];
    }

    /**
     * @return string
     */
    public function toSuiAddress(): string
    {
        return $this->getPublicKey()->toSuiAddress();
    }

    /**
     * @param string $value
     * @return array<string, mixed>
     */
    public static function decodeSuiPrivateKey(string $value): array
    {
        [$prefix, $words] = decode($value);
        if (self::SUI_PRIVATE_KEY_PREFIX !== $prefix) {
            throw new \Exception('invalid private key prefix');
        }
        $extendedSecretKey = convertBits($words, count($words), 5, 8, false);
        $secretKey = array_slice($extendedSecretKey, 1);
        $signatureScheme = Schema::SIGNATURE_FLAG_TO_SCHEME[$extendedSecretKey[0]];
        return [
            'schema' => $signatureScheme,
            'secretKey' => $secretKey,
        ];
    }

    /**
     * This returns a Bech32 encoded string starting with `suiprivkey`,
     * encoding 33-byte `flag || bytes` for the given the 32-byte private
     * key and its signature scheme.
     * @param array<int> $bytes
     * @param string $schema
     * @return string
     */
    public static function encodeSuiPrivateKey(array $bytes, string $schema): string
    {
        if (self::PRIVATE_KEY_SIZE !== count($bytes)) {
            throw new \Exception('Invalid bytes length');
        }
        $flag = Schema::SIGNATURE_SCHEME_TO_FLAG[$schema];
        $privKeyBytes = array_fill(0, count($bytes) + 1, 0);
        $privKeyBytes[0] = $flag;
        $privKeyBytes = array_merge([$flag], $bytes);
        return encode(
            self::SUI_PRIVATE_KEY_PREFIX,
            convertBits($privKeyBytes, count($privKeyBytes), 8, 5, true)
        );
    }
}
