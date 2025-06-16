<?php

declare(strict_types=1);

namespace Sui\Transactions;

use Sui\Utils as SuiUtils;
use Sui\Type\Move\StructTag;
use Sui\Type\Move\NormalizedType;
use Sui\Transactions\Type\CallArg;
use Sui\Transactions\Type\Argument;

class Utils extends SuiUtils
{
    /**
     * @param NormalizedType $normalizedType
     * @return NormalizedType|null
     */
    public static function extractMutableReference(
        NormalizedType $normalizedType,
    ): NormalizedType | null {
        return 'MutableReference' === $normalizedType->key && $normalizedType->value instanceof NormalizedType
            ? $normalizedType->value
            : null;
    }

    /**
     * @param NormalizedType $normalizedType
     * @return NormalizedType|null
     */
    public static function extractReference(
        NormalizedType $normalizedType,
    ): NormalizedType | null {
        return 'Reference' === $normalizedType->key && $normalizedType->value instanceof NormalizedType
            ? $normalizedType->value
            : null;
    }

    /**
     * @param NormalizedType $normalizedType
     * @return StructTag|null
     */
    public static function extractStructTag(
        NormalizedType $normalizedType,
    ): StructTag | null {
        if ('Struct' === $normalizedType->key && $normalizedType->value instanceof StructTag) {
            return $normalizedType->value;
        }

        $ref = self::extractReference($normalizedType);
        $mutRef = self::extractMutableReference($normalizedType);

        if ($ref && 'Struct' === $ref->key && $ref->value instanceof StructTag) {
            return $ref->value;
        }

        if ($mutRef && 'Struct' === $mutRef->key && $mutRef->value instanceof StructTag) {
            return $mutRef->value;
        }

        return null;
    }

    /**
     * @param string|CallArg $arg
     * @return string|null
     */
    public static function getIdFromCallArg(string|CallArg $arg): string | null
    {
        if (is_string($arg)) {
            return SuiUtils::normalizeSuiAddress($arg);
        }

        if ('Object' === $arg->kind) {
            if (isset($arg->value->ImmOrOwnedObject)) {
                return SuiUtils::normalizeSuiAddress($arg->value->ImmOrOwnedObject->objectId);
            }

            if (isset($arg->value->Receiving)) {
                return SuiUtils::normalizeSuiAddress($arg->value->Receiving->objectId);
            }

            if (isset($arg->value->SharedObject)) {
                return SuiUtils::normalizeSuiAddress($arg->value->SharedObject->objectId);
            }
        }

        if ('UnresolvedObject' === $arg->kind && isset($arg->value->objectId)) {
            return SuiUtils::normalizeSuiAddress($arg->value->objectId);
        }

        return null;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function isArgument(mixed $value): bool
    {
        return $value instanceof Argument;
    }

    /**
     * @param array<mixed> $array
     * @return array<mixed>
     */
    public static function flattenArray(array $array): array
    {
        $result = [];
        array_walk_recursive($array, function ($item) use (&$result) { // @phpcs:ignore
            $result[] = $item;
        });
        return $result;
    }

    /**
     * @param array<mixed> $input
     * @return array<mixed>
     */
    public static function transformKind(array $input): array
    {
        $output = [];
        $kindValue = null;

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $value = self::transformKind($value);
            }

            if ('kind' === $key) {
                $kindValue = $value;
                $output[$kindValue] = $input[$kindValue];
            } elseif ('kind' === $key || 'value' === $key) {
                continue;
            } elseif (!is_null($value)) {
                $output[$key] = $value;
            }
        }

        if (!is_null($kindValue)) {
            $output['$kind'] = $kindValue;
        }

        return $output;
    }
}
