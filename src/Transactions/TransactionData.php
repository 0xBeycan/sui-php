<?php

declare(strict_types=1);

namespace Sui\Transactions;

class TransactionData
{
    private const VERSION = '2';

    protected string $sender;

    private GasData $gasData;

    private Expiration $expiration;

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * @param string $sender
     * @return void
     */
    public function setSenderIfNotSet(string $sender): void
    {
        if (!isset($this->sender)) {
            $this->sender = $sender;
        }
    }

    /**
     * @param string $sender
     * @return void
     */
    public function setSender(string $sender): void
    {
        $this->sender = $sender;
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @return GasData
     */
    public function getGasData(): GasData
    {
        return $this->gasData;
    }

    /**
     * @param GasData $gasData
     * @return void
     */
    public function setGasData(GasData $gasData): void
    {
        $this->gasData = $gasData;
    }

    /**
     * @return Expiration
     */
    public function getExpiration(): Expiration
    {
        return $this->expiration;
    }

    /**
     * @param Expiration $expiration
     * @return void
     */
    public function setExpiration(Expiration $expiration): void
    {
        $this->expiration = $expiration;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'sender' => $this->sender,
            'gasData' => $this->gasData->toArray(),
            'expiration' => $this->expiration->toArray(),
        ];
    }
}
