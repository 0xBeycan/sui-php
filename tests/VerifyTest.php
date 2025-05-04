<?php

declare(strict_types=1);

namespace Sui\Tests;

use PHPUnit\Framework\TestCase;
use Sui\Keypairs\Ed25519\Keypair;
use Sui\Verify;
use Sui\Utils;

class VerifyTest extends TestCase
{
    /**
     * Test ED25519 personal message signature verification
     *
     * This test verifies:
     * 1. Valid signature verification
     * 2. Signature verification with provided address
     * 3. Invalid signature verification
     * 4. Wrong address verification
     *
     * @return void
     */
    public function testEd25519PersonalMessageSignatures(): void
    {
        $keypair = new Keypair();
        $address = $keypair->getPublicKey()->toSuiAddress();
        $message = Utils::fromBase64('aGVsbG8gd29ybGQ='); // "hello world" in base64

        // Test valid signature verification
        $signatureResult = $keypair->signPersonalMessage($message);
        $publicKey = Verify::verifyPersonalMessageSignature($message, $signatureResult['signature']);
        $this->assertEquals($address, $publicKey->toSuiAddress());

        // Test signature verification with provided address
        $publicKey = Verify::verifyPersonalMessageSignature(
            $message,
            $signatureResult['signature'],
            ['address' => $address],
        );
        $this->assertEquals($address, $publicKey->toSuiAddress());

        // Test invalid signature verification
        $invalidMessage = Utils::fromBase64('d3JvbmcgbWVzc2FnZQ=='); // "wrong message" in base64
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Signature is not valid for the provided message');
        Verify::verifyPersonalMessageSignature($invalidMessage, $signatureResult['signature']);

        // Test wrong address verification
        $wrongKeypair = new Keypair();
        $wrongAddress = $wrongKeypair->getPublicKey()->toSuiAddress();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Signature is not valid for the provided address');
        Verify::verifyPersonalMessageSignature($message, $signatureResult['signature'], ['address' => $wrongAddress]);
    }
}
