<?php

declare(strict_types=1);

namespace Sui\Type;

class ObjectRead
{
    public string $status;

    public mixed $details;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->status = $data['status'];

        switch ($this->status) {
            case 'VersionFound':
                $this->details = new SuiObjetData($data['details']);
                break;
            case 'ObjectDeleted':
                $this->details = new SuiObjectRef($data['details']);
                break;
            default:
                $this->details = $data['details'];
        }
    }
}
