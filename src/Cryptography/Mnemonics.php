<?php

declare(strict_types=1);

namespace Sui\Cryptography;

use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP39\Language\English;

class Mnemonics
{
    /**
     * Convert a mnemonic to a seed
     *
     * @param string $mnemonic The mnemonic to convert
     * @return string The seed
     */
    public static function mnemonicToSeed(string $mnemonic): string
    {
        return BIP39::fromMnemonic(explode(' ', $mnemonic), English::getInstance())->generateSeed();
    }

    /**
     * Parse and validate a path that is compliant
     * to SLIP-0010 in form m/44'/784'/{account_index}'/{change_index}'/{address_index}'.
     *
     * @param string $path path string (e.g. `m/44'/784'/0'/0'/0'`).
     * @return bool Whether the path is valid
     */
    public static function isValidHardenedPath(string $path): bool
    {
        return (bool) preg_match("/^m\/44'\/784'\/[0-9]+'\/[0-9]+'\/[0-9]+'+$/", $path);
    }

    /**
     * Parse and validate a path that is compliant
     * to BIP-32 in form m/54'/784'/{account_index}'/{change_index}/{address_index}
     * for Secp256k1 and m/74'/784'/{account_index}'/{change_index}/{address_index}
     * for Secp256r1.
     *
     * Note that the purpose for Secp256k1 is registered as 54, to differentiate from Ed25519 with purpose 44.
     *
     * @param string $path path string (e.g. `m/54'/784'/0'/0/0`).
     * @return bool Whether the path is valid
     */
    public static function isValidBIP32Path(string $path): bool
    {
        return (bool) preg_match("/^m\/(54|74)'\/784'\/[0-9]+'\/[0-9]+\/[0-9]+$/", $path);
    }

    /**
     * Derive the seed in hex format from a 12-word mnemonic string.
     *
     * @param string $mnemonic 12 words string split by spaces.
     * @return string The seed in hex format
     */
    public static function mnemonicToSeedHex(string $mnemonic): string
    {
        return bin2hex(self::mnemonicToSeed($mnemonic));
    }
}
