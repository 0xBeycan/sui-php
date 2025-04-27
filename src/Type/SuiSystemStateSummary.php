<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiSystemStateSummary
{
    /** @var SuiValidatorSummary[] */
    public array $activeValidators;
    /** @var array<int, array{0: string, 1: string}> */
    public array $atRiskValidators;
    public string $epoch;
    public string $epochDurationMs;
    public string $epochStartTimestampMs;
    public string $inactivePoolsId;
    public string $inactivePoolsSize;
    public string $maxValidatorCount;
    public string $minValidatorJoiningStake;
    public string $pendingActiveValidatorsId;
    public string $pendingActiveValidatorsSize;
    /** @var string[] */
    public array $pendingRemovals;
    public string $protocolVersion;
    public string $referenceGasPrice;
    public bool $safeMode;
    public string $safeModeComputationRewards;
    public string $safeModeNonRefundableStorageFee;
    public string $safeModeStorageRebates;
    public string $safeModeStorageRewards;
    public string $stakeSubsidyBalance;
    public string $stakeSubsidyCurrentDistributionAmount;
    public int $stakeSubsidyDecreaseRate;
    public string $stakeSubsidyDistributionCounter;
    public string $stakeSubsidyPeriodLength;
    public string $stakeSubsidyStartEpoch;
    public string $stakingPoolMappingsId;
    public string $stakingPoolMappingsSize;
    public string $storageFundNonRefundableBalance;
    public string $storageFundTotalObjectStorageRebates;
    public string $systemStateVersion;
    public string $totalStake;
    public string $validatorCandidatesId;
    public string $validatorCandidatesSize;
    public string $validatorLowStakeGracePeriod;
    public string $validatorLowStakeThreshold;
    /** @var array<int, array{0: string, 1: string[]}> */
    public array $validatorReportRecords;
    public string $validatorVeryLowStakeThreshold;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->activeValidators = array_map(
            fn(array $item) => new SuiValidatorSummary($item),
            $data['activeValidators']
        );
        $this->atRiskValidators = $data['atRiskValidators'];
        $this->epoch = $data['epoch'];
        $this->epochDurationMs = $data['epochDurationMs'];
        $this->epochStartTimestampMs = $data['epochStartTimestampMs'];
        $this->inactivePoolsId = $data['inactivePoolsId'];
        $this->inactivePoolsSize = $data['inactivePoolsSize'];
        $this->maxValidatorCount = $data['maxValidatorCount'];
        $this->minValidatorJoiningStake = $data['minValidatorJoiningStake'];
        $this->pendingActiveValidatorsId = $data['pendingActiveValidatorsId'];
        $this->pendingActiveValidatorsSize = $data['pendingActiveValidatorsSize'];
        $this->pendingRemovals = $data['pendingRemovals'];
        $this->protocolVersion = $data['protocolVersion'];
        $this->referenceGasPrice = $data['referenceGasPrice'];
        $this->safeMode = $data['safeMode'];
        $this->safeModeComputationRewards = $data['safeModeComputationRewards'];
        $this->safeModeNonRefundableStorageFee = $data['safeModeNonRefundableStorageFee'];
        $this->safeModeStorageRebates = $data['safeModeStorageRebates'];
        $this->safeModeStorageRewards = $data['safeModeStorageRewards'];
        $this->stakeSubsidyBalance = $data['stakeSubsidyBalance'];
        $this->stakeSubsidyCurrentDistributionAmount = $data['stakeSubsidyCurrentDistributionAmount'];
        $this->stakeSubsidyDecreaseRate = $data['stakeSubsidyDecreaseRate'];
        $this->stakeSubsidyDistributionCounter = $data['stakeSubsidyDistributionCounter'];
        $this->stakeSubsidyPeriodLength = $data['stakeSubsidyPeriodLength'];
        $this->stakeSubsidyStartEpoch = $data['stakeSubsidyStartEpoch'];
        $this->stakingPoolMappingsId = $data['stakingPoolMappingsId'];
        $this->stakingPoolMappingsSize = $data['stakingPoolMappingsSize'];
        $this->storageFundNonRefundableBalance = $data['storageFundNonRefundableBalance'];
        $this->storageFundTotalObjectStorageRebates = $data['storageFundTotalObjectStorageRebates'];
        $this->systemStateVersion = $data['systemStateVersion'];
        $this->totalStake = $data['totalStake'];
        $this->validatorCandidatesId = $data['validatorCandidatesId'];
        $this->validatorCandidatesSize = $data['validatorCandidatesSize'];
        $this->validatorLowStakeGracePeriod = $data['validatorLowStakeGracePeriod'];
        $this->validatorLowStakeThreshold = $data['validatorLowStakeThreshold'];
        $this->validatorReportRecords = $data['validatorReportRecords'];
        $this->validatorVeryLowStakeThreshold = $data['validatorVeryLowStakeThreshold'];
    }
}
