<?php

declare(strict_types=1);

namespace Sui\Type;

class CommitteeInfo
{
    public string $epoch;

    /**
     * @var array<array<string,string>>
     */
    public array $validators;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->epoch = $data['epoch'];
        $this->validators = $data['validators'];
    }
}
