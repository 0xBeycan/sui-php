<?php

declare(strict_types=1);

namespace Sui\Type;

class OwnedObjectRef
{
    public ObjectOwner $owner;

    public SuiObjectRef $reference;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->owner = new ObjectOwner($data['owner']);
        $this->reference = new SuiObjectRef($data['reference']);
    }
}
