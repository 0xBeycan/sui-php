<?php

declare(strict_types=1);

namespace Sui\Transactions\Data;

use Sui\Transactions\TransactionData;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\GasData;
use Sui\Transactions\Type\Expiration;
use Sui\Transactions\Commands\Command;

class V2
{
    /**
     * @var string
     */
    private string $version;

    /**
     * @var string|null
     */
    private ?string $sender;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $expiration;

    /**
     * @var array<string, mixed>
     */
    private array $gasData;

    /**
     * @var array<int, CallArg>
     */
    private array $inputs;

    /**
     * @var array<int, Command>
     */
    private array $commands;

    /**
     * @param string $version
     * @param string|null $sender
     * @param array<string, mixed>|null $expiration
     * @param array<string, mixed> $gasData
     * @param array<int, CallArg> $inputs
     * @param array<int, Command> $commands
     */
    public function __construct(
        string $version,
        ?string $sender,
        ?array $expiration,
        array $gasData,
        array $inputs,
        array $commands
    ) {
        $this->version = $version;
        $this->sender = $sender;
        $this->expiration = $expiration;
        $this->gasData = $gasData;
        $this->inputs = $inputs;
        $this->commands = $commands;
    }

    /**
     * Get the version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get the sender
     *
     * @return string|null
     */
    public function getSender(): ?string
    {
        return $this->sender;
    }

    /**
     * Get the expiration
     *
     * @return array<string, mixed>|null
     */
    public function getExpiration(): ?array
    {
        return $this->expiration;
    }

    /**
     * Get the gas data
     *
     * @return array<string, mixed>
     */
    public function getGasData(): array
    {
        return $this->gasData;
    }

    /**
     * Get the inputs
     *
     * @return array<int, CallArg>
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * Get the commands
     *
     * @return array<int, Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Serializes a TransactionData object into V2 format
     *
     * @param TransactionData $transactionData The transaction data to serialize
     * @return self The serialized V2 transaction data
     */
    public static function serializeV2TransactionData(TransactionData $transactionData): self
    {
        $expiration = null;
        if (!$transactionData->getExpiration()->isNone()) {
            $expiration = ['Epoch' => $transactionData->getExpiration()->getEpoch()];
        } else {
            $expiration = ['None' => true];
        }

        $gasData = $transactionData->getGasData();
        $gasDataArray = [
            'budget' => $gasData->getBudget(),
            'price' => $gasData->getPrice(),
            'owner' => $gasData->getOwner(),
            'payment' => $gasData->getPayment(),
        ];

        return new self(
            '2',
            $transactionData->getSender(),
            $expiration,
            $gasDataArray,
            $transactionData->getInputs(),
            $transactionData->getCommands()
        );
    }

    /**
     * Creates a TransactionData object from V2 format
     *
     * @param self $data The V2 transaction data
     * @return TransactionData The created TransactionData object
     */
    public static function transactionDataFromV2(self $data): TransactionData
    {
        $expiration = null;
        if ($data->getExpiration()) {
            if (isset($data->getExpiration()['Epoch'])) {
                $expiration = new Expiration($data->getExpiration()['Epoch'], false);
            } else {
                $expiration = new Expiration('0', true);
            }
        }

        if (!$data->getSender()) {
            throw new \InvalidArgumentException('Sender is required');
        }

        if (!$expiration) {
            throw new \InvalidArgumentException('Expiration is required');
        }

        return new TransactionData(
            $data->getSender(),
            new GasData(
                $data->getGasData()['owner'] ?? null,
                $data->getGasData()['budget'] ?? null,
                $data->getGasData()['price'] ?? null,
                $data->getGasData()['payment'] ?? null
            ),
            $expiration,
            $data->getInputs(),
            $data->getCommands()
        );
    }

    /**
     * Converts the V2 transaction data to an array
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'sender' => $this->sender,
            'expiration' => $this->expiration,
            'gasData' => $this->gasData,
            'inputs' => $this->inputs,
            'commands' => $this->commands,
        ];
    }

    /**
     * @param array<mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['version'],
            $data['sender'],
            $data['expiration'],
            $data['gasData'],
            $data['inputs'],
            $data['commands'],
        );
    }
}
