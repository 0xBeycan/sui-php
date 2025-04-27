<?php

declare(strict_types=1);

namespace Sui\Type;

class ExecutionStatus
{
    public string $status;

    public ?string $error;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->status = $data['status'];
        $this->error = $data['error'] ?? null;
    }
}
