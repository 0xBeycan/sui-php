<?php

declare(strict_types=1);

namespace Sui\Type;

class ValidatorsApy
{
    /**
     * @var array<ValidatorApy>
     */
    public array $apys;

    public string $epoch;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->apys = array_map(
            fn($item) => new ValidatorApy($item),
            $data['apys']
        );
        $this->epoch = $data['epoch'];
    }
}
