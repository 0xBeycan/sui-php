<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

class Argument extends SafeEnum
{
    /**
     * @param string $kind
     * @param mixed $value
     * @param string|null $type
     * @param \Closure|null $hook
     */
    public function __construct(
        public string $kind,
        public mixed $value,
        public ?string $type = null,
        private ?\Closure $hook = null,
    ) {
        parent::__construct($kind, $value);
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        if ($this->hook) {
            return ($this->hook)($this, 'kind');
        }
        return $this->kind;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        if ($this->hook) {
            return ($this->hook)($this, 'value');
        }
        return $this->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $base = parent::toArray();
        if ($this->type) {
            $base['type'] = $this->type;
        }
        return $base;
    }
}
