<?php

declare(strict_types=1);

namespace Sui\Bcs;

class Effect
{
    /**
     * @return Type
     */
    public static function packageUpgradeError(): Type
    {
        return Bcs::enum('PackageUpgradeError', [
            'UnableToFetchPackage' => Bcs::struct('UnableToFetchPackage', [
                'packageId' => self::address(),
            ]),
            'NotAPackage' => Bcs::struct('NotAPackage', [
                'objectId' => self::address(),
            ]),
            'IncompatibleUpgrade' => null,
            'DigestDoesNotMatch' => Bcs::struct('DigestDoesNotMatch', [
                'digest' => Bcs::vector(Bcs::u8()),
            ]),
            'UnknownUpgradePolicy' => Bcs::struct('UnknownUpgradePolicy', [
                'policy' => Bcs::u8(),
            ]),
            'PackageIDDoesNotMatch' => Bcs::struct('PackageIDDoesNotMatch', [
                'packageId' => self::address(),
                'ticketId' => self::address(),
            ]),
        ]);
    }

    /**
     * @return Type
     */
    public static function moduleId(): Type
    {
        return Bcs::struct('ModuleId', [
            'address' => self::address(),
            'name' => Bcs::string(),
        ]);
    }

    /**
     * @return Type
     */
    public static function moveLocation(): Type
    {
        return Bcs::struct('MoveLocation', [
            'module' => self::moduleId(),
            'function' => Bcs::u16(),
            'instruction' => Bcs::u16(),
            'functionName' => self::optionEnum(Bcs::string()),
        ]);
    }

    /**
     * @return Type
     */
    public static function commandArgumentError(): Type
    {
        return Bcs::enum('CommandArgumentError', [
            'TypeMismatch' => null,
            'InvalidBCSBytes' => null,
            'InvalidUsageOfPureArg' => null,
            'InvalidArgumentToPrivateEntryFunction' => null,
            'IndexOutOfBounds' => Bcs::struct('IndexOutOfBounds', [
                'idx' => Bcs::u16(),
            ]),
            'SecondaryIndexOutOfBounds' => Bcs::struct('SecondaryIndexOutOfBounds', [
                'resultIdx' => Bcs::u16(),
                'secondaryIdx' => Bcs::u16(),
            ]),
            'InvalidResultArity' => Bcs::struct('InvalidResultArity', [
                'resultIdx' => Bcs::u16(),
            ]),
            'InvalidGasCoinUsage' => null,
            'InvalidValueUsage' => null,
            'InvalidObjectByValue' => null,
            'InvalidObjectByMutRef' => null,
            'SharedObjectOperationNotAllowed' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function typeArgumentError(): Type
    {
        return Bcs::enum('TypeArgumentError', [
            'TypeNotFound' => null,
            'ConstraintNotSatisfied' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function executionFailureStatus(): Type
    {
        return Bcs::enum('ExecutionFailureStatus', [
            'InsufficientGas' => null,
            'InvalidGasObject' => null,
            'InvariantViolation' => null,
            'FeatureNotYetSupported' => null,
            'MoveObjectTooBig' => Bcs::struct('MoveObjectTooBig', [
                'objectSize' => Bcs::u64(),
                'maxObjectSize' => Bcs::u64(),
            ]),
            'MovePackageTooBig' => Bcs::struct('MovePackageTooBig', [
                'objectSize' => Bcs::u64(),
                'maxObjectSize' => Bcs::u64(),
            ]),
            'CircularObjectOwnership' => Bcs::struct('CircularObjectOwnership', [
                'object' => self::address(),
            ]),
            'InsufficientCoinBalance' => null,
            'CoinBalanceOverflow' => null,
            'PublishErrorNonZeroAddress' => null,
            'SuiMoveVerificationError' => null,
            'MovePrimitiveRuntimeError' => self::optionEnum(self::moveLocation()),
            'MoveAbort' => Bcs::tuple([self::moveLocation(), Bcs::u64()]),
            'VMVerificationOrDeserializationError' => null,
            'VMInvariantViolation' => null,
            'FunctionNotFound' => null,
            'ArityMismatch' => null,
            'TypeArityMismatch' => null,
            'NonEntryFunctionInvoked' => null,
            'CommandArgumentError' => Bcs::struct('CommandArgumentError', [
                'argIdx' => Bcs::u16(),
                'kind' => self::commandArgumentError(),
            ]),
            'TypeArgumentError' => Bcs::struct('TypeArgumentError', [
                'argumentIdx' => Bcs::u16(),
                'kind' => self::typeArgumentError(),
            ]),
            'UnusedValueWithoutDrop' => Bcs::struct('UnusedValueWithoutDrop', [
                'resultIdx' => Bcs::u16(),
                'secondaryIdx' => Bcs::u16(),
            ]),
            'InvalidPublicFunctionReturnType' => Bcs::struct('InvalidPublicFunctionReturnType', [
                'idx' => Bcs::u16(),
            ]),
            'InvalidTransferObject' => null,
            'EffectsTooLarge' => Bcs::struct('EffectsTooLarge', [
                'currentSize' => Bcs::u64(),
                'maxSize' => Bcs::u64(),
            ]),
            'PublishUpgradeMissingDependency' => null,
            'PublishUpgradeDependencyDowngrade' => null,
            'PackageUpgradeError' => Bcs::struct('PackageUpgradeError', [
                'upgradeError' => self::packageUpgradeError(),
            ]),
            'WrittenObjectsTooLarge' => Bcs::struct('WrittenObjectsTooLarge', [
                'currentSize' => Bcs::u64(),
                'maxSize' => Bcs::u64(),
            ]),
            'CertificateDenied' => null,
            'SuiMoveVerificationTimedout' => null,
            'SharedObjectOperationNotAllowed' => null,
            'InputObjectDeleted' => null,
            'ExecutionCancelledDueToSharedObjectCongestion' => Bcs::struct('ExecutionCancelledDueToSharedObjectCongestion', [
                'congestedObjects' => Bcs::vector(self::address()),
            ]),
            'AddressDeniedForCoin' => Bcs::struct('AddressDeniedForCoin', [
                'address' => self::address(),
                'coinType' => Bcs::string(),
            ]),
            'CoinTypeGlobalPause' => Bcs::struct('CoinTypeGlobalPause', [
                'coinType' => Bcs::string(),
            ]),
            'ExecutionCancelledDueToRandomnessUnavailable' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function executionStatus(): Type
    {
        return Bcs::enum('ExecutionStatus', [
            'Success' => null,
            'Failed' => Bcs::struct('ExecutionFailed', [
                'error' => self::executionFailureStatus(),
                'command' => self::optionEnum(Bcs::u64()),
            ]),
        ]);
    }

    /**
     * @return Type
     */
    public static function gasCostSummary(): Type
    {
        return Bcs::struct('GasCostSummary', [
            'computationCost' => Bcs::u64(),
            'storageCost' => Bcs::u64(),
            'storageRebate' => Bcs::u64(),
            'nonRefundableStorageFee' => Bcs::u64(),
        ]);
    }

    /**
     * @return Type
     */
    public static function transactionEffectsV1(): Type
    {
        return Bcs::struct('TransactionEffectsV1', [
            'status' => self::executionStatus(),
            'executedEpoch' => Bcs::u64(),
            'gasUsed' => self::gasCostSummary(),
            'modifiedAtVersions' => Bcs::vector(Bcs::tuple([self::address(), Bcs::u64()])),
            'sharedObjects' => Bcs::vector(Map::suiObjectRef()),
            'transactionDigest' => Map::objectDigest(),
            'created' => Bcs::vector(Bcs::tuple([Map::suiObjectRef(), Map::owner()])),
            'mutated' => Bcs::vector(Bcs::tuple([Map::suiObjectRef(), Map::owner()])),
            'unwrapped' => Bcs::vector(Bcs::tuple([Map::suiObjectRef(), Map::owner()])),
            'deleted' => Bcs::vector(Map::suiObjectRef()),
            'unwrappedThenDeleted' => Bcs::vector(Map::suiObjectRef()),
            'wrapped' => Bcs::vector(Map::suiObjectRef()),
            'gasObject' => Bcs::tuple([Map::suiObjectRef(), Map::owner()]),
            'eventsDigest' => self::optionEnum(Map::objectDigest()),
            'dependencies' => Bcs::vector(Map::objectDigest()),
        ]);
    }

    /**
     * @return Type
     */
    public static function versionDigest(): Type
    {
        return Bcs::tuple([Bcs::u64(), Map::objectDigest()]);
    }

    /**
     * @return Type
     */
    public static function objectIn(): Type
    {
        return Bcs::enum('ObjectIn', [
            'NotExist' => null,
            'Exist' => Bcs::tuple([self::versionDigest(), Map::owner()]),
        ]);
    }

    /**
     * @return Type
     */
    public static function objectOut(): Type
    {
        return Bcs::enum('ObjectOut', [
            'NotExist' => null,
            'ObjectWrite' => Bcs::tuple([Map::objectDigest(), Map::owner()]),
            'PackageWrite' => self::versionDigest(),
        ]);
    }

    /**
     * @return Type
     */
    public static function idOperation(): Type
    {
        return Bcs::enum('IDOperation', [
            'None' => null,
            'Created' => null,
            'Deleted' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function effectsObjectChange(): Type
    {
        return Bcs::struct('EffectsObjectChange', [
            'inputState' => self::objectIn(),
            'outputState' => self::objectOut(),
            'idOperation' => self::idOperation(),
        ]);
    }

    /**
     * @return Type
     */
    public static function unchangedSharedKind(): Type
    {
        return Bcs::enum('UnchangedSharedKind', [
            'ReadOnlyRoot' => self::versionDigest(),
            'MutateDeleted' => Bcs::u64(),
            'ReadDeleted' => Bcs::u64(),
            'Cancelled' => Bcs::u64(),
            'PerEpochConfig' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function transactionEffectsV2(): Type
    {
        return Bcs::struct('TransactionEffectsV2', [
            'status' => self::executionStatus(),
            'executedEpoch' => Bcs::u64(),
            'gasUsed' => self::gasCostSummary(),
            'transactionDigest' => Map::objectDigest(),
            'gasObjectIndex' => self::optionEnum(Bcs::u32()),
            'eventsDigest' => self::optionEnum(Map::objectDigest()),
            'dependencies' => Bcs::vector(Map::objectDigest()),
            'lamportVersion' => Bcs::u64(),
            'changedObjects' => Bcs::vector(Bcs::tuple([self::address(), self::effectsObjectChange()])),
            'unchangedSharedObjects' => Bcs::vector(Bcs::tuple([self::address(), self::unchangedSharedKind()])),
            'auxDataDigest' => self::optionEnum(Map::objectDigest()),
        ]);
    }

    /**
     * @return Type
     */
    public static function transactionEffects(): Type
    {
        return Bcs::enum('TransactionEffects', [
            'V1' => self::transactionEffectsV1(),
            'V2' => self::transactionEffectsV2(),
        ]);
    }

    /**
     * @param Type $type
     * @return Type
     */
    private static function optionEnum(Type $type): Type
    {
        return Map::optionEnum($type);
    }

    /**
     * @return Type
     */
    private static function address(): Type
    {
        return Map::address();
    }
}
