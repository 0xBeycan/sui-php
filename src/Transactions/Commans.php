<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Utils;
use Sui\Transactions\Commands\MoveCall;
use Sui\Transactions\Commands\TransferObjects;
use Sui\Transactions\Commands\SplitCoins;
use Sui\Transactions\Commands\MergeCoins;
use Sui\Transactions\Commands\Publish;
use Sui\Transactions\Commands\Upgrade;
use Sui\Transactions\Commands\MakeMoveVec;
use Sui\Transactions\Type\Argument;

class Commands
{
    public const UPGRADE_POLICY_COMPATIBLE = 0;
    public const UPGRADE_POLICY_ADDITIVE = 128;
    public const UPGRADE_POLICY_DEP_ONLY = 192;

    /**
     * @param array<mixed> $input The input parameters for the move call
     * @return MoveCall
     */
    public static function moveCall(array $input): MoveCall
    {
        if (isset($input['target'])) {
            [$pkg, $mod, $fn] = array_pad(explode('::', $input['target']), 3, '');
        } else {
            $pkg = $input['package'] ?? '';
            $mod = $input['module'] ?? '';
            $fn = $input['function'] ?? '';
        }

        return new MoveCall(
            $pkg,
            $mod,
            $fn,
            $input['typeArguments'] ?? [],
            $input['arguments'] ?? []
        );
    }

    /**
     * @param Argument[] $objects The objects to transfer
     * @param Argument $address The destination address
     * @return TransferObjects
     */
    public static function transferObjects(array $objects, Argument $address): TransferObjects
    {
        return new TransferObjects($objects, $address);
    }

    /**
     * @param Argument $coin The coin to split
     * @param Argument[] $amounts The amounts to split into
     * @return SplitCoins
     */
    public static function splitCoins(Argument $coin, array $amounts): SplitCoins
    {
        return new SplitCoins($coin, $amounts);
    }

    /**
     * @param Argument $destination The destination coin
     * @param Argument[] $sources The source coins to merge
     * @return MergeCoins
     */
    public static function mergeCoins(Argument $destination, array $sources): MergeCoins
    {
        return new MergeCoins($destination, $sources);
    }

    /**
     * @param array<mixed> $input The input parameters for publishing
     * @return Publish
     */
    public static function publish(array $input): Publish
    {
        return new Publish(
            array_map(
                fn($module) => is_string($module) ? $module : Utils::toBase64($module),
                $input['modules']
            ),
            array_map(
                fn($dep) => Utils::normalizeSuiObjectId($dep),
                $input['dependencies']
            )
        );
    }

    /**
     * @param array<mixed> $input The input parameters for upgrading
     * @return Upgrade
     */
    public static function upgrade(array $input): Upgrade
    {
        return new Upgrade(
            array_map(
                fn($module) => is_string($module) ? $module : Utils::toBase64($module),
                $input['modules']
            ),
            array_map(
                fn($dep) => Utils::normalizeSuiObjectId($dep),
                $input['dependencies']
            ),
            $input['package'],
            $input['ticket']
        );
    }

    /**
     * @param array<mixed> $input The input parameters for making a move vector
     * @return MakeMoveVec
     */
    public static function makeMoveVec(array $input): MakeMoveVec
    {
        return new MakeMoveVec(
            $input['type'] ?? null,
            $input['elements']
        );
    }

    /**
     * @param array<mixed> $input The input parameters for the intent
     * @return array<mixed>
     */
    public static function intent(array $input): array
    {
        $inputs = [];
        foreach ($input['inputs'] ?? [] as $key => $value) {
            $inputs[$key] = is_array($value) ? $value : [$value];
        }

        return [
            'name' => $input['name'],
            'inputs' => $inputs,
            'data' => $input['data'] ?? []
        ];
    }
}
