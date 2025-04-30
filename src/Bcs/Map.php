<?php

declare(strict_types=1);

namespace Sui\Bcs;

use Sui\Utils;
use Sui\Constants;

class Map
{
    /**
     * @param array<mixed> $options
     * @return Type
     */
    public static function unsafeU64(array $options = []): Type
    {
        return Bcs::u64(array_merge(['name' => 'unsafe_u64'], $options))
            ->transform('unsafe_u64', function (int $value): int {
                return $value;
            }, function (int $value): int {
                return $value;
            });
    }

    /**
     * @param Type $type
     * @return Type The created Type instance
     */
    public static function optionEnum(Type $type): Type
    {
        return Bcs::enum('Option', [
            'Some' => $type,
            'None' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function address(): Type
    {
        return Bcs::bytes(Constants::SUI_ADDRESS_LENGTH)
            ->transform(
                'Address',
                function (string|array $value): array {
                    return is_string($value) ? Utils::fromHex(Utils::normalizeSuiAddress($value)) : $value;
                },
                function (string $value): string {
                    return Utils::normalizeSuiAddress(Utils::toHex($value));
                },
                function (mixed $value): void {
                    $value = is_string($value) ? $value : Utils::toHex($value);
                    if (!$value || !Utils::isValidSuiAddress(Utils::normalizeSuiAddress($value))) {
                        throw new \Exception('Invalid Sui address');
                    }
                }
            );
    }

    /**
     * @return Type
     */
    public static function objectDigest(): Type
    {
        return Bcs::vector(Bcs::u8())->transform(
            'ObjectDigest',
            function (string $value): array {
                return Utils::fromBase58($value);
            },
            function (array $value): string {
                return Utils::toBase58($value);
            },
            function (string $value): void {
                if (32 !== count(Utils::fromBase58($value))) {
                    throw new \Exception('ObjectDigest must be 32 bytes');
                }
            }
        );
    }

    /**
     * @return Type
     */
    public static function suiObjectRef(): Type
    {
        return Bcs::struct('SuiObjectRef', [
            'objectId' => self::address(),
            'version' => Bcs::u64(),
            'digest' => self::objectDigest(),
        ]);
    }

    /**
     * @return Type
     */
    public static function sharedObjectRef(): Type
    {
        return Bcs::struct('SharedObjectRef', [
            'objectId' => self::address(),
            'initialSharedVersion' => Bcs::u64(),
            'mutable' => Bcs::bool(),
        ]);
    }

    /**
     * @return Type
     */
    public static function objectArg(): Type
    {
        return Bcs::enum('ObjectArg', [
            'ImmOrOwnedObject' => self::suiObjectRef(),
            'SharedObject' => self::sharedObjectRef(),
            'Receiving' => self::suiObjectRef(),
        ]);
    }

    /**
     * @return Type
     */
    public static function owner(): Type
    {
        return Bcs::enum('Owner', [
            'AddressOwner' => self::address(),
            'ObjectOwner' => self::address(),
            'Shared' => Bcs::struct('Shared', [
                'initialSharedVersion' => Bcs::u64(),
            ]),
            'Immutable' => null,
            'ConsensusV2' => Bcs::struct('ConsensusV2', [
                'authenticator' => Bcs::enum('Authenticator', [
                    'SingleOwner' => self::address(),
                ]),
                'startVersion' => Bcs::u64(),
            ]),
        ]);
    }

    /**
     * @return Type
     */
    public static function callArg(): Type
    {
        return Bcs::enum('CallArg', [
            'Pure' => Bcs::struct('Pure', [
                'bytes' => Bcs::vector(Bcs::u8())->transform(
                    'bytes',
                    function (mixed $value): array {
                        return is_string($value) ? Utils::fromBase64($value) : $value;
                    },
                    function (array $value): string {
                        return Utils::toBase64($value);
                    }
                ),
            ]),
            'Object' => self::objectArg(),
        ]);
    }

    /**
     * @return Type
     */
    public static function innerTypeTag(): Type
    {
        return Bcs::enum('InnerTypeTag', [
            'bool' => null,
            'u8' => null,
            'u64' => null,
            'u128' => null,
            'address' => null,
            'signer' => null,
            'vector' => Bcs::lazy(fn (): Type => self::innerTypeTag()),
            'struct' => Bcs::lazy(fn (): Type => self::structTag()),
            'u16' => null,
            'u32' => null,
            'u256' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function typeTag(): Type
    {
        return self::innerTypeTag()->transform(
            'InnerTypeTag',
            function (mixed $value): array {
                return is_string($value) ? Serializer::parseFromStr($value) : $value;
            },
            function (array $value): string {
                return Serializer::tagToString($value);
            }
        );
    }

    /**
     * @return Type
     */
    public static function argument(): Type
    {
        return Bcs::enum('Argument', [
            'GasCoin' => null,
            'Input' => Bcs::u16(),
            'Result' => Bcs::u16(),
            'NestedResult' => Bcs::tuple([Bcs::u16(), Bcs::u16()]),
        ]);
    }

    /**
     * @return Type
     */
    public static function programmableMoveCall(): Type
    {
        return Bcs::struct('ProgrammableMoveCall', [
            'package' => self::address(),
            'module' => Bcs::string(),
            'function' => Bcs::string(),
            'typeArguments' => Bcs::vector(self::typeTag()),
            'arguments' => Bcs::vector(self::argument()),
        ]);
    }

    /**
     * @return Type
     */
    public static function command(): Type
    {
        return Bcs::enum('Command', [
            'MoveCall' => self::programmableMoveCall(),
            'TransferObjects' => Bcs::struct('TransferObjects', [
                'objects' => Bcs::vector(self::argument()),
                'address' => self::argument(),
            ]),
            'SplitCoins' => Bcs::struct('SplitCoins', [
                'coin' => self::argument(),
                'amounts' => Bcs::vector(self::argument()),
            ]),
            'MergeCoins' => Bcs::struct('MergeCoins', [
                'destination' => self::argument(),
                'sources' => Bcs::vector(self::argument()),
            ]),
            'Publish' => Bcs::struct('Publish', [
                'modules' => Bcs::vector(
                    Bcs::vector(Bcs::u8())->transform(
                        'modules',
                        function (mixed $value): array {
                            return is_string($value) ? Utils::fromBase64($value) : $value;
                        },
                        function (array $value): string {
                            return Utils::toBase64($value);
                        }
                    )
                ),
                'dependencies' => Bcs::vector(self::address()),
            ]),
            'MakeMoveVec' => Bcs::struct('MakeMoveVec', [
                'type' => self::optionEnum(self::typeTag())->transform(
                    'type',
                    function (mixed $value): array {
                        return null === $value ? ['None' => true] : ['Some' => $value];
                    },
                    function (mixed $value): string {
                        return $value['Some'] ?: null;
                    }
                ),
                'elements' => Bcs::vector(self::argument()),
            ]),
            'Upgrade' => Bcs::struct('Upgrade', [
                'modules' => Bcs::vector(
                    Bcs::vector(Bcs::u8())->transform(
                        'modules',
                        function (mixed $value): array {
                            return is_string($value) ? Utils::fromBase64($value) : $value;
                        },
                        function (array $value): string {
                            return Utils::toBase64($value);
                        }
                    ),
                ),
                'dependencies' => Bcs::vector(self::address()),
                'package' => self::address(),
                'ticket' => self::argument(),
            ]),
        ]);
    }

    /**
     * @return Type
     */
    public static function programmableTransaction(): Type
    {
        return Bcs::struct('ProgrammableTransaction', [
            'inputs' => Bcs::vector(self::callArg()),
            'commands' => Bcs::vector(self::command()),
        ]);
    }

    /**
     * @return Type
     */
    public static function transactionKind(): Type
    {
        return Bcs::enum('TransactionKind', [
            'ProgrammableTransaction' => self::programmableTransaction(),
            'ChangeEpoch' => null,
            'Genesis' => null,
            'ConsensusCommitPrologue' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function transactionExpiration(): Type
    {
        return Bcs::enum('TransactionExpiration', [
            'None' => null,
            'Epoch' => self::unsafeU64(),
        ]);
    }

    /**
     * @return Type
     */
    public static function structTag(): Type
    {
        return Bcs::struct('StructTag', [
            'address' => self::address(),
            'module' => Bcs::string(),
            'name' => Bcs::string(),
            'typeParams' => Bcs::vector(self::innerTypeTag()),
        ]);
    }

    /**
     * @return Type
     */
    public static function gasData(): Type
    {
        return Bcs::struct('GasData', [
            'payment' => Bcs::vector(self::suiObjectRef()),
            'owner' => self::address(),
            'price' => Bcs::u64(),
            'budget' => Bcs::u64(),
        ]);
    }

    /**
     * @return Type
     */
    public static function transactionDataV1(): Type
    {
        return Bcs::struct('TransactionDataV1', [
            'kind' => self::transactionKind(),
            'sender' => self::address(),
            'gasData' => self::gasData(),
            'expiration' => self::transactionExpiration(),
        ]);
    }

    /**
     * @return Type
     */
    public static function transactionData(): Type
    {
        return Bcs::enum('TransactionData', [
            'V1' => self::transactionDataV1(),
        ]);
    }

    /**
     * @return Type
     */
    public static function intentScope(): Type
    {
        return Bcs::enum('IntentScope', [
            'TransactionData' => null,
            'TransactionEffects' => null,
            'CheckpointSummary' => null,
            'PersonalMessage' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function intentVersion(): Type
    {
        return Bcs::enum('IntentVersion', [
            'V0' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function appId(): Type
    {
        return Bcs::enum('AppId', [
            'Sui' => null,
        ]);
    }

    /**
     * @return Type
     */
    public static function intent(): Type
    {
        return Bcs::struct('Intent', [
            'scope' => self::intentScope(),
            'version' => self::intentVersion(),
            'appId' => self::appId(),
        ]);
    }

    /**
     * @param Type $type
     * @return Type
     */
    public static function intentMessage(Type $type): Type
    {
        return Bcs::struct("IntentMessage<{$type->getName()}>", [
            'intent' => self::intent(),
            'value' => $type,
        ]);
    }

    /**
     * @return Type
     */
    public static function compressedSignature(): Type
    {
        return Bcs::enum('CompressedSignature', [
            'ED25519' => Bcs::fixedArray(64, Bcs::u8()),
            'Secp256k1' => Bcs::fixedArray(64, Bcs::u8()),
            'Secp256r1' => Bcs::fixedArray(64, Bcs::u8()),
            'ZkLogin' => Bcs::vector(Bcs::u8()),
        ]);
    }

    /**
     * @return Type
     */
    public static function publicKey(): Type
    {
        return Bcs::enum('PublicKey', [
            'ED25519' => Bcs::fixedArray(32, Bcs::u8()),
            'Secp256k1' => Bcs::fixedArray(33, Bcs::u8()),
            'Secp256r1' => Bcs::fixedArray(33, Bcs::u8()),
            'ZkLogin' => Bcs::vector(Bcs::u8()),
        ]);
    }

    /**
     * @return Type
     */
    public static function multiSigPkMap(): Type
    {
        return Bcs::struct('MultiSigPkMap', [
            'pubKey' => self::publicKey(),
            'weight' => Bcs::u8(),
        ]);
    }

    /**
     * @return Type
     */
    public static function multiSigPublicKey(): Type
    {
        return Bcs::struct('MultiSigPublicKey', [
            'pk_map' => Bcs::vector(self::multiSigPkMap()),
            'threshold' => Bcs::u16(),
        ]);
    }

    /**
     * @return Type
     */
    public static function multiSig(): Type
    {
        return Bcs::struct('MultiSig', [
            'sigs' => Bcs::vector(self::compressedSignature()),
            'bitmap' => Bcs::u16(),
            'multisig_pk' => self::multiSigPublicKey(),
        ]);
    }

    /**
     * @return Type
     */
    public static function base64String(): Type
    {
        return Bcs::vector(Bcs::u8())->transform(
            'base64String',
            function (mixed $value): array {
                return is_string($value) ? Utils::fromBase64($value) : $value;
            },
            function (array $value): string {
                return Utils::toBase64($value);
            }
        );
    }

    /**
     * @return Type
     */
    public static function senderSignedTransaction(): Type
    {
        return Bcs::struct('SenderSignedTransaction', [
            'intentMessage' => self::intentMessage(self::transactionData()),
            'txSignatures' => Bcs::vector(self::base64String()),
        ]);
    }

    /**
     * @return Type
     */
    public static function senderSignedData(): Type
    {
        return Bcs::vector(self::senderSignedTransaction(), ['name' => 'SenderSignedData']);
    }

    /**
     * @return Type
     */
    public static function passkeyAuthenticator(): Type
    {
        return Bcs::struct('PasskeyAuthenticator', [
            'authenticatorData' => Bcs::vector(Bcs::u8()),
            'clientDataJson' => Bcs::string(),
            'userSignature' => Bcs::vector(Bcs::u8()),
        ]);
    }
}
