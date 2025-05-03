<?php

declare(strict_types=1);

namespace Sui\Tests\Transactions;

use Sui\Client;
use Sui\Utils as SuiUtils;
use Sui\Transactions\Utils;
use PHPUnit\Framework\TestCase;
use Sui\Type\Move\NormalizedType;
use Sui\Transactions\Normalizer;

class UtilsTest extends TestCase
{
    /**
     * @var Client
     */
    protected Client $client;

    /**
     * @var string
     */
    protected string $tokenPackage = '0x669d8724a063f2b8890ba15728366f1acf924d8440909cc0bca4b7d15666068e';

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->client = new Client('https://fullnode.testnet.sui.io:443');
    }

    /**
     * @param array<mixed> $array
     * @return array<mixed>
     */
    private function filter(array $array): array
    {
        return array_values(array_filter($array));
    }

    /**
     * @return void
     */
    public function testExtract(): void
    {
        $response = $this->client->getNormalizedMoveFunction($this->tokenPackage, 'tusdc', 'init');
        $extractedMutableReferences = $this->filter(array_map(
            fn(NormalizedType $item) => Utils::extractMutableReference($item),
            $response->parameters
        ));
        $this->assertCount(1, $extractedMutableReferences);
        $extractedReferences = $this->filter(array_map(
            fn(NormalizedType $item) => Utils::extractReference($item),
            $response->parameters
        ));
        $this->assertCount(0, $extractedReferences);
        $structTag = Utils::extractStructTag($extractedMutableReferences[0]);
        $this->assertEquals('TxContext', $structTag->name);
    }

    /**
     * @test
     * @return void
     */
    public function testArgument(): void
    {
        $options = ['test' => 'value'];
        $result = Normalizer::argument($options);
        $this->assertTrue(Utils::isArgument($result));
    }

    /**
     * @return void
     */
    public function testParseStructTag(): void
    {
        $structTag = SuiUtils::parseStructTag('0x1::Test_NFT::TEST_NFT');
        $this->assertEquals(SuiUtils::normalizeSuiAddress('0x1'), $structTag->address);
        $this->assertEquals('Test_NFT', $structTag->module);
        $this->assertEquals('TEST_NFT', $structTag->name);
        $this->assertCount(0, $structTag->typeParams);
    }

    /**
     * @test
     * @return void
     */
    public function testGetIdFromCallArg(): void
    {
        $this->assertEquals(SuiUtils::normalizeSuiAddress('0x1'), Utils::getIdFromCallArg('0x1'));

        $options = [
            'Object' => [
                'ImmOrOwnedObject' => [
                    'objectId' => '0x1',
                    'version' => '1',
                    'digest' => 'digest123'
                ]
            ]
        ];

        $normalizedId = Utils::getIdFromCallArg(Normalizer::callArg($options));

        $this->assertEquals(
            SuiUtils::normalizeSuiAddress('0x1'),
            $normalizedId
        );

        $options = [
            'UnresolvedObject' => [
                'objectId' => '0x1',
            ]
        ];

        $normalizedId = Utils::getIdFromCallArg(Normalizer::callArg($options));

        $this->assertEquals(
            SuiUtils::normalizeSuiAddress('0x1'),
            $normalizedId
        );
    }
}
