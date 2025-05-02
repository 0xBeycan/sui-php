<?php

declare(strict_types=1);

namespace Sui\Transactions\Type;

#[\AllowDynamicProperties]
abstract class SafeEnum
{
    /**
     * @param string $kind
     * @param mixed $value
     */
    public function __construct(
        public string $kind,
        public mixed $value,
    ) {
        $this->{$this->kind} = $value;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set(string $name, mixed $value): void
    {
        $this->{$name} = $value;
    }

    /**
     * @param string $name
     * @return string
     */
    public function __get(string $name): string
    {
        return $this->{$name};
    }
}
