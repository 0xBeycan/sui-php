<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Utils;
use InvalidArgumentException;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\ObjectRef;
use Sui\Transactions\Type\StructTag;
use Sui\Transactions\Type\GasData;
use Sui\Transactions\Type\SharedObject;
use Sui\Transactions\Type\ObjectArg;
use Sui\Transactions\Type\Command;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\Pure;
use Sui\Transactions\Type\UnresolvedPure;
use Sui\Transactions\Type\UnresolvedObject;
use Sui\Transactions\Type\TypeSignature;
use Sui\Transactions\Type\NormalizedCallArg;
use Sui\Transactions\Type\TransactionData;
use Sui\Transactions\Type\TransactionExpiration;
use Sui\Transactions\Commands\Intent;
use Sui\Transactions\Commands\MoveCall;
use Sui\Transactions\Commands\TransferObjects;
use Sui\Transactions\Commands\SplitCoins;
use Sui\Transactions\Commands\MergeCoins;
use Sui\Transactions\Commands\Publish;
use Sui\Transactions\Commands\MakeMoveVec;
use Sui\Transactions\Commands\Upgrade;

class Normalizer
{
    /**
     * Normalize a Sui address
     *
     * @param string $address
     * @return string
     * @throws \Exception
     */
    public static function suiAddress(string $address): string
    {
        $address = Utils::normalizeSuiAddress($address);

        if (Utils::isValidSuiAddress($address)) {
            return $address;
        }

        throw new \Exception('Invalid Sui address');
    }

    /**
     * @param string|int|float $value
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function jsonU64(string|int|float $value): string
    {
        try {
            $num = (string)$value;
            if (bccomp($num, '0') < 0 || bccomp($num, '18446744073709551615') > 0) {
                throw new InvalidArgumentException('Value out of u64 range');
            }
            return $num;
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Invalid u64 value');
        }
    }

    /**
     * @param array<mixed> $options
     * @return ObjectRef
     */
    public static function objectRef(array $options): ObjectRef
    {
        return new ObjectRef(
            self::suiAddress($options['objectId']),
            self::jsonU64($options['version']),
            $options['digest']
        );
    }

    /**
     * @param array<mixed> $options
     * @return Argument
     */
    public static function argument(array $options): Argument
    {
        $kind = self::getKind($options);
        $value = $options[$kind];
        $type = $options['type'] ?? null;
        return new Argument((string) $kind, $value, $type);
    }

    /**
     * @param array<mixed> $options
     * @return GasData
     */
    public static function gasData(array $options): GasData
    {
        return new GasData(
            isset($options['budget']) ? self::jsonU64($options['budget']) : null,
            isset($options['price']) ? self::jsonU64($options['price']) : null,
            isset($options['owner']) ? self::suiAddress($options['owner']) : null,
            isset($options['payment']) ? array_map(function ($value) {
                return $value instanceof ObjectRef ? $value : self::objectRef($value);
            }, $options['payment']) : null,
        );
    }

    /**
     * @param array<mixed> $options
     * @return StructTag
     */
    public static function structTag(array $options): StructTag
    {
        return new StructTag(
            $options['address'],
            $options['module'],
            $options['name'],
            array_map(function ($value) {
                return $value instanceof StructTag
                    ? $value
                    : self::structTag($value);
            }, $options['typeParams'] ?? []),
        );
    }

    /**
     * @param array<mixed> $options
     * @return TypeSignature
     */
    public static function typeSignature(array $options): TypeSignature
    {
        return new TypeSignature(
            $options['body'],
            isset($options['ref']) ? $options['ref'] : null
        );
    }

    /**
     * @param array<mixed> $options
     * @return Intent
     */
    public static function intent(array $options): Intent
    {
        return new Intent(
            $options['name'],
            array_map(function (mixed $value) {
                if (is_array($value) && array_is_list($value)) {
                    return array_map(
                        fn($item) => $item instanceof Argument
                            ? $item
                            : self::argument($item),
                        $value
                    );
                } else {
                    return $value instanceof Argument
                        ? $value
                        : self::argument($value);
                }
            }, $options['inputs']),
            $options['data'],
        );
    }

    /**
     * @param array<mixed> $input
     * @return MoveCall
     */
    public static function moveCall(array $input): MoveCall
    {
        if (isset($input['target'])) {
            [$package, $module, $function] = array_pad(explode('::', $input['target']), 3, '');
        } else {
            $package = $input['package'];
            $module = $input['module'];
            $function = $input['function'];
        }

        return new MoveCall(
            self::suiAddress($package),
            $module,
            $function,
            $input['typeArguments'] ?? [],
            array_map(function ($value) {
                return $value instanceof Argument
                    ? $value
                    : self::argument($value);
            }, $input['arguments'] ?? []),
            isset($input['_argumentTypes']) ? array_map(function ($value) {
                return $value instanceof TypeSignature
                    ? $value
                    : self::typeSignature($value);
            }, $input['_argumentTypes']) : null,
        );
    }

    /**
     * @param array<mixed> $options
     * @return TransferObjects
     */
    public static function transferObjects(array $options): TransferObjects
    {
        return new TransferObjects(
            array_map(function ($value) {
                return $value instanceof Argument
                    ? $value
                    : self::argument($value);
            }, $options['objects']),
            $options['address'] instanceof Argument
                ? $options['address']
                : self::argument($options['address']),
        );
    }

    /**
     * @param array<mixed> $options
     * @return SplitCoins
     */
    public static function splitCoins(array $options): SplitCoins
    {
        return new SplitCoins(
            $options['coin'] instanceof Argument
                ? $options['coin']
                : self::argument($options['coin']),
            array_map(function ($value) {
                return $value instanceof Argument
                    ? $value
                    : self::argument($value);
            }, $options['amounts']),
        );
    }

    /**
     * @param array<mixed> $options
     * @return MergeCoins
     */
    public static function mergeCoins(array $options): MergeCoins
    {
        return new MergeCoins(
            $options['destination'] instanceof Argument
                ? $options['destination']
                : self::argument($options['destination']),
            array_map(function ($value) {
                return $value instanceof Argument
                    ? $value
                    : self::argument($value);
            }, $options['sources']),
        );
    }

    /**
     * @param array<mixed> $options
     * @return Publish
     */
    public static function publish(array $options): Publish
    {
        return new Publish(
            array_map(function ($value) {
                return is_string($value) ? $value : Utils::toBase64($value);
            }, $options['modules']),
            array_map(function ($value) {
                return self::suiAddress($value);
            }, $options['dependencies']),
        );
    }

    /**
     * @param array<mixed> $options
     * @return MakeMoveVec
     */
    public static function makeMoveVec(array $options): MakeMoveVec
    {
        return new MakeMoveVec(
            array_map(function ($value) {
                return $value instanceof Argument ? $value : self::argument($value);
            }, $options['elements']),
            isset($options['type']) ? $options['type'] : null,
        );
    }

    /**
     * @param array<mixed> $options
     * @return Upgrade
     */
    public static function upgrade(array $options): Upgrade
    {
        return new Upgrade(
            array_map(function ($value) {
                return is_string($value) ? $value : Utils::toBase64($value);
            }, $options['modules']),
            array_map(function ($value) {
                return self::suiAddress($value);
            }, $options['dependencies']),
            self::suiAddress($options['package']),
            $options['ticket'] instanceof Argument ? $options['ticket'] : self::argument($options['ticket']),
        );
    }

    /**
     * @param array<mixed> $options
     * @return string
     */
    private static function getKind(array $options): string
    {
        return isset($options['$kind']) ? $options['$kind'] : array_keys($options)[0];
    }

    /**
     * @param array<mixed> $options
     * @return Command
     */
    public static function command(array $options): Command
    {
        $kind = self::getKind($options);
        $command = $options[$kind];
        switch ($kind) {
            case '$Intent':
                $command = self::intent($command);
                return new Command('$Intent', $command);
            case 'MoveCall':
                $command = self::moveCall($command);
                return new Command('MoveCall', $command);
            case 'TransferObjects':
                $command = self::transferObjects($command);
                return new Command('TransferObjects', $command);
            case 'SplitCoins':
                $command = self::splitCoins($command);
                return new Command('SplitCoins', $command);
            case 'MergeCoins':
                $command = self::mergeCoins($command);
                return new Command('MergeCoins', $command);
            case 'Publish':
                $command = self::publish($command);
                return new Command('Publish', $command);
            case 'MakeMoveVec':
                $command = self::makeMoveVec($command);
                return new Command('MakeMoveVec', $command);
            case 'Upgrade':
                $command = self::upgrade($command);
                return new Command('Upgrade', $command);
            default:
                throw new \Exception('Invalid command');
        }
    }

    /**
     * @param array<mixed> $options
     * @return SharedObject
     */
    public static function sharedObject(array $options): SharedObject
    {
        return new SharedObject(
            self::suiAddress($options['objectId']),
            self::jsonU64($options['initialSharedVersion']),
            $options['mutable'],
        );
    }

    /**
     * @param array<mixed> $options
     * @return ObjectArg
     */
    public static function objectArg(array $options): ObjectArg
    {
        $kind = self::getKind($options);
        $objectArg = $options[$kind];
        switch ($kind) {
            case 'ImmOrOwnedObject':
                $objectArg = self::objectRef($objectArg);
                return new ObjectArg('ImmOrOwnedObject', $objectArg);
            case 'SharedObject':
                $objectArg = self::sharedObject($objectArg);
                return new ObjectArg('SharedObject', $objectArg);
            case 'Receiving':
                $objectArg = self::objectRef($objectArg);
                return new ObjectArg('Receiving', $objectArg);
            default:
                throw new \Exception('Invalid object arg');
        }
    }

    /**
     * @param array<mixed> $options
     * @return Pure
     */
    public static function pure(array $options): Pure
    {
        return new Pure($options['bytes']);
    }

    /**
     * @param array<mixed> $options
     * @return UnresolvedPure
     */
    public static function unresolvedPure(array $options): UnresolvedPure
    {
        return new UnresolvedPure($options['value']);
    }

    /**
     * @param array<mixed> $options
     * @return UnresolvedObject
     */
    public static function unresolvedObject(array $options): UnresolvedObject
    {
        return new UnresolvedObject(
            self::suiAddress($options['objectId']),
            isset($options['version']) ? self::jsonU64($options['version']) : null,
            isset($options['digest']) ? $options['digest'] : null,
            isset($options['initialSharedVersion']) ? self::jsonU64($options['initialSharedVersion']) : null,
        );
    }

    /**
     * @param array<mixed> $options
     * @return CallArg
     */
    public static function callArg(array $options): CallArg
    {
        $kind = self::getKind($options);
        $callArg = $options[$kind];
        switch ($kind) {
            case 'Object':
                $callArg = self::objectArg($callArg);
                return new CallArg('Object', $callArg);
            case 'Pure':
                $callArg = self::pure($callArg);
                return new CallArg('Pure', $callArg);
            case 'UnresolvedPure':
                $callArg = self::unresolvedPure($callArg);
                return new CallArg('UnresolvedPure', $callArg);
            case 'UnresolvedObject':
                $callArg = self::unresolvedObject($callArg);
                return new CallArg('UnresolvedObject', $callArg);
            default:
                throw new \Exception('Invalid call arg ' . $kind);
        }
    }

    /**
     * @param array<mixed> $options
     * @return NormalizedCallArg
     */
    public static function normalizedCallArg(array $options): NormalizedCallArg
    {
        $kind = self::getKind($options);
        $normalizedCallArg = $options[$kind];
        switch ($kind) {
            case 'Object':
                $normalizedCallArg = self::objectArg($normalizedCallArg);
                return new NormalizedCallArg('Object', $normalizedCallArg);
            case 'Pure':
                $normalizedCallArg = self::pure($normalizedCallArg);
                return new NormalizedCallArg('Pure', $normalizedCallArg);
            default:
                throw new \Exception('Invalid normalized call arg');
        }
    }

    /**
     * @param array<mixed> $options
     * @return TransactionExpiration
     */
    public static function transactionExpiration(array $options): TransactionExpiration
    {
        return new TransactionExpiration(
            self::getKind($options),
            $options['None'] ?? false,
            isset($options['Epoch']) ? self::jsonU64($options['Epoch']) : null
        );
    }

    /**
     * @param array<mixed> $options
     * @return TransactionData
     */
    public static function transactionData(array $options): TransactionData
    {
        if (isset($options['expiration'])) {
            $expiration = $options['expiration'] instanceof TransactionExpiration
                ? $options['expiration']
                : self::transactionExpiration($options['expiration']);
        } else {
            $expiration = null;
        }
        $gasData = $options['gasData'] ?? null;
        if ($gasData) {
            $gasData = $gasData instanceof GasData
                ? $gasData
                : self::gasData($gasData);
        } else {
            $gasData = self::gasData([]);
        }
        $sender = $options['sender'] ? self::suiAddress($options['sender']) : null;
        return new TransactionData(
            $options['version'] ?? 2,
            $gasData,
            array_map(function ($callArg) {
                return $callArg instanceof CallArg
                    ? $callArg
                    : self::callArg($callArg);
            }, $options['inputs'] ?? []),
            array_map(function ($command) {
                return $command instanceof Command
                    ? $command
                    : self::command($command);
            }, $options['commands'] ?? []),
            $sender,
            $expiration,
        );
    }
}
