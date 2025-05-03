<?php

declare(strict_types=1);

namespace Sui\Tests;

use PHPUnit\Framework\TestCase;
use Sui\Cryptography\Helpers;
use Sui\Cryptography\Mnemonics;
use Sui\Cryptography\Schema;
use Sui\Cryptography\Keypair;
use Sui\Cryptography\PublicKey;
use Sui\Utils;

class CryptoTest extends TestCase
{
    /**
     * Test the messageWithIntent function
     * @return void
     */
    public function testMessageWithIntent(): void
    {
        $message = [1, 2, 3, 4, 5];
        $scope = 'TransactionData';
        $result = Helpers::messageWithIntent($scope, $message);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test the parseSerializedSignature function with a mock signature
     * @return void
     */
    public function testParseSerializedSignature(): void
    {
        // Create a mock serialized signature
        $signatureBytes = array_merge(
            [Schema::SCHEME_ED25519['flag']],
            array_fill(0, 64, 0), // signature
            array_fill(0, 32, 0)  // public key
        );
        $serializedSignature = Utils::toBase64($signatureBytes);

        $result = Helpers::parseSerializedSignature($serializedSignature);
        $this->assertIsArray($result);
        $this->assertEquals(Schema::SCHEME_ED25519['name'], $result['signatureScheme']);
    }

    /**
     * Test the mnemonicToSeed function with a valid mnemonic
     * @return void
     */
    public function testMnemonicToSeed(): void
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = Mnemonics::mnemonicToSeed($mnemonic);
        $this->assertIsString($seed);
        $this->assertNotEmpty($seed);
    }

    /**
     * Test the isValidHardenedPath function with valid and invalid paths
     * @return void
     */
    public function testIsValidHardenedPath(): void
    {
        $validPath = "m/44'/784'/0'/0'/0'";
        $invalidPath = "m/44'/784'/0/0/0";

        $this->assertTrue(Mnemonics::isValidHardenedPath($validPath));
        $this->assertFalse(Mnemonics::isValidHardenedPath($invalidPath));
    }

    /**
     * Test the isValidBIP32Path function with valid and invalid paths
     * @return void
     */
    public function testIsValidBIP32Path(): void
    {
        $validPath = "m/54'/784'/0'/0/0";
        $invalidPath = "m/54'/784'/0'/0'/0'";

        $this->assertTrue(Mnemonics::isValidBIP32Path($validPath));
        $this->assertFalse(Mnemonics::isValidBIP32Path($invalidPath));
    }

    /**
     * Test the mnemonicToSeedHex function with a valid mnemonic
     * @return void
     */
    public function testMnemonicToSeedHex(): void
    {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = Mnemonics::mnemonicToSeedHex($mnemonic);
        $this->assertIsString($seed);
        $this->assertTrue(ctype_xdigit($seed));
    }

    /**
     * Test the Schema constants for signature schemes
     * @return void
     */
    public function testSchemaConstants(): void
    {
        $this->assertEquals(0x00, Schema::SCHEME_ED25519['flag']);
        $this->assertEquals(0x01, Schema::SCHEME_SECP256K1['flag']);
        $this->assertEquals(0x02, Schema::SCHEME_SECP256R1['flag']);
        $this->assertEquals(0x03, Schema::SCHEME_MULTISIG['flag']);
        $this->assertEquals(0x05, Schema::SCHEME_ZKLOGIN['flag']);
        $this->assertEquals(0x06, Schema::SCHEME_PASSKEY['flag']);

        $this->assertEquals(32, Schema::SCHEME_ED25519['size']);
        $this->assertEquals(33, Schema::SCHEME_SECP256K1['size']);
        $this->assertEquals(33, Schema::SCHEME_SECP256R1['size']);
    }

    /**
     * Test the signature scheme mappings
     * @return void
     */
    public function testSignatureSchemeMappings(): void
    {
        $this->assertEquals(
            Schema::SCHEME_ED25519['name'],
            Schema::SIGNATURE_FLAG_TO_SCHEME[Schema::SCHEME_ED25519['flag']]
        );
        $this->assertEquals(
            Schema::SCHEME_SECP256K1['name'],
            Schema::SIGNATURE_FLAG_TO_SCHEME[Schema::SCHEME_SECP256K1['flag']]
        );
        $this->assertEquals(
            Schema::SCHEME_SECP256R1['name'],
            Schema::SIGNATURE_FLAG_TO_SCHEME[Schema::SCHEME_SECP256R1['flag']]
        );
    }

    /**
     * Test the Keypair class with a valid private key
     * @return void
     */
    public function testKeypair(): void
    {
        $result = Keypair::decodeSuiPrivateKey(
            'suiprivkey1qrcamlu07sa6jwv9j8f7ranaq20qgak8tphs6lycpr02qtuuvgsty2qfauw'
        );
        $this->assertEquals(Schema::SCHEME_ED25519['name'], $result['schema']);
        $this->assertEquals(32, count($result['secretKey']));

        $serialized = Keypair::encodeSuiPrivateKey($result['secretKey'], $result['schema']);
        $this->assertEquals(
            'suiprivkey1qrcamlu07sa6jwv9j8f7ranaq20qgak8tphs6lycpr02qtuuvgsty2qfauw',
            $serialized
        );
    }

    /**
     * Test the serializeSignature function with a valid signature
     * @return void
     */
    public function testSerializeSignature(): void
    {
        $signature = 'AO5kwjQzC9CGF8vUDg0fW1/XdBjwQA3mGlXOc03LZ5OCOga9+D6mJ6ZpVKyn8J5NplkUoKS54YtAYQup+uf0WwhmzPO6CLfsA1NZTCg4ax+HrwCrnu+ldrA+xPH7tWp41w=='; // @phpcs:ignore
        $result = Helpers::parseSerializedSignature($signature);
        $this->assertEquals(Schema::SCHEME_ED25519['name'], $result['signatureScheme']);
        $this->assertEquals(64, count($result['signature']));
        $this->assertEquals(32, count($result['publicKey']));
    }

    /**
     * Test the hashTypedData function with a valid type and data
     * @return void
     */
    public function testHashTypedData(): void
    {
        $type = 'TransactionData';
        $data = [1, 2, 3, 4, 5];
        $result = Utils::hashTypedData($type, $data);
        $this->assertEquals(Utils::toBase64($result), 'sQmcQscA+OmsK/03aYD+0rnLGAj3sSE5KIjdXpQ2C5I=');
    }
}
