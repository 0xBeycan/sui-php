<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Utils as BaseUtils;
use Sui\Transactions\Type\Argument;
use Sui\Transactions\Type\CallArg;

class TransactionUtils
{
    /**
     * Extracts MutableReference from a normalized type
     *
     * @param mixed $normalizedType The normalized type to extract from
     * @return mixed|null The extracted MutableReference or null if not found
     */
    public static function extractMutableReference(mixed $normalizedType): mixed
    {
        return is_array($normalizedType) && isset($normalizedType['MutableReference'])
            ? $normalizedType['MutableReference']
            : null;
    }

    /**
     * Extracts Reference from a normalized type
     *
     * @param mixed $normalizedType The normalized type to extract from
     * @return mixed|null The extracted Reference or null if not found
     */
    public static function extractReference(mixed $normalizedType): mixed
    {
        return is_array($normalizedType) && isset($normalizedType['Reference'])
            ? $normalizedType['Reference']
            : null;
    }

    /**
     * Extracts StructTag from a normalized type
     *
     * @param mixed $normalizedType The normalized type to extract from
     * @return array{Struct: mixed}|null The extracted StructTag or null if not found
     */
    public static function extractStructTag(mixed $normalizedType): ?array
    {
        if (is_array($normalizedType) && isset($normalizedType['Struct'])) {
            return $normalizedType;
        }

        $ref = self::extractReference($normalizedType);
        $mutRef = self::extractMutableReference($normalizedType);

        if (is_array($ref) && isset($ref['Struct'])) {
            return $ref;
        }

        if (is_array($mutRef) && isset($mutRef['Struct'])) {
            return $mutRef;
        }

        return null;
    }

    /**
     * Gets ID from a CallArg
     *
     * @param string|CallArg $arg The argument to get ID from
     * @return string|null The normalized address or null if not found
     * @disregard
     */
    public static function getIdFromCallArg(string|CallArg $arg): ?string
    {
        if (is_string($arg)) {
            return BaseUtils::normalizeSuiAddress($arg);
        }

        if ($arg instanceof CallArg) {
            return BaseUtils::normalizeSuiAddress($arg->getObject()->getImmOrOwnedObject()->getObjectId());
        }
    }

    /**
     * Checks if a value is an Argument
     *
     * @param mixed $value The value to check
     * @return bool True if the value is an Argument, false otherwise
     */
    public static function isArgument(mixed $value): bool
    {
        return $value instanceof Argument;
    }
}
