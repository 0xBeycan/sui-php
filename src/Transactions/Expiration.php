<?php

declare(strict_types=1);

namespace Sui\Transactions;

class Expiration
{
    private bool $none = true;

    private string $epoch;

    /**
     * @param string $epoch
     * @param bool $none
     */
    public function __construct(string $epoch, bool $none = true)
    {
        $this->none = $none;
        $this->epoch = $epoch;
    }

    /**
     * @return bool
     */
    public function isNone(): bool
    {
        return $this->none;
    }

    /**
     * @return string
     */
    public function getEpoch(): string
    {
        return $this->epoch;
    }

    /**
     * @param string $epoch
     * @return self
     */
    public function setEpoch(string $epoch): self
    {
        $this->epoch = $epoch;
        return $this;
    }

    /**
     * @param bool $none
     * @return self
     */
    public function setNone(bool $none): self
    {
        $this->none = $none;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'none' => $this->none,
            'epoch' => $this->epoch,
        ];
    }
}
