<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Transactions\Type\Command;
use Sui\Transactions\Type\Argument;

class Commands
{
    /**
     * @param array<mixed> $input
     * @return Command
     */
    public static function moveCall(array $input): Command
    {
        return Normalizer::command([
            'MoveCall' => $input,
        ]);
    }

    /**
     * @param array<Argument> $objects
     * @param Argument $address
     * @return Command
     */
    public static function transferObjects(array $objects, Argument $address): Command
    {
        return Normalizer::command([
            'TransferObjects' => [
                'objects' => $objects,
                'address' => $address,
            ],
        ]);
    }

    /**
     * @param Argument $coin
     * @param array<Argument> $amounts
     * @return Command
     */
    public static function splitCoins(Argument $coin, array $amounts): Command
    {
        return Normalizer::command([
            'SplitCoins' => [
                'coin' => $coin,
                'amounts' => $amounts,
            ],
        ]);
    }

    /**
     * @param Argument $destination
     * @param array<Argument> $sources
     * @return Command
     */
    public static function mergeCoins(Argument $destination, array $sources): Command
    {
        return Normalizer::command([
            'MergeCoins' => [
                'destination' => $destination,
                'sources' => $sources,
            ],
        ]);
    }

    /**
     * @param array<array<int>|string> $modules
     * @param array<string> $dependencies
     * @return Command
     */
    public static function publish(array $modules, array $dependencies): Command
    {
        return Normalizer::command([
            'Publish' => [
                'modules' => $modules,
                'dependencies' => $dependencies,
            ],
        ]);
    }

    /**
     * @param array<array<int>|string> $modules
     * @param array<string> $dependencies
     * @param string $packageId
     * @param Argument $ticket
     * @return Command
     */
    public static function upgrade(array $modules, array $dependencies, string $packageId, Argument $ticket): Command
    {
        return Normalizer::command([
            'Upgrade' => [
                'modules' => $modules,
                'dependencies' => $dependencies,
                'package' => $packageId,
                'ticket' => $ticket,
            ],
        ]);
    }

    /**
     * @param array<Argument> $elements
     * @param string|null $type
     * @return Command
     */
    public static function makeMoveVec(array $elements, ?string $type = null): Command
    {
        return Normalizer::command([
            'MakeMoveVec' => [
                'type' => $type,
                'elements' => $elements,
            ],
        ]);
    }

    /**
     * @param string $name
     * @param array<string, Argument|Argument[]> $inputs
     * @param array<string, mixed> $data
     * @return Command
     */
    public static function intent(string $name, array $inputs = [], array $data = []): Command
    {
        return Normalizer::command([
            '$Intent' => [
                'name' => $name,
                'inputs' => $inputs,
                'data' => $data,
            ],
        ]);
    }
}
