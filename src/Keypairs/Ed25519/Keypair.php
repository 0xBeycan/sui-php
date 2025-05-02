<?php

declare(strict_types=1);

namespace Sui\Keypairs\Ed25519;

use Sui\Utils;
use ParagonIE_Sodium_Compat;
use Sui\Keypairs\KeypairData;
use Sui\Cryptography\Mnemonics;
use Sui\Cryptography\Keypair as BaseKeypair;

/**
 * An Ed25519 Keypair used for signing transactions.
 */
class Keypair extends BaseKeypair
{
    /** @var KeypairData */
    private KeypairData $keypair;

    /**
     * Create a new Ed25519 keypair instance.
     * Generate random keypair if no {@link KeypairData} is provided.
     *
     * @param KeypairData|null $keypair Ed25519 keypair
     */
    public function __construct(?KeypairData $keypair = null)
    {
        if ($keypair) {
            $this->keypair = new KeypairData(
                $keypair->publicKey,
                array_slice($keypair->secretKey, 0, self::PRIVATE_KEY_SIZE)
            );
        } else {
            $this->keypair = self::generateData();
        }
    }

    /**
     * Get the key scheme of the keypair ED25519
     *
     * @return string
     */
    public function getKeyScheme(): string
    {
        return 'ED25519';
    }

    /**
     * Generate a new random Ed25519 keypair
     *
     * @return KeypairData
     */
    private static function generateData(): KeypairData
    {
        $keypair = ParagonIE_Sodium_Compat::crypto_sign_keypair();
        $publicKey = ParagonIE_Sodium_Compat::crypto_sign_publickey($keypair);
        $secretKey = ParagonIE_Sodium_Compat::crypto_sign_secretkey($keypair);
        return new KeypairData(
            self::stringToBytes($publicKey),
            self::stringToBytes($secretKey)
        );
    }

    /**
     * Convert a sodium string to a byte array
     *
     * @param string $string
     * @return array<int>
     */
    private static function stringToBytes(string $string): array
    {
        return array_values(unpack('C*', $string) ?: []);
    }

    /**
     * Generate a new random Ed25519 keypair
     *
     * @return self
     */
    public static function generate(): self
    {
        return new self(self::generateData());
    }

    /**
     * Create a Ed25519 keypair from a raw secret key byte array, also known as seed.
     * This is NOT the private scalar which is result of hashing and bit clamping of
     * the raw secret key.
     *
     * @throws \Exception if the provided secret key is invalid and validation is not skipped.
     *
     * @param array<int>|string $secretKey secret key as a byte array or Bech32 secret key string
     * @param array{skipValidation?: bool} $options skip secret key validation
     * @return self
     */
    public static function fromSecretKey(
        string|array $secretKey,
        array $options = []
    ): self {
        if (is_string($secretKey)) {
            $decoded = self::decodeSuiPrivateKey($secretKey);

            if ('ED25519' !== $decoded['schema']) {
                throw new \Exception(sprintf('Expected a ED25519 keypair, got %s', $decoded['schema']));
            }

            return self::fromSecretKey($decoded['secretKey'], $options);
        }

        $secretKeyLength = count($secretKey);
        if (self::PRIVATE_KEY_SIZE !== $secretKeyLength) {
            throw new \Exception(
                sprintf(
                    'Wrong secretKey size. Expected %d bytes, got %d.',
                    self::PRIVATE_KEY_SIZE,
                    $secretKeyLength
                )
            );
        }

        // Convert secret key to sodium format
        $strSecretKey = implode('', array_map('chr', $secretKey));
        $keypair = ParagonIE_Sodium_Compat::crypto_sign_seed_keypair($strSecretKey);

        // Get the public key from the keypair
        $publicKey = ParagonIE_Sodium_Compat::crypto_sign_publickey($keypair);

        // Create KeypairData with the correct format
        $keypairData = new KeypairData(
            self::stringToBytes($publicKey),
            self::stringToBytes(ParagonIE_Sodium_Compat::crypto_sign_secretkey($keypair))
        );

        if (!isset($options['skipValidation']) || !$options['skipValidation']) {
            $signData = 'sui validation';
            $signature = ParagonIE_Sodium_Compat::crypto_sign_detached(
                $signData,
                ParagonIE_Sodium_Compat::crypto_sign_secretkey($keypair)
            );
            if (
                !ParagonIE_Sodium_Compat::crypto_sign_verify_detached(
                    $signature,
                    $signData,
                    $publicKey
                )
            ) {
                throw new \Exception('provided secretKey is invalid');
            }
        }

        return new self($keypairData);
    }

    /**
     * The public key for this Ed25519 keypair
     *
     * @return PublicKey
     */
    public function getPublicKey(): PublicKey
    {
        return new PublicKey($this->keypair->publicKey);
    }

    /**
     * The Bech32 secret key string for this Ed25519 keypair
     *
     * @return string
     */
    public function getSecretKey(): string
    {
        return self::encodeSuiPrivateKey(
            array_slice($this->keypair->secretKey, 0, self::PRIVATE_KEY_SIZE),
            $this->getKeyScheme()
        );
    }

    /**
     * Return the signature for the provided data using Ed25519.
     *
     * @param array<int> $data
     * @return array<int>
     */
    public function sign(array $data): array
    {
        $message = implode('', array_map('chr', $data));
        $secretKey = ParagonIE_Sodium_Compat::crypto_sign_secretkey(
            ParagonIE_Sodium_Compat::crypto_sign_seed_keypair(
                implode('', array_map('chr', array_slice($this->keypair->secretKey, 0, self::PRIVATE_KEY_SIZE)))
            )
        );
        $signature = ParagonIE_Sodium_Compat::crypto_sign_detached($message, $secretKey);
        return array_values(unpack('C*', $signature));
    }

    /**
     * Derive Ed25519 keypair from mnemonics and path. The mnemonics must be normalized
     * and validated against the english wordlist.
     *
     * If path is none, it will default to m/44'/784'/0'/0'/0', otherwise the path must
     * be compliant to SLIP-0010 in form m/44'/784'/{account_index}'/{change_index}'/{address_index}'.
     *
     * @param string $mnemonics
     * @param string|null $path
     * @return self
     * @throws \Exception
     */
    public static function deriveKeypair(string $mnemonics, ?string $path = null): self
    {
        if (null === $path) {
            $path = "m/44'/784'/0'/0'/0'";
        }

        if (!Mnemonics::isValidHardenedPath($path)) {
            throw new \Exception('Invalid derivation path');
        }

        $seed = Mnemonics::mnemonicToSeedHex($mnemonics);
        $derivedKey = HdKey::derivePath($path, $seed)['key'];
        $keyBytes = array_values(unpack('C*', $derivedKey));

        return self::fromSecretKey($keyBytes);
    }

    /**
     * Derive Ed25519 keypair from mnemonicSeed and path.
     *
     * If path is none, it will default to m/44'/784'/0'/0'/0', otherwise the path must
     * be compliant to SLIP-0010 in form m/44'/784'/{account_index}'/{change_index}'/{address_index}'.
     *
     * @param string $seedHex
     * @param string|null $path
     * @return self
     * @throws \Exception
     */
    public static function deriveKeypairFromSeed(string $seedHex, ?string $path = null): self
    {
        if (null === $path) {
            $path = "m/44'/784'/0'/0'/0'";
        }

        if (!Mnemonics::isValidHardenedPath($path)) {
            throw new \Exception('Invalid derivation path');
        }

        $key = HdKey::derivePath($path, $seedHex)['key'];
        $keyBytes = array_values(unpack('C*', $key));

        return self::fromSecretKey($keyBytes);
    }
}
