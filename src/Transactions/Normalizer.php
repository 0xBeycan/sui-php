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
        return new Argument((string) array_keys($options)[0], $options);
    }

    /**
     * @param array<mixed> $options
     * @return GasData
     */
    public static function gasData(array $options): GasData
    {
        return new GasData(
            $options['budget'] ? self::jsonU64($options['budget']) : null,
            $options['price'] ? self::jsonU64($options['price']) : null,
            $options['owner'] ? self::suiAddress($options['owner']) : null,
            array_map(function ($value) {
                return self::objectRef($value);
            }, $options['payment'] ?? []),
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
            $options['typeParams'],
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
            $options['ref'] ?? null
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
            array_map(function (array $value) {
                if (is_array($value) && array_is_list($value)) {
                    return array_map(
                        fn($item) => self::argument($item),
                        $value
                    );
                } else {
                    return self::argument($value);
                }
            }, $options['inputs']),
            $options['data'],
        );
    }

    /**
     * @param array<mixed> $options
     * @return MoveCall
     */
    public static function moveCall(array $options): MoveCall
    {
        return new MoveCall(
            self::suiAddress($options['package']),
            $options['module'],
            $options['function'],
            $options['typeArguments'],
            array_map(function ($value) {
                return self::argument($value);
            }, $options['arguments']),
            isset($options['_argumentTypes']) ? array_map(function ($value) {
                return self::typeSignature($value);
            }, $options['_argumentTypes']) : null,
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
                return self::argument($value);
            }, $options['objects']),
            self::argument($options['address']),
        );
    }

    /**
     * @param array<mixed> $options
     * @return SplitCoins
     */
    public static function splitCoins(array $options): SplitCoins
    {
        return new SplitCoins(
            self::argument($options['coin']),
            array_map(function ($value) {
                return self::argument($value);
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
            self::argument($options['destination']),
            array_map(function ($value) {
                return self::argument($value);
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
            $options['modules'],
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
                return self::argument($value);
            }, $options['elements']),
            $options['type'] ?? null,
        );
    }

    /**
     * @param array<mixed> $options
     * @return Upgrade
     */
    public static function upgrade(array $options): Upgrade
    {
        return new Upgrade(
            $options['modules'],
            array_map(function ($value) {
                return self::suiAddress($value);
            }, $options['dependencies']),
            self::suiAddress($options['package']),
            self::argument($options['ticket']),
        );
    }

    /**
     * @param array<mixed> $options
     * @return string
     */
    private static function getKind(array $options): string
    {
        return array_keys($options)[0];
    }

    /**
     * @param array<mixed> $options
     * @return Command
     */
    public static function command(array $options): Command
    {
        switch (self::getKind($options)) {
            case 'Intent':
                return new Command('Intent', self::intent($options));
            case 'MoveCall':
                return new Command('MoveCall', self::moveCall($options));
            case 'TransferObjects':
                return new Command('TransferObjects', self::transferObjects($options));
            case 'SplitCoins':
                return new Command('SplitCoins', self::splitCoins($options));
            case 'MergeCoins':
                return new Command('MergeCoins', self::mergeCoins($options));
            case 'Publish':
                return new Command('Publish', self::publish($options));
            case 'MakeMoveVec':
                return new Command('MakeMoveVec', self::makeMoveVec($options));
            case 'Upgrade':
                return new Command('Upgrade', self::upgrade($options));
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
        switch (self::getKind($options)) {
            case 'ImmOrOwnedObject':
                return new ObjectArg('ImmOrOwnedObject', self::objectRef($options));
            case 'SharedObject':
                return new ObjectArg('SharedObject', self::sharedObject($options));
            case 'Receiving':
                return new ObjectArg('Receiving', self::objectRef($options));
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
            $options['version'] ? self::jsonU64($options['version']) : null,
            $options['digest'] ? $options['digest'] : null,
            $options['initialSharedVersion'] ? self::jsonU64($options['initialSharedVersion']) : null,
        );
    }

    /**
     * @param array<mixed> $options
     * @return CallArg
     */
    public static function callArg(array $options): CallArg
    {
        switch (self::getKind($options)) {
            case 'Object':
                return new CallArg('Object', self::objectArg($options));
            case 'Pure':
                return new CallArg('Pure', self::pure($options));
            case 'UnresolvedPure':
                return new CallArg('UnresolvedPure', self::unresolvedPure($options));
            case 'UnresolvedObject':
                return new CallArg('UnresolvedObject', self::unresolvedObject($options));
            default:
                throw new \Exception('Invalid call arg');
        }
    }

    /**
     * @param array<mixed> $options
     * @return NormalizedCallArg
     */
    public static function normalizedCallArg(array $options): NormalizedCallArg
    {
        switch (self::getKind($options)) {
            case 'Object':
                return new NormalizedCallArg('Object', self::objectArg($options));
            case 'Pure':
                return new NormalizedCallArg('Pure', self::pure($options));
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
            $options['Epoch'] ? self::jsonU64($options['Epoch']) : null
        );
    }

    /**
     * @param array<mixed> $options
     * @return TransactionData
     */
    public static function transactionData(array $options): TransactionData
    {
        return new TransactionData(
            $options['version'] ?? 2,
            self::gasData($options['gasData']),
            array_map(function ($callArg) {
                return self::callArg($callArg);
            }, $options['inputs']),
            array_map(function ($command) {
                return self::command($command);
            }, $options['commands']),
            $options['sender'] ? self::suiAddress($options['sender']) : null,
            $options['expiration'] ? self::transactionExpiration($options['expiration']) : null,
        );
    }
}
