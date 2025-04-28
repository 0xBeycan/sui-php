<?php

declare(strict_types=1);

namespace Sui\Transactions;

class GasData
{
    private ?string $budget = null;

    private ?string $price = null;

    private ?string $owner = null;

    /**
     * @var array<ObjectRef>|null
     */
    private ?array $payment = null;

    /**
     * @param string|null $budget
     * @param string|null $price
     * @param string|null $owner
     * @param array<ObjectRef>|null $payment
     */
    public function __construct(
        ?string $budget = null,
        ?string $price = null,
        ?string $owner = null,
        ?array $payment = null
    ) {
        $this->budget = $budget;
        $this->price = $price;
        $this->owner = $owner;
        $this->payment = $payment;
    }

    /**
     * @return string|null
     */
    public function getBudget(): ?string
    {
        return $this->budget;
    }

    /**
     * @return string|null
     */
    public function getPrice(): ?string
    {
        return $this->price;
    }

    /**
     * @return string|null
     */
    public function getOwner(): ?string
    {
        return $this->owner;
    }

    /**
     * @return array<ObjectRef>|null
     */
    public function getPayment(): ?array
    {
        return $this->payment;
    }

    /**
     * @param string|null $budget
     * @return self
     */
    public function setBudget(?string $budget): self
    {
        $this->budget = $budget;
        return $this;
    }

    /**
     * @param string|null $price
     * @return self
     */
    public function setPrice(?string $price): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @param string|null $owner
     * @return self
     */
    public function setOwner(?string $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    /**
     * @param array<ObjectRef>|null $payment
     * @return self
     */
    public function setPayment(?array $payment): self
    {
        $this->payment = array_map(
            fn(ObjectRef $objectRef) => $objectRef,
            $payment ?? []
        );
        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function toArray(): array
    {
        return [
            'budget' => $this->budget,
            'price' => $this->price,
            'owner' => $this->owner,
            'payment' => $this->payment,
        ];
    }
}
