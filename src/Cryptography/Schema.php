<?php

declare(strict_types=1);

namespace Sui\Cryptography;

class Schema
{
    // Signature scheme types
    public const SCHEME_ED25519 = [
        'name' => 'ED25519',
        'flag' => 0x00,
        'size' => 32,
    ];
    public const SCHEME_SECP256K1 = [
        'name' => 'Secp256k1',
        'flag' => 0x01,
        'size' => 33,
    ];
    public const SCHEME_SECP256R1 = [
        'name' => 'Secp256r1',
        'flag' => 0x02,
        'size' => 33,
    ];
    public const SCHEME_MULTISIG = [
        'name' => 'MultiSig',
        'flag' => 0x03,
    ];
    public const SCHEME_ZKLOGIN = [
        'name' => 'ZkLogin',
        'flag' => 0x05,
    ];
    public const SCHEME_PASSKEY = [
        'name' => 'Passkey',
        'flag' => 0x06,
    ];

    // Signature scheme flags
    public const SIGNATURE_SCHEME_TO_FLAG = [
        self::SCHEME_ED25519['name'] => self::SCHEME_ED25519['flag'],
        self::SCHEME_SECP256K1['name'] => self::SCHEME_SECP256K1['flag'],
        self::SCHEME_SECP256R1['name'] => self::SCHEME_SECP256R1['flag'],
        self::SCHEME_MULTISIG['name'] => self::SCHEME_MULTISIG['flag'],
        self::SCHEME_ZKLOGIN['name'] => self::SCHEME_ZKLOGIN['flag'],
        self::SCHEME_PASSKEY['name'] => self::SCHEME_PASSKEY['flag'],
    ];

    // Signature flag to scheme mapping
    public const SIGNATURE_FLAG_TO_SCHEME = [
        self::SCHEME_ED25519['flag'] => self::SCHEME_ED25519['name'],
        self::SCHEME_SECP256K1['flag'] => self::SCHEME_SECP256K1['name'],
        self::SCHEME_SECP256R1['flag'] => self::SCHEME_SECP256R1['name'],
        self::SCHEME_MULTISIG['flag'] => self::SCHEME_MULTISIG['name'],
        self::SCHEME_ZKLOGIN['flag'] => self::SCHEME_ZKLOGIN['name'],
        self::SCHEME_PASSKEY['flag'] => self::SCHEME_PASSKEY['name'],
    ];

    // Signature scheme sizes
    public const SIGNATURE_SCHEME_TO_SIZE = [
        self::SCHEME_ED25519['name'] => self::SCHEME_ED25519['size'],
        self::SCHEME_SECP256K1['name'] => self::SCHEME_SECP256K1['size'],
        self::SCHEME_SECP256R1['name'] => self::SCHEME_SECP256R1['size'],
    ];
}
