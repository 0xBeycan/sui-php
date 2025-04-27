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

    public string $bcs;

    public string $bcsEncoding;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->id = new EventId($data['id']);
        $this->packageId = $data['packageId'];
        $this->parsedJson = $data['parsedJson'];
        $this->sender = $data['sender'];
        $this->timestampMs = $data['timestampMs'];
        $this->transactionModule = $data['transactionModule'];
        $this->type = $data['type'];
        $this->bcs = $data['bcs'];
        $this->bcsEncoding = $data['bcsEncoding'];
    }
}
