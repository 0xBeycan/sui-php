<?php

declare(strict_types=1);

namespace Sui\Type;

class TransactionEffects
{
    /**
     * @var array<OwnedObjectRef>|null
     */
    public ?array $created;

    /**
     * @var array<SuiObjectRef>|null
     */
    public ?array $deleted;

    /**
     * @var array<string>|null
     */
    public ?array $dependencies;

    public ?string $eventsDigest;

    public string $executedEpoch;

    public OwnedObjectRef $gasObject;

    public GasCostSummary $gasUsed;

    public string $messageVersion;

    /**
     * @var array<ModifiedAtVersions>|null
     */
    public ?array $modifiedAtVersions;

    /**
     * @var array<OwnedObjectRef>|null
     */
    public ?array $mutated;

    /**
     * @var array<SuiObjectRef>|null
     */
    public ?array $sharedObjects;

    public ExecutionStatus $status;

    public string $transactionDigest;

    /**
     * @var array<OwnedObjectRef>|null
     */
    public ?array $unwrapped;

    /**
     * @var array<SuiObjectRef>|null
     */
    public ?array $unwrappedThenDeleted;

    /**
     * @var array<SuiObjectRef>|null
     */
    public ?array $wrapped;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->dependencies = $data['dependencies'] ?? null;
        $this->eventsDigest = $data['eventsDigest'] ?? null;
        $this->executedEpoch = $data['executedEpoch'];
        $this->gasObject = new OwnedObjectRef($data['gasObject']);
        $this->gasUsed = new GasCostSummary($data['gasUsed']);
        $this->messageVersion = $data['messageVersion'];
        $this->status = new ExecutionStatus($data['status']);
        $this->transactionDigest = $data['transactionDigest'];

        $this->created = isset($data['created']) ? array_map(
            static fn(array $item) => new OwnedObjectRef($item),
            $data['created']
        ) : null;
        $this->deleted = isset($data['deleted']) ? array_map(
            static fn(array $item) => new SuiObjectRef($item),
            $data['deleted']
        ) : null;
        $this->modifiedAtVersions = isset($data['modifiedAtVersions']) ? array_map(
            static fn(array $item) => new ModifiedAtVersions($item),
            $data['modifiedAtVersions']
        ) : null;
        $this->mutated = isset($data['mutated']) ? array_map(
            static fn(array $item) => new OwnedObjectRef($item),
            $data['mutated']
        ) : null;
        $this->sharedObjects = isset($data['sharedObjects']) ? array_map(
            static fn(array $item) => new SuiObjectRef($item),
            $data['sharedObjects']
        ) : null;
        $this->unwrapped = isset($data['unwrapped']) ? array_map(
            static fn(array $item) => new OwnedObjectRef($item),
            $data['unwrapped']
        ) : null;
        $this->unwrappedThenDeleted = isset($data['unwrappedThenDeleted']) ? array_map(
            static fn(array $item) => new SuiObjectRef($item),
            $data['unwrappedThenDeleted']
        ) : null;
        $this->wrapped = isset($data['wrapped']) ? array_map(
            static fn(array $item) => new SuiObjectRef($item),
            $data['wrapped']
        ) : null;
    }
}
