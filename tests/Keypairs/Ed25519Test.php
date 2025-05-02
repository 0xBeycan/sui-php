<?php

declare(strict_types=1);

namespace Sui\Tests\Keypairs\Ed25519;

use PHPUnit\Framework\TestCase;
use Sui\Keypairs\Ed25519\HdKey;
use Sui\Keypairs\Ed25519\Keypair;
use Sui\Cryptography\Mnemonics;

class Ed25519Test extends TestCase
{
    private const TEST_SEED = '000102030405060708090a0b0c0d0e0f';
    private const TEST_PATH = "m/44'/784'/0'/0'/0'";
    private const TEST_PRIVATE_KEY = 'suiprivkey1qpvdquqtm3fhnwsfqtcem6z096v8q6qynwafe3lgaqwx2t4j4255uhua3sv';
    private const TEST_PUBLIC_KEY = '0x63a7cc78f0506be86fcec6b602695141c6d39001b11444bbe37ba189616dfe59';
    private const TEST_MNEMONIC = 'verb sunset apology pool become slight risk logic version sound couple never';

    /**
     * Tests that derivePath returns valid key and chainCode with correct lengths
     * @return void
     */
    public function testDerivePathWithValidInput(): void
    {
        $result = HdKey::derivePath(self::TEST_PATH, self::TEST_SEED);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('key', $result);
        $this->assertArrayHasKey('chainCode', $result);
        $this->assertEquals(32, strlen($result['key']));
        $this->assertEquals(32, strlen($result['chainCode']));
    }

    /**
     * Tests that different derivation paths produce different keys
     * @return void
     */
    public function testDerivePathWithDifferentPaths(): void
    {
        $paths = [
            "m/44'/784'/0'/0'/0'",
            "m/44'/784'/0'/0'/1'",
            "m/44'/784'/0'/1'/0'",
        ];

        $results = [];
        foreach ($paths as $path) {
            $results[$path] = HdKey::derivePath($path, self::TEST_SEED);
        }

        // Each path should produce different keys
        foreach ($paths as $i => $path1) {
            foreach (array_slice($paths, $i + 1) as $path2) {
                $this->assertNotEquals(
                    bin2hex($results[$path1]['key']),
                    bin2hex($results[$path2]['key']),
                    "Paths $path1 and $path2 should produce different keys"
                );
            }
        }
    }

    /**
     * Tests that an invalid path throws an InvalidArgumentException
     * @return void
     */
    public function testDerivePathWithInvalidPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid derivation path');

        HdKey::derivePath('invalid/path', self::TEST_SEED);
    }

    /**
     * Tests that a non-hardened path throws an InvalidArgumentException
     * @return void
     */
    public function testDerivePathWithNonHardenedPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid derivation path');

        HdKey::derivePath('m/44/784/0/0/0', self::TEST_SEED);
    }

    /**
     * Tests that an empty path throws an InvalidArgumentException
     * @return void
     */
    public function testDerivePathWithEmptyPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid derivation path');

        HdKey::derivePath('', self::TEST_SEED);
    }

    /**
     * Tests that different offsets produce different keys
     * @return void
     */
    public function testDerivePathWithCustomOffset(): void
    {
        $customOffset = 0x10000000;
        $result1 = HdKey::derivePath(self::TEST_PATH, self::TEST_SEED);
        $result2 = HdKey::derivePath(self::TEST_PATH, self::TEST_SEED, $customOffset);

        $this->assertNotEquals(
            bin2hex($result1['key']),
            bin2hex($result2['key']),
            'Different offsets should produce different keys'
        );
    }

    /**
     * Tests that different seeds produce different keys
     * @return void
     */
    public function testDerivePathWithDifferentSeeds(): void
    {
        $seed1 = '000102030405060708090a0b0c0d0e0f';
        $seed2 = '000102030405060708090a0b0c0d0e10';

        $result1 = HdKey::derivePath(self::TEST_PATH, $seed1);
        $result2 = HdKey::derivePath(self::TEST_PATH, $seed2);

        $this->assertNotEquals(
            bin2hex($result1['key']),
            bin2hex($result2['key']),
            'Different seeds should produce different keys'
        );
    }

    /**
     * Tests that an invalid seed throws a ValueError
     * @return void
     */
    public function testDerivePathWithInvalidSeed(): void
    {
        $this->expectException(\ValueError::class);
        HdKey::derivePath(self::TEST_PATH, 'invalid_hex');
    }

    /**
     * Tests that an empty seed throws a ValueError
     * @return void
     */
    public function testDerivePathWithEmptySeed(): void
    {
        $this->expectException(\ValueError::class);
        HdKey::derivePath(self::TEST_PATH, '');
    }

    /**
     * Tests that keypair can be created from secret key
     * @return void
     */
    public function testCreateKeypairFromSecretKey(): void
    {
        $keypair = Keypair::fromSecretKey(self::TEST_PRIVATE_KEY);
        $this->assertEquals(self::TEST_PUBLIC_KEY, $keypair->toSuiAddress());
    }

    /**
     * Tests that keypair can be generated
     * @return void
     */
    public function testGenerateKeypair(): void
    {
        $keypair = Keypair::generate();
        $keypair2 = Keypair::fromSecretKey($keypair->getSecretKey());
        $this->assertEquals($keypair->getSecretKey(), $keypair2->getSecretKey());
        $this->assertEquals($keypair->getPublicKey(), $keypair2->getPublicKey());
    }

    /**
     * Tests that keypair can sign and verify messages
     * @return void
     */
    public function testSignAndVerifyMessage(): void
    {
        $keypair = Keypair::fromSecretKey(self::TEST_PRIVATE_KEY);
        $message = 'Test message to sign';
        $messageBytes = array_map('ord', str_split($message));
        $signature = $keypair->sign($messageBytes);
        $this->assertTrue($keypair->getPublicKey()->verify($messageBytes, $signature));
    }

    /**
     * Tests that keypair can sign and verify messages with different content
     * @return void
     */
    public function testSignAndVerifyDifferentMessages(): void
    {
        $keypair = Keypair::fromSecretKey(self::TEST_PRIVATE_KEY);
        $message1 = 'First test message';
        $message2 = 'Second test message';
        $message1Bytes = array_map('ord', str_split($message1));
        $message2Bytes = array_map('ord', str_split($message2));
        $signature1 = $keypair->sign($message1Bytes);
        $signature2 = $keypair->sign($message2Bytes);
        $this->assertTrue($keypair->getPublicKey()->verify($message1Bytes, $signature1));
        $this->assertTrue($keypair->getPublicKey()->verify($message2Bytes, $signature2));
        $this->assertFalse($keypair->getPublicKey()->verify($message1Bytes, $signature2));
    }

    /**
     * Tests that keypair can be created from mnemonic
     * @return void
     */
    public function testCreateKeypairFromMnemonic(): void
    {
        $keypair = Keypair::deriveKeypair(self::TEST_MNEMONIC);
        $this->assertEquals(self::TEST_PUBLIC_KEY, $keypair->toSuiAddress());
    }

    /**
     * Tests that keypair can be created from seed
     * @return void
     */
    public function testCreateKeypairFromSeed(): void
    {
        $seed = Mnemonics::mnemonicToSeedHex(self::TEST_MNEMONIC);
        $keypair = Keypair::deriveKeypairFromSeed($seed);
        $this->assertEquals(self::TEST_PUBLIC_KEY, $keypair->toSuiAddress());
    }
}
