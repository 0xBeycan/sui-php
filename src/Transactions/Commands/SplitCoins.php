<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Argument;

class SplitCoins extends Command
{
    /**
     * @var Argument
     */
    private Argument $coin;

    /**
     * @var array<Argument>
     */
    private array $amounts;

    /**
     * @param Argument $coin The coin to split
     * @param array<Argument> $amounts The amounts to split the coin into
     */
    public function __construct(Argument $coin, array $amounts)
    {
        $this->coin = $coin;
        $this->amounts = $amounts;
    }

    /**
     * @return Argument
     */
    public function getCoin(): Argument
    {
        return $this->coin;
    }

    /**
     * @param Argument $coin
     * @return self
     */
    public function setCoin(Argument $coin): self
    {
        $this->coin = $coin;
        return $this;
    }

    /**
     * @return array<Argument>
     */
    public function getAmounts(): array
    {
        return $this->amounts;
    }

    /**
     * @param array<Argument> $amounts
     * @return self
     */
    public function setAmounts(array $amounts): self
    {
        $this->amounts = $amounts;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'coin' => $this->coin->toArray(),
            'amounts' => array_map(fn(Argument $amount) => $amount->toArray(), $this->amounts),
        ];
    }
}
