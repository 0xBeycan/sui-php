<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

use Sui\Transactions\Type\Argument;

class MergeCoins extends Command
{
    /**
     * @var Argument
     */
    private Argument $destination;

    /**
     * @var array<Argument>
     */
    private array $sources;

    /**
     * @param Argument $destination The destination coin to merge into
     * @param array<Argument> $sources The source coins to merge from
     */
    public function __construct(Argument $destination, array $sources)
    {
        $this->destination = $destination;
        $this->sources = $sources;
    }

    /**
     * @return Argument
     */
    public function getDestination(): Argument
    {
        return $this->destination;
    }

    /**
     * @param Argument $destination
     * @return self
     */
    public function setDestination(Argument $destination): self
    {
        $this->destination = $destination;
        return $this;
    }

    /**
     * @return array<Argument>
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @param array<Argument> $sources
     * @return self
     */
    public function setSources(array $sources): self
    {
        $this->sources = $sources;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'destination' => $this->destination->toArray(),
            'sources' => array_map(fn(Argument $source) => $source->toArray(), $this->sources),
        ];
    }
}
