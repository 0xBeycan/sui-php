<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiEvent
{
    public EventId $id;

    public string $packageId;

    public mixed $parsedJson;

    public string $sender;

    public ?string $timestampMs;

    public string $transactionModule;

    public string $type;

    public string $bsc;

    public string $bscEncoding;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->id = new EventId($data['id']);
        $this->packageId = (string) $data['packageId'];
        $this->parsedJson = $data['parsedJson'];
        $this->sender = (string) $data['sender'];
        $this->timestampMs = (string) $data['timestampMs'];
        $this->transactionModule = (string) $data['transactionModule'];
        $this->type = (string) $data['type'];
        $this->bsc = (string) $data['bsc'];
        $this->bscEncoding = (string) $data['bscEncoding'];
    }
}
