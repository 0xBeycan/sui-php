<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Utils;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\ObjectRef;

class Inputs
{
    /**
     * Creates a Pure CallArg from data
     *
     * @param array<int>|string $data The data to create a Pure CallArg from
     * @return CallArg The created Pure CallArg
     */
    public static function pure(array|string $data): CallArg
    {
        $bytes = is_string($data) ? Utils::toBase64($data) : Utils::toBase64(implode(array_map('chr', $data)));
        return new CallArg(
            new Type\ObjectArg(
                new ObjectRef('', '0', ''),
                new Type\SharedObject('', '0', false),
                new ObjectRef('', '0', '')
            ),
            $bytes,
            null,
            new Type\UnresolvedObject('')
        );
    }

    /**
     * Creates an Object CallArg
     *
     * @param mixed $value The value to create an Object CallArg from
     * @return CallArg The created Object CallArg
     */
    public static function object(mixed $value): CallArg
    {
        return new CallArg(
            new Type\ObjectArg(
                new ObjectRef('', '0', ''),
                new Type\SharedObject('', '0', false),
                new ObjectRef('', '0', '')
            ),
            '',
            null,
            new Type\UnresolvedObject($value)
        );
    }

    /**
     * Creates an ObjectRef CallArg
     *
     * @param ObjectRef $objectRef The object reference
     * @return CallArg The created ObjectRef CallArg
     */
    public static function objectRef(ObjectRef $objectRef): CallArg
    {
        return new CallArg(
            new Type\ObjectArg(
                new ObjectRef(
                    Utils::normalizeSuiAddress($objectRef->getObjectId()),
                    $objectRef->getVersion(),
                    $objectRef->getDigest()
                ),
                new Type\SharedObject('', '0', false),
                new ObjectRef('', '0', '')
            ),
            '',
            null,
            new Type\UnresolvedObject('')
        );
    }

    /**
     * Creates a SharedObjectRef CallArg
     *
     * @param array{objectId: string, mutable: bool, initialSharedVersion: int|string} $params The parameters
     * @return CallArg The created SharedObjectRef CallArg
     */
    public static function sharedObjectRef(array $params): CallArg
    {
        return new CallArg(
            new Type\ObjectArg(
                new ObjectRef('', '0', ''),
                new Type\SharedObject(
                    Utils::normalizeSuiAddress($params['objectId']),
                    (string) $params['initialSharedVersion'],
                    $params['mutable']
                ),
                new ObjectRef('', '0', '')
            ),
            '',
            null,
            new Type\UnresolvedObject('')
        );
    }

    /**
     * Creates a ReceivingRef CallArg
     *
     * @param ObjectRef $objectRef The object reference
     * @return CallArg The created ReceivingRef CallArg
     */
    public static function receivingRef(ObjectRef $objectRef): CallArg
    {
        return new CallArg(
            new Type\ObjectArg(
                new ObjectRef('', '0', ''),
                new Type\SharedObject('', '0', false),
                new ObjectRef(
                    Utils::normalizeSuiAddress($objectRef->getObjectId()),
                    $objectRef->getVersion(),
                    $objectRef->getDigest()
                )
            ),
            '',
            null,
            new Type\UnresolvedObject('')
        );
    }
}
