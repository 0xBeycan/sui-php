<?php

declare(strict_types=1);

namespace Sui\Tests\Keypairs;

use Sui\Utils;
use Sui\Verify;
use PHPUnit\Framework\TestCase;
use Sui\Keypairs\Ed25519\HdKey;
use Sui\Keypairs\Ed25519\Keypair;
use Sui\Cryptography\Mnemonics;
use Sui\Cryptography\Helpers;
use Sui\Keypairs\Ed25519\PublicKey;
use Sui\Transactions\Transaction;
use Sui\Transactions\BuildTransactionOptions;

class Ed25519Test extends TestCase
{
    private const TEST_SEED = '000102030405060708090a0b0c0d0e0f';
    private const TEST_PATH = "m/44'/784'/0'/0'/0'";
    private const TEST_PRIVATE_KEY = 'suiprivkey1qpvdquqtm3fhnwsfqtcem6z096v8q6qynwafe3lgaqwx2t4j4255uhua3sv';
    private const TEST_PUBLIC_KEY = '0x63a7cc78f0506be86fcec6b602695141c6d39001b11444bbe37ba189616dfe59';
    private const TEST_MNEMONIC = 'verb sunset apology pool become slight risk logic version sound couple never';

    private const VALID_SECRET_KEY = 'mdqVWeFekT7pqy5T49+tV12jO0m+ESW7ki4zSU9JiCg=';
    private const PRIVATE_KEY_SIZE = 32;

    // Test cases from TypeScript code
    private const TEST_CASES = [
        [
            'film crazy soon outside stand loop subway crumble thrive popular green nuclear struggle pistol arm wife phrase warfare march wheat nephew ask sunny firm', // phpcs:ignore
            'ImR/7u82MGC9QgWhZxoV8QoSNnZZGLG19jjYLzPPxGk=',
            '0xa2d14fad60c56049ecf75246a481934691214ce413e6a8ae2fe6834c173a6133',
            'NwIObhuKot7QRWJu4wWCC5ttOgEfN7BrrVq1draImpDZqtKEaWjNNRKKfWr1FL4asxkBlQd8IwpxpKSTzcXMAQ==',
        ],
        [
            'require decline left thought grid priority false tiny gasp angle royal system attack beef setup reward aunt skill wasp tray vital bounce inflict level', // phpcs:ignore
            'vG6hEnkYNIpdmWa/WaLivd1FWBkxG+HfhXkyWgs9uP4=',
            '0x1ada6e6f3f3e4055096f606c746690f1108fcc2ca479055cc434a3e1d3f758aa',
            '8BSMw/VdYSXxbpl5pp8b5ylWLntTWfBG3lSvAHZbsV9uD2/YgsZDbhVba4rIPhGTn3YvDNs3FOX5+EIXMup3Bw==',
        ],
        [
            'organ crash swim stick traffic remember army arctic mesh slice swear summer police vast chaos cradle squirrel hood useless evidence pet hub soap lake', // phpcs:ignore
            'arEzeF7Uu90jP4Sd+Or17c+A9kYviJpCEQAbEt0FHbU=',
            '0xe69e896ca10f5a77732769803cc2b5707f0ab9d4407afb5e4b4464b89769af14',
            '/ihBMku1SsqK+yDxNY47N/tAREZ+gWVTvZrUoCHsGGR9CoH6E7SLKDRYY9RnwBw/Bt3wWcdJ0Wc2Q3ioHIlzDA==',
        ],
    ];

    private const TS_TEST_CASES = [
        [
            'film crazy soon outside stand loop subway crumble thrive popular green nuclear struggle pistol arm wife phrase warfare march wheat nephew ask sunny firm', // phpcs:ignore
            'suiprivkey1qrwsjvr6gwaxmsvxk4cfun99ra8uwxg3c9pl0nhle7xxpe4s80y05ctazer',
            '0xa2d14fad60c56049ecf75246a481934691214ce413e6a8ae2fe6834c173a6133',
        ],
        [
            'require decline left thought grid priority false tiny gasp angle royal system attack beef setup reward aunt skill wasp tray vital bounce inflict level', // phpcs:ignore
            'suiprivkey1qzdvpa77ct272ultqcy20dkw78dysnfyg90fhcxkdm60el0qht9mvzlsh4j',
            '0x1ada6e6f3f3e4055096f606c746690f1108fcc2ca479055cc434a3e1d3f758aa',
        ],
        [
            'organ crash swim stick traffic remember army arctic mesh slice swear summer police vast chaos cradle squirrel hood useless evidence pet hub soap lake', // phpcs:ignore
            'suiprivkey1qqqscjyyr64jea849dfv9cukurqj2swx0m3rr4hr7sw955jy07tzgcde5ut',
            '0xe69e896ca10f5a77732769803cc2b5707f0ab9d4407afb5e4b4464b89769af14',
        ],
    ];

    private const TX_BYTES = 'AAACAQDMdYtdFSLGe6VbgpuIsMksv9Ypzpvkq2jiYq0hAjUpOQIAAAAAAAAAIHGwPza+lUm6RuJV1vn9pA4y0PwVT7k/KMMbUViQS5ydACAMVn/9+BYsttUa90vgGZRDuS6CPUumztJN5cbEY3l9RgEBAQEAAAEBAHUFfdk1Tg9l6STLBoSBJbbUuehTDUlLH7p81kpqCKsaBCiJ034Ac84f1oqgmpz79O8L/UeLNDUpOUMa+LadeX93AgAAAAAAAAAgs1e67e789jSlrzOJUXq0bb7Bn/hji+3F5UoMAbze595xCSZCVjU1ItUC9G7KQjygNiBbzZe8t7YLPjRAQyGTzAIAAAAAAAAAIAujHFcrkJJhZfCmxmCHsBWxj5xkviUqB479oupdgMZu07b+hkrjyvCcX50dO30v3PszXFj7+lCNTUTuE4UI3eoCAAAAAAAAACBIv39dyVELUFTkNv72mat5R1uHFkQdViikc1lTMiSVlOD+eESUq3neyciBatafk9dHuhhrS37RaSflqKwFlwzPAgAAAAAAAAAg8gqL3hCkAho8bb0PoqshJdqQFoRP8ZmQMZDFvsGBqa11BX3ZNU4PZekkywaEgSW21LnoUw1JSx+6fNZKagirGgEAAAAAAAAAKgQAAAAAAAAA'; // phpcs:ignore
    private const DIGEST = 'VMVv+/L/EG7/yhEbCQ1qiSt30JXV8yIm+4xO6yTkqeM=';

    private const PB_TEST_CASES = [
        [
            'rawPublicKey' => 'UdGRWooy48vGTs0HBokIis5NK+DUjiWc9ENUlcfCCBE=',
            'suiPublicKey' => 'AFHRkVqKMuPLxk7NBwaJCIrOTSvg1I4lnPRDVJXHwggR',
            'suiAddress' => '0xd77a6cd55073e98d4029b1b0b8bd8d88f45f343dad2732fc9a7965094e635c55',
        ],
        [
            'rawPublicKey' => '0PTAfQmNiabgbak9U/stWZzKc5nsRqokda2qnV2DTfg=',
            'suiPublicKey' => 'AND0wH0JjYmm4G2pPVP7LVmcynOZ7EaqJHWtqp1dg034',
            'suiAddress' => '0x7e8fd489c3d3cd9cc7cbcc577dc5d6de831e654edd9997d95c412d013e6eea23',
        ],
        [
            'rawPublicKey' => '6L/l0uhGt//9cf6nLQ0+24Uv2qanX/R6tn7lWUJX1Xk=',
            'suiPublicKey' => 'AOi/5dLoRrf//XH+py0NPtuFL9qmp1/0erZ+5VlCV9V5',
            'suiAddress' => '0x3a1b4410ebe9c3386a429c349ba7929aafab739c277f97f32622b971972a14a2',
        ],
    ];

    private const VALID_KEY_BASE64 = 'Uz39UFseB/B38iBwjesIU1JZxY6y+TRL9P84JFw41W4=';

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

    /**
     * Tests keypair signData functionality with test cases from TypeScript
     * @return void
     */
    public function testKeypairSignData(): void
    {
        $txBytes = Utils::fromBase64(self::TX_BYTES);
        $intentMessage = Helpers::messageWithIntent('TransactionData', $txBytes);
        $digest = Utils::blake2b($intentMessage, 32);
        $this->assertEquals(self::DIGEST, Utils::toBase64($digest));

        foreach (self::TEST_CASES as $testCase) {
            $mnemonic = $testCase[0];
            $expectedPublicKey = $testCase[1];
            $expectedAddress = $testCase[2];
            $expectedSignature = $testCase[3];

            $keypair = Keypair::deriveKeypair($mnemonic);
            $this->assertEquals($expectedPublicKey, $keypair->getPublicKey()->toBase64());
            $this->assertEquals($expectedAddress, $keypair->toSuiAddress());

            $result = $keypair->signTransaction($txBytes);
            $parsedSignature = PublicKey::parseSerializedKeypairSignature($result['signature']);
            $this->assertEquals($expectedSignature, Utils::toBase64($parsedSignature['signature']));
            $this->assertTrue($keypair->getPublicKey()->verifyTransaction($txBytes, $result['signature']));
        }
    }

    /**
     * Tests keypair signMessage functionality
     * @return void
     */
    public function testKeypairSignMessage(): void
    {
        $keypair = Keypair::generate();
        $message = 'hello world';
        $messageBytes = Utils::textEncode($message);

        $signature = $keypair->sign($messageBytes);
        $this->assertTrue($keypair->getPublicKey()->verify($messageBytes, $signature));
    }

    /**
     * Tests keypair signMessage with invalid message
     * @return void
     */
    public function testKeypairInvalidSignMessage(): void
    {
        $keypair = Keypair::generate();
        $message1 = 'hello world';
        $message2 = 'hello worlds';
        $message1Bytes = Utils::textEncode($message1);
        $message2Bytes = Utils::textEncode($message2);

        $signature = $keypair->sign($message1Bytes);
        $this->assertFalse($keypair->getPublicKey()->verify($message2Bytes, $signature));
    }

    /**
     * Tests that new keypair has correct public key length
     * @return void
     */
    public function testNewKeypair(): void
    {
        $keypair = Keypair::generate();
        $this->assertEquals(32, count($keypair->getPublicKey()->toRawBytes()));
    }

    /**
     * Tests that keypair can be created from base64 encoded secret key
     * @return void
     */
    public function testCreateKeypairFromBase64SecretKey(): void
    {
        $secretKey = Utils::fromBase64(self::VALID_SECRET_KEY);
        $keypair = Keypair::fromSecretKey($secretKey);
        $this->assertEquals('Gy9JCW4+Xb0Pz6nAwM2S2as7IVRLNNXdSmXZi4eLmSI=', $keypair->getPublicKey()->toBase64());
    }

    /**
     * Tests that keypair can be created from secret key and mnemonics matches keytool
     * @return void
     */
    public function testCreateKeypairFromSecretKeyAndMnemonicsMatchesKeytool(): void
    {
        foreach (self::TS_TEST_CASES as $testCase) {
            // Keypair derived from mnemonic
            $keypair = Keypair::deriveKeypair($testCase[0]);
            $this->assertEquals($testCase[2], $keypair->toSuiAddress());

            // Decode Sui private key from Bech32 string
            $parsed = Keypair::decodeSuiPrivateKey($testCase[1]);
            $kp = Keypair::fromSecretKey($parsed['secretKey']);
            $this->assertEquals($testCase[2], $kp->toSuiAddress());

            // Exported keypair matches the Bech32 encoded secret key
            $exported = $kp->getSecretKey();
            $this->assertEquals($testCase[1], $exported);
        }
    }

    /**
     * Tests that keypair can be generated from random seed
     * @return void
     */
    public function testGenerateKeypairFromRandomSeed(): void
    {
        $seed = array_fill(0, self::PRIVATE_KEY_SIZE, 8);
        $keypair = Keypair::fromSecretKey($seed);
        $this->assertEquals('E5j2LG0aRXxRumpLXz29L2n8qTIWIY3ImX5Ba9F9k8o=', $keypair->getPublicKey()->toBase64());
    }

    /**
     * Tests that signature of data is valid
     * @return void
     */
    public function testSignatureOfDataIsValid(): void
    {
        $keypair = Keypair::generate();
        $signData = Utils::textEncode('hello world');
        $signature = $keypair->sign($signData);

        $isValid = \ParagonIE_Sodium_Compat::crypto_sign_verify_detached(
            implode('', array_map('chr', $signature)),
            implode('', array_map('chr', $signData)),
            implode('', array_map('chr', $keypair->getPublicKey()->toRawBytes()))
        );

        $this->assertTrue($isValid);
        $this->assertTrue($keypair->getPublicKey()->verify($signData, $signature));
    }

    /**
     * Tests that incorrect coin type node for ed25519 derivation path throws exception
     * @return void
     */
    public function testIncorrectCoinTypeNodeForEd25519DerivationPath(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid derivation path');
        Keypair::deriveKeypair(self::TS_TEST_CASES[0][0], "m/44'/0'/0'/0'/0'");
    }

    /**
     * Tests that incorrect purpose node for ed25519 derivation path throws exception
     * @return void
     */
    public function testIncorrectPurposeNodeForEd25519DerivationPath(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid derivation path');
        Keypair::deriveKeypair(self::TS_TEST_CASES[0][0], "m/54'/784'/0'/0'/0'");
    }

    /**
     * Tests that invalid mnemonics to derive ed25519 keypair throws exception
     * @return void
     */
    public function testInvalidMnemonicsToDeriveEd25519Keypair(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unacceptable word count for BIP39 mnemonic');
        Keypair::deriveKeypair('aaa');
    }

    /**
     * @return void
     */
    public function testSignsTransactions(): void
    {
        $keypair = Keypair::generate();
        $tx = new Transaction(new BuildTransactionOptions());
        $tx->setSender($keypair->getPublicKey()->toSuiAddress());
        $tx->setGasPrice(5);
        $tx->setGasBudget(100);
        $tx->setGasPayment([
            [
                'objectId' => str_pad(strval(mt_rand(0, 100000)), 64, '0'),
                'version' => strval(mt_rand(0, 10000)),
                'digest' => Utils::toBase58(json_decode('[
                    0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3, 4, 5, 6, 7, 8,
                    9, 1, 2
                ]'))
            ]
        ]);

        $bytes = $tx->build();
        $serializedSignature = $keypair->signTransaction($bytes)['signature'];

        $this->assertTrue($keypair->getPublicKey()->verifyTransaction($bytes, $serializedSignature));
        $this->assertTrue(!!Verify::verifyTransactionSignature($bytes, $serializedSignature));
    }

    /**
     * Tests that keypair can sign and verify personal messages
     * @return void
     */
    public function testSignsPersonalMessages(): void
    {
        $keypair = Keypair::generate();
        $message = Utils::textEncode('hello world');

        $serializedSignature = $keypair->signPersonalMessage($message)['signature'];

        $this->assertTrue($keypair->getPublicKey()->verifyPersonalMessage($message, $serializedSignature));
        $this->assertTrue(!!Verify::verifyPersonalMessageSignature($message, $serializedSignature));
    }

    /**
     * Tests that invalid public key inputs throw exceptions
     * @return void
     */
    public function testInvalid(): void
    {
        // public key length 33 is invalid for Ed25519
        $this->expectException(\Exception::class);
        new PublicKey([
            3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
            0, 0,
        ]);

        $this->expectException(\Exception::class);
        new PublicKey(
            '0x300000000000000000000000000000000000000000000000000000000000000000000'
        );

        $this->expectException(\Exception::class);
        new PublicKey('0x300000000000000000000000000000000000000000000000000000000000000');

        $this->expectException(\Exception::class);
        new PublicKey(
            '135693854574979916511997248057056142015550763280047535983739356259273198796800000'
        );

        $this->expectException(\Exception::class);
        new PublicKey('12345');
    }

    /**
     * Tests that toBase64 returns the correct base64 encoded string
     * @return void
     */
    public function testToBase64(): void
    {
        $key = new PublicKey(self::VALID_KEY_BASE64);
        $this->assertEquals(self::VALID_KEY_BASE64, $key->toBase64());
    }

    /**
     * Tests that toRawBytes returns the correct byte array and equals works correctly
     * @return void
     */
    public function testToBuffer(): void
    {
        $key = new PublicKey(self::VALID_KEY_BASE64);
        $this->assertEquals(32, count($key->toRawBytes()));
        $this->assertTrue((new PublicKey($key->toRawBytes()))->equals($key));
    }

    /**
     * Tests that toSuiAddress returns the correct Sui address for base64 encoded public keys
     * @return void
     */
    public function testToSuiAddressFromBase64PublicKey(): void
    {
        foreach (self::PB_TEST_CASES as $testCase) {
            $key = new PublicKey($testCase['rawPublicKey']);
            $this->assertEquals($testCase['suiAddress'], $key->toSuiAddress());
        }
    }

    /**
     * Tests that toSuiPublicKey returns the correct Sui public key for base64 encoded public keys
     * @return void
     */
    public function testToSuiPublicKeyFromBase64PublicKey(): void
    {
        foreach (self::PB_TEST_CASES as $testCase) {
            $key = new PublicKey($testCase['rawPublicKey']);
            $this->assertEquals($testCase['suiPublicKey'], $key->toSuiPublicKey());
        }
    }
}
