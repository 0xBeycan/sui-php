<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiExecutionResult
{
    /**
     * @var array<SuiArgument,int[],string>|null
     */
    public ?array $mutableReferenceOutputs = [];

    /**
     * @var array<int[],string>|null
     */
    public ?array $returnValues = null;

    /**
     * @param array<mixed,> $data
     */
    public function __construct(array $data)
    {
        $this->mutableReferenceOutputs = isset($data['mutableReferenceOutputs']) ? array_map(
            fn($output) => [new SuiArgument($output[0]), $output[1], $output[2]],
            $data['mutableReferenceOutputs']
        ) : null;
        $this->returnValues = $data['returnValues'] ?? null;
    }
}
