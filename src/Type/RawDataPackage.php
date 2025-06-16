<?php

declare(strict_types=1);

namespace Sui\Type;

class RawDataPackage
{
    public string $id;

    /**
     * @var array<string,UpgradeInfo>
     */
    public array $linkageTable;

    /**
     * @var array<string,string>
     */
    public array $moduleMap;

    /**
     * @var array<TypeOrigin>
     */
    public array $typeOriginTable;

    public string $version;

    /**
     * @param mixed $data
     */
    public function __construct(mixed $data)
    {
        $this->id = $data['id'];
        $this->version = $data['version'];
        $this->moduleMap = $data['moduleMap'];

        $this->linkageTable = array_map(
            fn(array $item) => new UpgradeInfo($item),
            $data['linkageTable']
        );

        $this->typeOriginTable = array_map(
            fn(array $item) => new TypeOrigin($item),
            $data['typeOriginTable']
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'version' => $this->version,
            'moduleMap' => $this->moduleMap,
            'linkageTable' => array_map(
                fn(UpgradeInfo $item) => $item->toArray(),
                $this->linkageTable
            ),
            'typeOriginTable' => array_map(
                fn(TypeOrigin $item) => $item->toArray(),
                $this->typeOriginTable
            ),
        ];
    }
}
