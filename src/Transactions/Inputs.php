<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Transactions\Type\CallArg;

class Inputs
{
    /**
     * @param string|array<int> $data
     * @return CallArg
     */
    public static function pure(string|array $data): CallArg
    {
        return Normalizer::callArg([
            'Pure' => [
                'bytes' => is_string($data) ? $data : Utils::toBase64($data),
            ],
        ]);
    }

    /**
     * @param string $objectId
     * @param string $digest
     * @param int|string $version
     * @return CallArg
     */
    public static function objectRef(string $objectId, string $digest, int|string $version): CallArg
    {
        return Normalizer::callArg([
            'Object' => [
                'ImmOrOwnedObject' => [
                    'digest' => $digest,
                    'version' => $version,
                    'objectId' => $objectId,
                ],
            ],
        ]);
    }

    /**
     * @param string $objectId
     * @param bool $mutable
     * @param int|string $initialSharedVersion
     * @return CallArg
     */
    public static function sharedObjectRef(string $objectId, bool $mutable, int|string $initialSharedVersion): CallArg
    {
        return Normalizer::callArg([
            'Object' => [
                'SharedObject' => [
                    'mutable' => $mutable,
                    'initialSharedVersion' => $initialSharedVersion,
                    'objectId' => $objectId,
                ],
            ],
        ]);
    }

    /**
     * @param string $objectId
     * @param string $digest
     * @param int|string $version
     * @return CallArg
     */
    public static function receivingRef(string $objectId, string $digest, int|string $version): CallArg
    {
        return Normalizer::callArg([
            'Object' => [
                'Receiving' => [
                    'digest' => $digest,
                    'version' => $version,
                    'objectId' => $objectId,
                ],
            ],
        ]);
    }
}
