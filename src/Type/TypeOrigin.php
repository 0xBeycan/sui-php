<?php

declare(strict_types=1);

namespace Sui\Type;

class TypeOrigin
{
    public string $datatypeName;

    public string $moduleName;

    public string $package;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->datatypeName = $data['datatype_name'];
        $this->moduleName = $data['module_name'];
        $this->package = $data['package'];
    }
}
