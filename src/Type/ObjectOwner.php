<?php

declare(strict_types=1);

namespace Sui\Type;

class ObjectOwner
{
    public string $type;

    public string | int $value;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        if ('Immutable' === $data) {
            $this->type = $data;
        } else {
            $ownerType = array_key_first($data);
            $this->type = $ownerType;

            if ('AddressOwner' == $ownerType || 'ObjectOwner' == $ownerType) {
                $this->value = $data[$ownerType];
            } else {
                $this->value = (int) $data[$ownerType]['initial_shared_version'];
            }
        }
    }
}
