<?php

declare(strict_types=1);

namespace Sui;

use Exception;
use Sui\Constants;
use Sui\Type\Balance;
use Sui\Type\ObjectRead;
use Sui\Type\SuiObjetData;
use Sui\Type\CoinMetadata;
use Sui\Type\DelegatedStake;
use Sui\Type\TransactionBlock;
use Sui\Type\DevInspectResults;
use Sui\Transactions\Transaction;
use Sui\Paginated\PaginatedCoins;
use Sui\Paginated\PaginatedEvents;
use Sui\Paginated\PaginatedObjects;
use Sui\Type\Move\NormalizedStruct;
use Sui\Type\Move\NormalizedModule;
use Sui\Type\SuiSystemStateSummary;
use Sui\Type\Move\NormalizedFunction;
use Sui\Paginated\PaginatedTransactionBlocks;
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
            fn(array $item) => new Balance($item),
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
        ?int $limit = null
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
    public function getAllCoins(string $owner, ?string $cursor = null, ?int $limit = null): PaginatedCoins
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
        ?int $limit = null
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

    /**
     * @param array<string> $ids
     * @param array<mixed> $options
     * @return array<SuiObjetData>
     */
    public function multiGetObjects(array $ids, array $options = []): array
    {
        return array_map(
            fn(array $item) => new SuiObjetData($item['data'] ?? []),
            $this->request('sui_multiGetObjects', [
                $ids,
                $options,
            ])
        );
    }

    /**
     * @param string $package
     * @param string $module
     * @param string $function
     * @return array<string>
     */
    public function getMoveFunctionArgTypes(string $package, string $module, string $function): array
    {
        return $this->request('sui_getMoveFunctionArgTypes', [
            $package,
            $module,
            $function,
        ]);
    }

    /**
     * @param string $package
     * @return array<NormalizedModule>
     */
    public function getNormalizedMoveModulesByPackage(string $package): array
    {
        return array_map(
            fn(array $item) => new NormalizedModule($item),
            $this->request('sui_getNormalizedMoveModulesByPackage', [
                $package,
            ])
        );
    }

    /**
     * @param string $package
     * @param string $module
     * @return NormalizedModule
     */
    public function getNormalizedMoveModule(string $package, string $module): NormalizedModule
    {
        return new NormalizedModule($this->request('sui_getNormalizedMoveModule', [
            $package,
            $module,
        ]));
    }

    /**
     * @param string $package
     * @param string $module
     * @param string $function
     * @return NormalizedFunction
     */
    public function getNormalizedMoveFunction(string $package, string $module, string $function): NormalizedFunction
    {
        return new NormalizedFunction($this->request('sui_getNormalizedMoveFunction', [
            $package,
            $module,
            $function,
        ]));
    }

    /**
     * @param string $package
     * @param string $module
     * @param string $struct
     * @return NormalizedStruct
     */
    public function getNormalizedMoveStruct(string $package, string $module, string $struct): NormalizedStruct
    {
        return new NormalizedStruct($this->request('sui_getNormalizedMoveStruct', [
            $package,
            $module,
            $struct,
        ]));
    }

    /**
     * @param array<mixed> $filter
     * @param array<mixed> $options
     * @param string|null $cursor
     * @param string|null $order
     * @param integer|null $limit
     * @return PaginatedTransactionBlocks
     */
    public function queryTransactionBlocks(
        array $filter = [],
        array $options = [],
        ?string $cursor = null,
        ?string $order = null,
        ?int $limit = null
    ): PaginatedTransactionBlocks {
        return PaginatedTransactionBlocks::fromArray($this->request('suix_queryTransactionBlocks', [
            [
                'filter' => count($filter) > 0 ? $filter : null,
                'options' => count($options) > 0 ? $options : null
            ],
            $cursor,
            $limit,
            "descending" === ($order ?? "descending")
        ]));
    }

    /**
     * @param string $digest
     * @param array<mixed> $options
     * @return TransactionBlock
     */
    public function getTransactionBlock(string $digest, array $options = []): TransactionBlock
    {
        return new TransactionBlock($this->request('sui_getTransactionBlock', [
            $digest,
            $options,
        ]));
    }

    /**
     * @param array<string> $digests
     * @param array<mixed> $options
     * @return array<TransactionBlock>
     */
    public function multiGetTransactionBlocks(array $digests, array $options = []): array
    {
        return array_map(
            fn(array $item) => new TransactionBlock($item),
            $this->request('sui_multiGetTransactionBlocks', [
                $digests,
                $options,
            ])
        );
    }

    /**
     * @return int
     */
    public function getTotalTransactionBlocks(): int
    {
        return (int) $this->request('sui_getTotalTransactionBlocks');
    }

    /**
     * @return int
     */
    public function getReferenceGasPrice(): int
    {
        return (int) $this->request('suix_getReferenceGasPrice');
    }

    /**
     * @param string $owner
     * @return array<DelegatedStake>
     */
    public function getStakes(string $owner): array
    {
        return array_map(
            fn(array $item) => new DelegatedStake($item),
            $this->request('suix_getStakes', [
                $owner,
            ])
        );
    }

    /**
     * @param array<string> $ids
     * @return array<DelegatedStake>
     */
    public function getStakesByIds(array $ids): array
    {
        return array_map(
            fn(array $item) => new DelegatedStake($item),
            $this->request('suix_getStakesByIds', [
                $ids,
            ])
        );
    }

    /**
     * @return SuiSystemStateSummary
     */
    public function getLatestSuiSystemState(): SuiSystemStateSummary
    {
        return new SuiSystemStateSummary($this->request('suix_getLatestSuiSystemState'));
    }

    /**
     * @param array<mixed> $query
     * @param string|null $cursor
     * @param integer|null $limit
     * @param string|null $order
     * @return PaginatedEvents
     */
    public function queryEvents(
        array $query,
        ?string $cursor = null,
        ?int $limit = null,
        ?string $order = null
    ): PaginatedEvents {
        return PaginatedEvents::fromArray($this->request('suix_queryEvents', [
            $query,
            $cursor,
            $limit,
            "descending" === ($order ?? "descending")
        ]));
    }

    /**
     * @param string $sender
     * @param string|array<int>|Transaction $transactionBlock
     * @param int|null $gasPrice
     * @param string|null $epoch
     * @return DevInspectResults
     */
    public function devInspectTransactionBlock(
        string $sender,
        string|array|Transaction $transactionBlock,
        ?int $gasPrice = null,
        ?string $epoch = null,
    ): DevInspectResults {
        $devInspectTxBytes = $transactionBlock;

        if ($transactionBlock instanceof Transaction) {
            $transactionBlock->setSenderIfNotSet($sender);
            $buildedTx = $transactionBlock->build([
                'client' => $this,
                'onlyTransactionKind' => true
            ]);
            $devInspectTxBytes = base64_encode(serialize($buildedTx));
        } elseif (is_array($transactionBlock)) {
            $devInspectTxBytes = base64_encode(serialize($transactionBlock));
        }

        return new DevInspectResults($this->request('sui_devInspectTransactionBlock', [
            $sender,
            $devInspectTxBytes,
            $gasPrice ? (string) $gasPrice : null,
            $epoch
        ]));
    }

    /**
     * @return array<mixed>
     */
    // @phpcs:ignore
    public function experimental_asClientExtension(): array
    {
        return [
            'name' => 'jsonRPC',
            'register' => fn() => $this
        ];
    }
}
