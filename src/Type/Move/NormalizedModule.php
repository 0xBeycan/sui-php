<?php

declare(strict_types=1);

namespace Sui\Type\Move;

// export interface SuiMoveNormalizedModule {
//     address: string;
//     enums?: {
//         [key: string]: SuiMoveNormalizedEnum;
//     };
//     exposedFunctions: {
//         [key: string]: SuiMoveNormalizedFunction;
//     };
//     fileFormatVersion: number;
//     friends: SuiMoveModuleId[];
//     name: string;
//     structs: {
//         [key: string]: SuiMoveNormalizedStruct;
//     };
// }

class NormalizedModule
{
    public string $address;

    /**
     * @var array<string,NormalizedEnum>|null
     */
    public ?array $enums;

    /**
     * @var array<string,NormalizedFunction>
     */
    public array $exposedFunctions;

    public int $fileFormatVersion;

    /**
     * @var array<string,ModuleId>
     */
    public array $friends;

    public string $name;

    /**
     * @var array<string,NormalizedStruct>
     */
    public array $structs;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->address = $data['address'];
        $this->fileFormatVersion = (int) ($data['fileFormatVersion'] ?? 0);
        $this->enums = isset($data['enums']) ? array_map(
            static fn(array $enum) => new NormalizedEnum($enum),
            $data['enums'] ?? []
        ) : null;
        $this->exposedFunctions = array_map(
            static fn(array $function) => new NormalizedFunction($function),
            $data['exposedFunctions']
        );
        $this->friends = array_map(
            static fn(array $friend) => new ModuleId($friend),
            $data['friends']
        );
        $this->structs = array_map(
            static fn(array $struct) => new NormalizedStruct($struct),
            $data['structs']
        );
    }
}
