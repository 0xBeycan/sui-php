<?php

declare(strict_types=1);

namespace Sui;

use Exception;
use Sui\Constants;
use Sui\Type\Balance;
use Sui\Type\ObjectRead;
use Sui\Type\Checkpoint;
use Sui\Type\SuiObjetData;
use Sui\Type\CoinMetadata;
use Sui\Type\CommitteeInfo;
use Sui\Type\ValidatorsApy;
use Sui\Type\DelegatedStake;
use Sui\Type\TransactionBlock;
use Sui\Type\DynamicFieldName;
use Sui\Type\DevInspectResults;
use Sui\Transactions\Transaction;
use Sui\Paginated\PaginatedCoins;
use Sui\Paginated\PaginatedEvents;
use Sui\Paginated\PaginatedObjects;
use Sui\Type\Move\NormalizedStruct;
use Sui\Type\Move\NormalizedModule;
use Sui\Type\SuiSystemStateSummary;
use Sui\Type\DryRunTransactionBlock;
use Sui\Type\Move\NormalizedFunction;
use Sui\Paginated\PaginatedCheckpoints;
use Sui\Paginated\PaginatedDynamicFieldInfo;
use Sui\Paginated\PaginatedTransactionBlocks;
use Sui\Paginated\PaginatedResolvedNameServiceNames;

class Client
{
    use Endpoints;

    /**
     * The Guzzle HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    private \GuzzleHttp\Client $client;

    /**
     * @param string $url The URL of the Sui server to connect to.
     */
    public function __construct(private string $url)
    {
        $this->client = new \GuzzleHttp\Client([
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
                'Error: ' . $result['error']['message'],
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
     * @param array<mixed>|null $options
     * @return TransactionBlock
     */
    public function getTransactionBlock(string $digest, ?array $options = null): TransactionBlock
    {
        return new TransactionBlock($this->request('sui_getTransactionBlock', [
            $digest,
            $options,
        ]));
    }

    /**
     * @param array<string> $digests
     * @param array<mixed>|null $options
     * @return array<TransactionBlock>
     */
    public function multiGetTransactionBlocks(array $digests, ?array $options = null): array
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
     * @param string|array<int>|Transaction $transactionBlock
     * @param string|null $sender
     * @return string
     */
    private function transactionBlockToBase64(
        string|array|Transaction $transactionBlock,
        ?string $sender = null
    ): string {
        if ($transactionBlock instanceof Transaction) {
            if (!$sender) {
                throw new Exception('Sender is required when using Transaction instance');
            }
            $transactionBlock->setSenderIfNotSet($sender);
            $buildedTx = $transactionBlock->build([
                'client' => $this,
                'onlyTransactionKind' => true
            ]);
            return Utils::toBase64($buildedTx);
        } elseif (is_array($transactionBlock)) {
            return Utils::toBase64($transactionBlock);
        } elseif (is_string($transactionBlock)) {
            return $transactionBlock;
        } else {
            throw new Exception('Invalid transaction block');
        }
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
        return new DevInspectResults($this->request('sui_devInspectTransactionBlock', [
            $sender,
            $this->transactionBlockToBase64($transactionBlock, $sender),
            $gasPrice ? (string) $gasPrice : null,
            $epoch
        ]));
    }

    /**
     * @param string|array<mixed>|Transaction $transactionBlock
     * @param string|null $sender
     * @return DryRunTransactionBlock
     */
    public function dryRunTransactionBlock(
        string|array|Transaction $transactionBlock,
        ?string $sender = null
    ): DryRunTransactionBlock {
        return new DryRunTransactionBlock($this->request('sui_dryRunTransactionBlock', [
            $this->transactionBlockToBase64($transactionBlock, $sender),
        ]));
    }

    /**
     * @param string $parentId
     * @param string|null $cursor
     * @param int|null $limit
     * @return PaginatedDynamicFieldInfo
     */
    public function getDynamicFields(
        string $parentId,
        ?string $cursor = null,
        ?int $limit = null
    ): PaginatedDynamicFieldInfo {
        return PaginatedDynamicFieldInfo::fromArray($this->request('suix_getDynamicFields', [
            $parentId,
            $cursor,
            $limit,
        ]));
    }

    /**
     * @param string $parentId
     * @param DynamicFieldName $name
     * @return SuiObjetData
     */
    public function getDynamicFieldObject(
        string $parentId,
        DynamicFieldName $name
    ): SuiObjetData {
        return new SuiObjetData($this->request('suix_getDynamicFieldObject', [
            $parentId,
            $name,
        ])['data'] ?? []);
    }

    /**
     * @return string
     */
    public function getLatestCheckpointSequenceNumber(): string
    {
        return $this->request('sui_getLatestCheckpointSequenceNumber');
    }

    /**
     * @param string $sequenceNumber
     * @return Checkpoint
     */
    public function getCheckpoint(string $sequenceNumber): Checkpoint
    {
        return new Checkpoint($this->request('sui_getCheckpoint', [
            $sequenceNumber,
        ]));
    }

    /**
     * @param string|null $cursor
     * @param integer|null $limit
     * @param string|null $order
     * @return PaginatedCheckpoints
     */
    public function getCheckpoints(
        ?string $cursor = null,
        ?int $limit = null,
        ?string $order = null
    ): PaginatedCheckpoints {
        return PaginatedCheckpoints::fromArray($this->request('sui_getCheckpoints', [
            $cursor,
            $limit,
            "descending" === ($order ?? "descending")
        ]));
    }

    /**
     * @param string|null $epoch
     * @return CommitteeInfo
     */
    public function getCommitteeInfo(?string $epoch = null): CommitteeInfo
    {
        return new CommitteeInfo($this->request('suix_getCommitteeInfo', [
            $epoch,
        ]));
    }

    /**
     * @return ValidatorsApy
     */
    public function getValidatorsApy(): ValidatorsApy
    {
        return new ValidatorsApy($this->request('suix_getValidatorsApy'));
    }
    /**
     * @return string
     */
    public function getChainIdentifier(): string
    {
        $checkpoint = $this->getCheckpoint('0');
        $bytes = Utils::fromBase58($checkpoint->digest);
        return Utils::toHex(array_slice($bytes, 0, 3));
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function resolveNameServiceAddress(string $name): ?string
    {
        return $this->request('suix_resolveNameServiceAddress', [$name]);
    }

    /**
     * @param string $address
     * @param string|null $cursor
     * @param integer|null $limit
     * @return PaginatedResolvedNameServiceNames
     */
    public function resolveNameServiceNames(
        string $address,
        ?string $cursor = null,
        ?int $limit = null
    ): PaginatedResolvedNameServiceNames {
        return PaginatedResolvedNameServiceNames::fromArray(
            $this->request('suix_resolveNameServiceNames', [$address, $cursor, $limit])
        );
    }

    /**
     * @param string|null $version
     * @return array<mixed>
     */
    public function getProtocolConfig(?string $version = null): array
    {
        return $this->request('sui_getProtocolConfig', [$version]);
    }

    /**
     * @param string $digest
     * @param array<mixed>|null $options
     * @param integer|null $timeout
     * @param integer|null $pollInterval
     * @return TransactionBlock
     */
    public function waitForTransaction(
        string $digest,
        ?array $options = null,
        ?int $timeout = 60000,
        ?int $pollInterval = 2000
    ): TransactionBlock {
        $startTime = microtime(true) * 1000;
        while (true) {
            $elapsed = (microtime(true) * 1000) - $startTime;
            if ($elapsed > $timeout) {
                throw new \RuntimeException("Timeout reached while waiting for transaction block.");
            }

            try {
                return $this->getTransactionBlock($digest, $options);
            } catch (\Exception $e) {
                $remainingTimeout = $timeout - $elapsed;
                $sleepTime = min($pollInterval, $remainingTimeout);
                if ($sleepTime > 0) {
                    usleep((int) ($sleepTime * 1000));
                }
            }
        }
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
