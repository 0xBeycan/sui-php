<?php

declare(strict_types=1);

namespace Sui\Keypairs\Ed25519;

class HdKey
{
    private const ED25519_CURVE = 'ed25519 seed';
    private const HARDENED_OFFSET = 0x80000000;
    private const PATH_REGEX = '/^m(\/[0-9]+\')+$/';

    /**
     * Derives a key from a seed using the specified path
     *
     * @param string $path The derivation path (e.g. "m/44'/784'/0'/0'/0'")
     * @param string $seed The seed in hex format
     * @param int $offset The hardened offset (default: 0x80000000)
     * @return array{key: string, chainCode: string} The derived key and chain code
     * @throws \InvalidArgumentException If the path is invalid
     * @throws \ValueError If the seed is invalid
     */
    public static function derivePath(string $path, string $seed, int $offset = self::HARDENED_OFFSET): array
    {
        if (!self::isValidPath($path)) {
            throw new \InvalidArgumentException('Invalid derivation path');
        }

        $masterKeys = self::getMasterKeyFromSeed($seed);
        $segments = self::getPathSegments($path);

        return array_reduce(
            $segments,
            fn($parentKeys, $segment) => self::ckdPriv($parentKeys, $segment + $offset),
            $masterKeys
        );
    }

    /**
     * Gets the master key from a seed
     *
     * @param string $seed The seed in hex format
     * @return array{key: string, chainCode: string} The master key and chain code
     * @throws \ValueError If the seed is invalid
     */
    private static function getMasterKeyFromSeed(string $seed): array
    {
        if (empty($seed)) {
            throw new \ValueError('Seed cannot be empty');
        }

        if (!ctype_xdigit($seed)) {
            throw new \ValueError('Seed must be a valid hexadecimal string');
        }

        if (0 !== strlen($seed) % 2) {
            throw new \ValueError('Seed must have an even length');
        }

        $binarySeed = hex2bin($seed);
        if (false === $binarySeed) {
            throw new \ValueError('Invalid seed format');
        }

        $h = hash_hmac('sha512', $binarySeed, self::ED25519_CURVE, true);
        return [
            'key' => substr($h, 0, 32),
            'chainCode' => substr($h, 32),
        ];
    }

    /**
     * Performs child key derivation
     *
     * @param array{key: string, chainCode: string} $parentKeys The parent key and chain code
     * @param int $index The index to derive
     * @return array{key: string, chainCode: string} The derived key and chain code
     */
    private static function ckdPriv(array $parentKeys, int $index): array
    {
        $indexBuffer = pack('N', $index);
        $data = "\x00" . $parentKeys['key'] . $indexBuffer;

        $h = hash_hmac('sha512', $data, $parentKeys['chainCode'], true);
        return [
            'key' => substr($h, 0, 32),
            'chainCode' => substr($h, 32),
        ];
    }

    /**
     * Validates a derivation path
     *
     * @param string $path The path to validate
     * @return bool True if the path is valid
     */
    private static function isValidPath(string $path): bool
    {
        if (!preg_match(self::PATH_REGEX, $path)) {
            return false;
        }

        $segments = array_slice(explode('/', $path), 1);
        foreach ($segments as $segment) {
            $segment = str_replace("'", '', $segment);
            if (!is_numeric($segment)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the segments from a derivation path
     *
     * @param string $path The derivation path
     * @return array<int> The path segments
     */
    private static function getPathSegments(string $path): array
    {
        return array_map(
            fn($segment) => (int) str_replace("'", '', $segment),
            array_slice(explode('/', $path), 1)
        );
    }
}
