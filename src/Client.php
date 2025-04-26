<?php

declare(strict_types=1);

namespace Sui;

use Exception;
use Sui\Constants;
use Sui\Response\CoinsResponse;
use Sui\Response\ObjectResponse;
use Sui\Response\BalanceResponse;
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

        return $responseBody['result'] ?? $responseBody;
    }

    /**
     * @return ?string
     */
    public function getRpcApiVersion(): ?string
    {
        return $this->request('rpc.discover')['info']['version'] ?? null;
    }

    /**
     * @param string $objectId
     * @param array<mixed> $options
     * @return ObjectResponse
     */
    public function getObject(string $objectId, array $options = []): ObjectResponse
    {
        return ObjectResponse::fromArray($this->request('sui_getObject', [$objectId, $options])['data'] ?? []);
    }

    /**
     * @param string $owner
     * @param string|null $coinType
     * @return BalanceResponse
     */
    public function getBalance(string $owner, ?string $coinType = null): BalanceResponse
    {
        return BalanceResponse::fromArray($this->request('suix_getBalance', [
            $owner,
            $coinType ?? Constants::SUI_TYPE_ARG,
        ]));
    }

    /**
     * @param string $owner
     * @return Array<BalanceResponse<
     */
    public function getAllBalances(string $owner): array
    {
        $result = $this->request('suix_getAllBalances', [
            $owner,
        ]);

        return array_map(
            static fn(array $item) => BalanceResponse::fromArray($item),
            $result ?? []
        );
    }

    /**
     * @param array<mixed> $filter
     * @return CoinsResponse
     */
    public function getCoins(array $filter): CoinsResponse
    {
        if (empty($filter['owner'])) {
            throw new Exception('Owner is required in the filter.');
        }

        return CoinsResponse::fromArray($this->request('suix_getCoins', [
            $filter['owner'],
            $filter['coinType'] ?? Constants::SUI_TYPE_ARG,
            $filter['cursor'] ?? null,
            $filter['limit'] ?? 10,
        ]));
    }

    /**
     * @param array<mixed> $filter
     * @return CoinsResponse
     */
    public function getAllCoins(array $filter): CoinsResponse
    {
        if (empty($filter['owner'])) {
            throw new Exception('Owner is required in the filter.');
        }

        return CoinsResponse::fromArray($this->request('suix_getAllCoins', [
            $filter['owner'],
            $filter['cursor'] ?? null,
            $filter['limit'] ?? 10,
        ]));
    }
}
