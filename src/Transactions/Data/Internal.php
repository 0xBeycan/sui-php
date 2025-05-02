<?php

declare(strict_types=1);

namespace Sui\Transactions\Data;

use Sui\Utils;
use InvalidArgumentException;
use Sui\Transactions\TransactionData;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\ObjectRef;
use Sui\Transactions\Type\ObjectArg;
use Sui\Transactions\Type\SharedObject;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\UnresolvedObject;
use Sui\Transactions\Type\GasData;
use Sui\Transactions\Type\Expiration;
use Sui\Transactions\Commands\Command;
use Sui\Transactions\Commands\MoveCall;
use Sui\Transactions\Commands\TransferObjects;
use Sui\Transactions\Commands\SplitCoins;
use Sui\Transactions\Commands\MergeCoins;
use Sui\Transactions\Commands\Publish;
use Sui\Transactions\Commands\MakeMoveVec;
use Sui\Transactions\Commands\Upgrade;

class Internal
{
    /**
     * Validates and normalizes a u64 number
     *
     * @param string|int|float $value The value to validate
     * @return string The normalized u64 value
     * @throws InvalidArgumentException If the value is not a valid u64
     */
    public static function normalizeU64(string|int|float $value): string
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
     * Validates and normalizes ObjectRef structure
     *
     * @param array<mixed> $data The data to validate
     * @return ObjectRef The normalized ObjectRef
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeObjectRef(array $data): ObjectRef
    {
        if (!isset($data['objectId']) || !isset($data['version']) || !isset($data['digest'])) {
            throw new InvalidArgumentException('Missing required fields in ObjectRef');
        }

        return new ObjectRef(
            Utils::normalizeSuiAddress($data['objectId']),
            self::normalizeU64($data['version']),
            $data['digest']
        );
    }

    /**
     * Validates and normalizes Argument structure
     *
     * @param array<mixed> $data The data to validate
     * @return Argument The normalized Argument
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeArgument(array $data): Argument
    {
        if (!isset($data['$kind'])) {
            throw new InvalidArgumentException('Missing $kind in Argument');
        }

        switch ($data['$kind']) {
            case 'GasCoin':
                if (!isset($data['GasCoin']) || true !== $data['GasCoin']) {
                    throw new InvalidArgumentException('Invalid GasCoin argument');
                }
                return new Argument(0, 'GasCoin', '', 0, [], true);
            case 'Input':
                if (!isset($data['Input']) || !is_int($data['Input'])) {
                    throw new InvalidArgumentException('Invalid Input argument');
                }
                if (isset($data['type']) && !in_array($data['type'], ['pure', 'object'])) {
                    throw new InvalidArgumentException('Invalid Input type');
                }
                return new Argument($data['Input'], 'Input', $data['type'] ?? '', 0);
            case 'Result':
                if (!isset($data['Result']) || !is_int($data['Result'])) {
                    throw new InvalidArgumentException('Invalid Result argument');
                }
                return new Argument(0, 'Result', '', $data['Result']);
            case 'NestedResult':
                if (
                    !isset($data['NestedResult'])
                    || !is_array($data['NestedResult'])
                    || 2 !== count($data['NestedResult'])
                    || !is_int($data['NestedResult'][0])
                    || !is_int($data['NestedResult'][1])
                ) {
                    throw new InvalidArgumentException('Invalid NestedResult argument');
                }
                return new Argument(0, 'NestedResult', '', 0, $data['NestedResult']);
            default:
                throw new InvalidArgumentException('Unknown Argument type');
        }
    }

    /**
     * Validates and normalizes GasData structure
     *
     * @param array<mixed> $data The data to validate
     * @return GasData The normalized GasData
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeGasData(array $data): GasData
    {
        $normalized = [];

        $normalized['budget'] = isset($data['budget']) ? self::normalizeU64($data['budget']) : null;
        $normalized['price'] = isset($data['price']) ? self::normalizeU64($data['price']) : null;
        $normalized['owner'] = isset($data['owner']) ? Utils::normalizeSuiAddress($data['owner']) : null;
        $normalized['payment'] = isset($data['payment']) ? array_map(
            fn($item) => self::normalizeObjectRef($item),
            $data['payment']
        ) : null;

        return new GasData(
            $normalized['budget'] ?? null,
            $normalized['price'] ?? null,
            $normalized['owner'] ?? null,
            $normalized['payment'] ?? null
        );
    }

    /**
     * Validates and normalizes StructTag structure
     *
     * @param array<mixed> $data The data to validate
     * @return array<mixed> The normalized StructTag
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeStructTag(array $data): array
    {
        if (
            !isset($data['address'])
            || !isset($data['module'])
            || !isset($data['name'])
            || !isset($data['typeParams'])
        ) {
            throw new InvalidArgumentException('Missing required fields in StructTag');
        }

        if (
            !is_string($data['address'])
            || !is_string($data['module'])
            || !is_string($data['name'])
            || !is_array($data['typeParams'])
        ) {
            throw new InvalidArgumentException('Invalid field types in StructTag');
        }

        foreach ($data['typeParams'] as $param) {
            if (!is_string($param)) {
                throw new InvalidArgumentException('Invalid type parameter in StructTag');
            }
        }

        return $data;
    }

    /**
     * Validates and normalizes OpenMoveTypeSignature structure
     *
     * @param array<mixed> $data The data to validate
     * @return array<mixed> The normalized OpenMoveTypeSignature
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeOpenMoveTypeSignature(array $data): array
    {
        if (!isset($data['body'])) {
            throw new InvalidArgumentException('Missing body in OpenMoveTypeSignature');
        }

        if (isset($data['ref']) && !in_array($data['ref'], ['&', '&mut', null])) {
            throw new InvalidArgumentException('Invalid ref in OpenMoveTypeSignature');
        }

        $normalized = ['body' => self::normalizeOpenMoveTypeSignatureBody($data['body'])];
        if (isset($data['ref'])) {
            $normalized['ref'] = $data['ref'];
        }

        return $normalized;
    }

    /**
     * Validates and normalizes OpenMoveTypeSignatureBody
     *
     * @param mixed $body The body to validate
     * @return mixed The normalized body
     * @throws InvalidArgumentException If the body is invalid
     */
    private static function normalizeOpenMoveTypeSignatureBody(mixed $body): mixed
    {
        if (is_string($body)) {
            if (!in_array($body, ['address', 'bool', 'u8', 'u16', 'u32', 'u64', 'u128', 'u256'])) {
                throw new InvalidArgumentException('Invalid primitive type in OpenMoveTypeSignatureBody');
            }
            return $body;
        }

        if (is_array($body)) {
            if (isset($body['vector'])) {
                return ['vector' => self::normalizeOpenMoveTypeSignatureBody($body['vector'])];
            }

            if (isset($body['datatype'])) {
                $datatype = $body['datatype'];
                if (
                    !isset($datatype['package'])
                    || !isset($datatype['module'])
                    || !isset($datatype['type'])
                    || !isset($datatype['typeParameters'])
                ) {
                    throw new InvalidArgumentException('Missing required fields in datatype');
                }

                if (
                    !is_string($datatype['package'])
                    || !is_string($datatype['module'])
                    || !is_string($datatype['type'])
                    || !is_array($datatype['typeParameters'])
                ) {
                    throw new InvalidArgumentException('Invalid field types in datatype');
                }

                return [
                    'datatype' => [
                        'package' => $datatype['package'],
                        'module' => $datatype['module'],
                        'type' => $datatype['type'],
                        'typeParameters' => array_map(
                            fn($item) => self::normalizeOpenMoveTypeSignatureBody($item),
                            $datatype['typeParameters']
                        )
                    ]
                ];
            }

            if (isset($body['typeParameter'])) {
                if (!is_int($body['typeParameter'])) {
                    throw new InvalidArgumentException('Invalid type parameter');
                }
                return $body;
            }
        }

        throw new InvalidArgumentException('Invalid OpenMoveTypeSignatureBody');
    }

    /**
     * Validates and normalizes ProgrammableMoveCall structure
     *
     * @param array<mixed> $data The data to validate
     * @return array<mixed> The normalized ProgrammableMoveCall
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeProgrammableMoveCall(array $data): array
    {
        if (
            !isset($data['package'])
            || !isset($data['module'])
            || !isset($data['function'])
            || !isset($data['typeArguments'])
            || !isset($data['arguments'])
        ) {
            throw new InvalidArgumentException('Missing required fields in ProgrammableMoveCall');
        }

        $normalized = [
            'package' => Utils::normalizeSuiAddress($data['package']),
            'module' => $data['module'],
            'function' => $data['function'],
            'typeArguments' => $data['typeArguments'],
            'arguments' => array_map(
                fn($item) => self::normalizeArgument($item),
                $data['arguments']
            )
        ];

        $normalized['_argumentTypes'] = isset($data['_argumentTypes']) ? array_map(
            fn($item) => self::normalizeOpenMoveTypeSignature($item),
            $data['_argumentTypes']
        ) : null;

        return $normalized;
    }

    /**
     * Validates and normalizes Command structure
     *
     * @param array<mixed> $data The data to validate
     * @return Command The normalized Command
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeCommand(array $data): Command
    {
        if (!isset($data['$kind'])) {
            throw new InvalidArgumentException('Missing $kind in Command');
        }

        switch ($data['$kind']) {
            case 'MoveCall':
                return new MoveCall(
                    $data['package'],
                    $data['module'],
                    $data['function'],
                    $data['typeArguments'] ?? [],
                    array_map(fn($arg) => self::normalizeArgument($arg), $data['arguments'] ?? []),
                    $data['_argumentTypes'] ?? null
                );
            case 'TransferObjects':
                if (!isset($data['objects']) || !isset($data['address'])) {
                    throw new InvalidArgumentException('Missing required fields in TransferObjects');
                }
                return new TransferObjects(
                    array_map(fn($obj) => self::normalizeArgument($obj), $data['objects']),
                    self::normalizeArgument($data['address'])
                );
            case 'SplitCoins':
                if (!isset($data['coin']) || !isset($data['amounts'])) {
                    throw new InvalidArgumentException('Missing required fields in SplitCoins');
                }
                return new SplitCoins(
                    self::normalizeArgument($data['coin']),
                    array_map(fn($amount) => self::normalizeArgument($amount), $data['amounts'])
                );
            case 'MergeCoins':
                if (!isset($data['destination']) || !isset($data['sources'])) {
                    throw new InvalidArgumentException('Missing required fields in MergeCoins');
                }
                return new MergeCoins(
                    self::normalizeArgument($data['destination']),
                    array_map(fn($source) => self::normalizeArgument($source), $data['sources'])
                );
            case 'Publish':
                if (!isset($data['modules']) || !isset($data['dependencies'])) {
                    throw new InvalidArgumentException('Missing required fields in Publish');
                }
                return new Publish($data['modules'], $data['dependencies']);
            case 'MakeMoveVec':
                if (!isset($data['elements'])) {
                    throw new InvalidArgumentException('Missing required fields in MakeMoveVec');
                }
                return new MakeMoveVec(
                    array_map(fn($element) => self::normalizeArgument($element), $data['elements']),
                    $data['type'] ?? null
                );
            case 'Upgrade':
                if (
                    !isset($data['modules'])
                    || !isset($data['dependencies'])
                    || !isset($data['package'])
                    || !isset($data['ticket'])
                ) {
                    throw new InvalidArgumentException('Missing required fields in Upgrade');
                }
                return new Upgrade(
                    $data['modules'],
                    $data['dependencies'],
                    $data['package'],
                    self::normalizeArgument($data['ticket'])
                );
            default:
                throw new InvalidArgumentException('Unknown Command type');
        }
    }

    /**
     * Validates and normalizes ObjectArg structure
     *
     * @param array<mixed> $data The data to validate
     * @return ObjectArg The normalized ObjectArg
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeObjectArg(array $data): ObjectArg
    {
        if (!isset($data['$kind'])) {
            throw new InvalidArgumentException('Missing $kind in ObjectArg');
        }

        switch ($data['$kind']) {
            case 'ImmOrOwnedObject':
            case 'Receiving':
                $objectRef = self::normalizeObjectRef($data);
                return new ObjectArg($objectRef, new SharedObject('', '', false), $objectRef);
            case 'SharedObject':
                if (!isset($data['objectId']) || !isset($data['initialSharedVersion']) || !isset($data['mutable'])) {
                    throw new InvalidArgumentException('Missing required fields in SharedObject');
                }
                $sharedObject = self::normalizeSharedObject($data);
                return new ObjectArg(new ObjectRef('', '', ''), $sharedObject, new ObjectRef('', '', ''));
            default:
                throw new InvalidArgumentException('Unknown ObjectArg type');
        }
    }

    /**
     * Validates and normalizes SharedObject structure
     *
     * @param array<mixed> $data The data to validate
     * @return SharedObject The normalized SharedObject
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeSharedObject(array $data): SharedObject
    {
        return new SharedObject(
            Utils::normalizeSuiAddress($data['objectId']),
            self::normalizeU64($data['initialSharedVersion']),
            $data['mutable']
        );
    }

    /**
     * Validates and normalizes CallArg structure
     *
     * @param array<mixed> $data The data to validate
     * @return CallArg The normalized CallArg
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeCallArg(array $data): CallArg
    {
        if (!isset($data['$kind'])) {
            throw new InvalidArgumentException('Missing $kind in CallArg');
        }

        switch ($data['$kind']) {
            case 'Object':
                $objectArg = self::normalizeObjectArg($data);
                return new CallArg($objectArg, '', null, new UnresolvedObject(''));
            case 'Pure':
                if (!isset($data['bytes']) || !is_string($data['bytes'])) {
                    throw new InvalidArgumentException('Invalid Pure argument');
                }
                return new CallArg(
                    new ObjectArg(
                        new ObjectRef('', '', ''),
                        new SharedObject('', '', false),
                        new ObjectRef('', '', '')
                    ),
                    $data['bytes'],
                    null,
                    new UnresolvedObject('')
                );
            case 'UnresolvedPure':
                if (!isset($data['value'])) {
                    throw new InvalidArgumentException('Missing value in UnresolvedPure');
                }
                return new CallArg(
                    new ObjectArg(
                        new ObjectRef('', '', ''),
                        new SharedObject('', '', false),
                        new ObjectRef('', '', '')
                    ),
                    '',
                    $data['value'],
                    new UnresolvedObject('')
                );
            case 'UnresolvedObject':
                $unresolvedObject = self::normalizeUnresolvedObject($data);
                return new CallArg(
                    new ObjectArg(
                        new ObjectRef('', '', ''),
                        new SharedObject('', '', false),
                        new ObjectRef('', '', '')
                    ),
                    '',
                    null,
                    $unresolvedObject
                );
            default:
                throw new InvalidArgumentException('Unknown CallArg type');
        }
    }

    /**
     * Validates and normalizes UnresolvedObject structure
     *
     * @param array<mixed> $data The data to validate
     * @return UnresolvedObject The normalized UnresolvedObject
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeUnresolvedObject(array $data): UnresolvedObject
    {
        if (!isset($data['objectId'])) {
            throw new InvalidArgumentException('Missing objectId in UnresolvedObject');
        }
        return new UnresolvedObject(
            Utils::normalizeSuiAddress($data['objectId']),
            isset($data['version']) ? self::normalizeU64($data['version']) : null,
            $data['digest'] ?? null,
            isset($data['initialSharedVersion']) ? self::normalizeU64($data['initialSharedVersion']) : null
        );
    }

    /**
     * Validates and normalizes TransactionExpiration structure
     *
     * @param array<mixed> $data The data to validate
     * @return Expiration The normalized TransactionExpiration
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeTransactionExpiration(array $data): Expiration
    {
        if (!isset($data['$kind'])) {
            throw new InvalidArgumentException('Missing $kind in TransactionExpiration');
        }

        switch ($data['$kind']) {
            case 'None':
                if (!isset($data['None']) || true !== $data['None']) {
                    throw new InvalidArgumentException('Invalid None expiration');
                }
                return new Expiration('', true);
            case 'Epoch':
                if (!isset($data['Epoch'])) {
                    throw new InvalidArgumentException('Missing Epoch value');
                }
                return new Expiration(self::normalizeU64($data['Epoch']), false);
            default:
                throw new InvalidArgumentException('Unknown TransactionExpiration type');
        }
    }

    /**
     * Validates and normalizes TransactionData structure
     *
     * @param array<mixed> $data The data to validate
     * @return TransactionData The normalized TransactionData
     * @throws InvalidArgumentException If the data is invalid
     */
    public static function normalizeTransactionData(array $data): TransactionData
    {
        if (!isset($data['version']) || 2 !== $data['version']) {
            throw new InvalidArgumentException('Invalid version in TransactionData');
        }

        $normalized = ['version' => 2];

        $normalized['sender'] = isset($data['sender']) ? Utils::normalizeSuiAddress($data['sender']) : null;

        $normalized['expiration'] = isset($data['expiration'])
        ? self::normalizeTransactionExpiration($data['expiration'])
        : null;

        $normalized['gasData'] = isset($data['gasData']) ? self::normalizeGasData($data['gasData']) : null;

        $normalized['inputs'] = isset($data['inputs']) ? array_map(
            fn($item) => self::normalizeCallArg($item),
            $data['inputs']
        ) : null;

        $normalized['commands'] = isset($data['commands']) ? array_map(
            fn($item) => self::normalizeCommand($item),
            $data['commands']
        ) : null;

        if (
            !isset($normalized['sender'])
            || !isset($normalized['gasData'])
            || !isset($normalized['expiration'])
            || !isset($normalized['inputs'])
            || !isset($normalized['commands'])
        ) {
            throw new InvalidArgumentException('Missing required fields in TransactionData');
        }

        return new TransactionData(
            $normalized['sender'],
            $normalized['gasData'],
            $normalized['expiration'],
            $normalized['inputs'],
            $normalized['commands']
        );
    }
}
