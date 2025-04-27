<?php

declare(strict_types=1);

namespace Sui\Type;

class SuiValidatorSummary
{
    public string $commissionRate;
    public string $description;
    public string $exchangeRatesId;
    public string $exchangeRatesSize;
    public string $gasPrice;
    public string $imageUrl;
    public string $name;
    public string $netAddress;
    public string $networkPubkeyBytes;
    public string $nextEpochCommissionRate;
    public string $nextEpochGasPrice;
    public ?string $nextEpochNetAddress;
    public ?string $nextEpochNetworkPubkeyBytes;
    public ?string $nextEpochP2pAddress;
    public ?string $nextEpochPrimaryAddress;
    public ?string $nextEpochProofOfPossession;
    public ?string $nextEpochProtocolPubkeyBytes;
    public string $nextEpochStake;
    public ?string $nextEpochWorkerAddress;
    public ?string $nextEpochWorkerPubkeyBytes;
    public string $operationCapId;
    public string $p2pAddress;
    public string $pendingPoolTokenWithdraw;
    public string $pendingStake;
    public string $pendingTotalSuiWithdraw;
    public string $poolTokenBalance;
    public string $primaryAddress;
    public string $projectUrl;
    public string $proofOfPossessionBytes;
    public string $protocolPubkeyBytes;
    public string $rewardsPool;
    public ?string $stakingPoolActivationEpoch;
    public ?string $stakingPoolDeactivationEpoch;
    public string $stakingPoolId;
    public string $stakingPoolSuiBalance;
    public string $suiAddress;
    public string $votingPower;
    public string $workerAddress;
    public string $workerPubkeyBytes;

    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data)
    {
        $this->commissionRate = $data['commissionRate'];
        $this->description = $data['description'];
        $this->exchangeRatesId = $data['exchangeRatesId'];
        $this->exchangeRatesSize = $data['exchangeRatesSize'];
        $this->gasPrice = $data['gasPrice'];
        $this->imageUrl = $data['imageUrl'];
        $this->name = $data['name'];
        $this->netAddress = $data['netAddress'];
        $this->networkPubkeyBytes = $data['networkPubkeyBytes'];
        $this->nextEpochCommissionRate = $data['nextEpochCommissionRate'];
        $this->nextEpochGasPrice = $data['nextEpochGasPrice'];
        $this->nextEpochNetAddress = $data['nextEpochNetAddress'] ?? null;
        $this->nextEpochNetworkPubkeyBytes = $data['nextEpochNetworkPubkeyBytes'] ?? null;
        $this->nextEpochP2pAddress = $data['nextEpochP2pAddress'] ?? null;
        $this->nextEpochPrimaryAddress = $data['nextEpochPrimaryAddress'] ?? null;
        $this->nextEpochProofOfPossession = $data['nextEpochProofOfPossession'] ?? null;
        $this->nextEpochProtocolPubkeyBytes = $data['nextEpochProtocolPubkeyBytes'] ?? null;
        $this->nextEpochStake = $data['nextEpochStake'];
        $this->nextEpochWorkerAddress = $data['nextEpochWorkerAddress'] ?? null;
        $this->nextEpochWorkerPubkeyBytes = $data['nextEpochWorkerPubkeyBytes'] ?? null;
        $this->operationCapId = $data['operationCapId'];
        $this->p2pAddress = $data['p2pAddress'];
        $this->pendingPoolTokenWithdraw = $data['pendingPoolTokenWithdraw'];
        $this->pendingStake = $data['pendingStake'];
        $this->pendingTotalSuiWithdraw = $data['pendingTotalSuiWithdraw'];
        $this->poolTokenBalance = $data['poolTokenBalance'];
        $this->primaryAddress = $data['primaryAddress'];
        $this->projectUrl = $data['projectUrl'];
        $this->proofOfPossessionBytes = $data['proofOfPossessionBytes'];
        $this->protocolPubkeyBytes = $data['protocolPubkeyBytes'];
        $this->rewardsPool = $data['rewardsPool'];
        $this->stakingPoolActivationEpoch = $data['stakingPoolActivationEpoch'] ?? null;
        $this->stakingPoolDeactivationEpoch = $data['stakingPoolDeactivationEpoch'] ?? null;
        $this->stakingPoolId = $data['stakingPoolId'];
        $this->stakingPoolSuiBalance = $data['stakingPoolSuiBalance'];
        $this->suiAddress = $data['suiAddress'];
        $this->votingPower = $data['votingPower'];
        $this->workerAddress = $data['workerAddress'];
        $this->workerPubkeyBytes = $data['workerPubkeyBytes'];
    }
}
