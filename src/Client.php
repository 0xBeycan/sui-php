<?php

declare(strict_types=1);

namespace Sui;

use Exception;
use Sui\Constants;
use Sui\Type\Balance;
use Sui\Type\ObjectRead;
use Sui\Type\SuiObjetData;
use Sui\Type\CoinMetadata;
use Sui\Paginated\PaginatedCoins;
use Sui\Paginated\PaginatedObjects;
use GuzzleHttp\Client as GuzzleClient;

class Client
{
    use Endpoints;

    /**
     * The Guzzle HTTP client instance.
     *
     * @var GuzzleClient
     */
    private GuzzleClient $client;

    /**
     * @param string $url The URL of the Sui server to connect to.
     */
    public function __construct(private string $url)
    {
        $this->client = new GuzzleClient([
            'base_uri' => $this->url,
            'timeout' => 2.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Sends a JSON-RPC request to the Sui server.
     *
     * @param string $method The JSON-RPC method to call.
     * @param array<mixed> $params The parameters for the method (optional).
     * @return mixed The response from the server.
     * @throws Exception If the request fails or the response is not valid.
     */
    public function request(string $method, array $params = []): mixed
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => uniqid(),
        ];

        $response = $this->client->request('POST', $this->url, ['json' => $payload]);

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode > 299) {
            throw new Exception('Request failed with status code: ' . $statusCode);
        }

        $responseBody = json_decode((string) $response->getBody(), true);

        $result = $responseBody['result'] ?? $responseBody;

        if (isset($result['error'])) {
            throw new Exception(
                'Error: ' . $result['error']['message'] ?? 'Unknown error',
                $result['error']['code'] ?? 0
            );
        }

        return $result;
    }

    /**
     * @return ?string
     */
    public function getRpcApiVersion(): ?string
    {
        return $this->request('rpc.discover')['info']['version'] ?? null;
    }

    /**
     * @param string $owner
     * @param string|null $coinType
     * @return Balance
     */
    public function getBalance(string $owner, ?string $coinType = null): Balance
    {
        return new Balance($this->request('suix_getBalance', [
            $owner,
            $coinType ?? Constants::SUI_TYPE_ARG,
        ]));
    }

    /**
     * @param string $owner
     * @return array<Balance>
     */
    public function getAllBalances(string $owner): array
    {
        $result = $this->request('suix_getAllBalances', [
            $owner,
        ]);

        return array_map(
            static fn(array $item) => new Balance($item),
            $result ?? []
        );
    }

    /**
     * @param string $owner
     * @param string|null $coinType
     * @param string|null $cursor
     * @param int $limit
     * @return PaginatedCoins
     */
    public function getCoins(
        string $owner,
        ?string $coinType = null,
        ?string $cursor = null,
        int $limit = 10
    ): PaginatedCoins {
        return PaginatedCoins::fromArray($this->request('suix_getCoins', [
            $owner,
            $coinType ?? Constants::SUI_TYPE_ARG,
            $cursor,
            $limit,
        ]));
    }

    /**
     * @param string $owner
     * @param string|null $cursor
     * @param int $limit
     * @return PaginatedCoins
     */
    public function getAllCoins(string $owner, ?string $cursor = null, int $limit = 10): PaginatedCoins
    {
        return PaginatedCoins::fromArray($this->request('suix_getAllCoins', [
            $owner,
            $cursor,
            $limit,
        ]));
    }

    /**
     * @param string $coinType
     * @return CoinMetadata|null
     */
    public function getCoinMetadata(string $coinType): ?CoinMetadata
    {
        return new CoinMetadata($this->request('suix_getCoinMetadata', [$coinType]));
    }

    /**
     * @param string $coinType
     * @return string
     */
    public function getTotalSupply(string $coinType): string
    {
        return $this->request('suix_getTotalSupply', [$coinType])['value'] ?? '0';
    }

    /**
     * @param string $objectId
     * @param array<mixed> $options
     * @return SuiObjetData
     */
    public function getObject(string $objectId, array $options = []): SuiObjetData
    {
        return new SuiObjetData($this->request('sui_getObject', [$objectId, $options])['data'] ?? []);
    }

    /**
     * @param string $owner
     * @param array<mixed> $filter
     * @param array<mixed> $options
     * @param string|null $cursor
     * @param int $limit
     * @return PaginatedObjects
     */
    public function getOwnedObjects(
        string $owner,
        array $filter = [],
        array $options = [],
        ?string $cursor = null,
        int $limit = 10
    ): PaginatedObjects {
        return PaginatedObjects::fromArray($this->request('suix_getOwnedObjects', [
            $owner,
            [
                'filter' => count($filter) > 0 ? $filter : null,
                'options' => count($options) > 0 ? $options : null
            ],
            $cursor,
            $limit,
        ]));
    }

    /**
     * @param string $id
     * @param float $version
     * @param array<mixed> $options
     * @return ObjectRead
     */
    public function tryGetPastObject(string $id, float $version, ?array $options = []): ObjectRead
    {
        return new ObjectRead($this->request('sui_tryGetPastObject', [
            $id,
            $version,
            $options,
        ]));
    }
}
