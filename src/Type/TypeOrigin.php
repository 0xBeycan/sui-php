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

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'datatype_name' => $this->datatypeName,
            'module_name' => $this->moduleName,
            'package_id' => $this->package,
        ];
    }
}
