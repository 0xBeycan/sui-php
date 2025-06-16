# SUI PHP SDK

## About

SUI PHP SDK is a PHP library that provides easy access to the SUI blockchain. This SDK allows PHP developers to interact with SUI blockchain nodes, create transactions, serialize and deserialize data using Binary Canonical Serialization (BCS), and manage accounts without writing low-level code. It is also fully implemented from the @mysten/sui (https://sdk.mystenlabs.com/typescript) TypeScript library. In other words, it is designed with the same structure there and you can easily find what you need in this library by examining the document in the link.

Key features:

- Network communication with SUI nodes
- BCS serialization and deserialization
- Transaction building and signing
- Account management

## Installation

```bash
composer require sui-php/sdk
```

## Example

```php
require 'vendor/autoload.php';

use Sui\Utils;
use Sui\Client;
use Sui\Bcs\Bcs;
use Sui\Keypairs\Ed25519\Keypair;
use Sui\Transactions\Inputs;
use Sui\Transactions\Commands;
use Sui\Transactions\Transaction;
use Sui\Transactions\BuildTransactionOptions;

$client = new Client('https://fullnode.devnet.sui.io:443');

$coin = Bcs::struct('Coin', [
    'value' => Bcs::u64(),
    'owner' => Bcs::string(),
    'is_locked' => Bcs::bool(),
]);

$rustBcs = 'gNGxBWAAAAAOQmlnIFdhbGxldCBHdXkA';
$expected = [
    'owner' => 'Big Wallet Guy',
    'value' => '412412400000',
    'is_locked' => false,
];

$serialized = $coin->serialize($expected, ['initialSize' => 1, 'maxSize' => 1024]);
$parsed = $coin->fromBase64($rustBcs);

$ref = [
    'objectId' => str_pad(strval(mt_rand(0, 100000)), 64, '0'),
    'version' => strval(mt_rand(0, 10000)),
    'digest' => Utils::toBase58(json_decode('[
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0,
        1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1,
        2, 3, 4, 5, 6, 7, 8, 9, 1, 2
    ]'))
];

$keypair = Keypair::generate();
$tx = new Transaction(new BuildTransactionOptions($client));

$tx->setSender($keypair->getPublicKey()->toSuiAddress());
$tx->setGasPrice(5);
$tx->setGasBudget(100);
$tx->setGasPayment([$ref]);

$coin = $tx->splitCoins($tx->gas(), [100]);

$tx->add(Commands::mergeCoins($tx->gas(), [$coin, $tx->object(Inputs::objectRef(
    $ref['objectId'],
    $ref['digest'],
    $ref['version']
))]));

$tx->add(Commands::moveCall([
    'target' => '0x2::devnet_nft::mint',
    'typeArguments' => [],
    'arguments' => [
        $tx->pureFactory->string('foo'),
        $tx->pureFactory->string('bar'),
        $tx->pureFactory->string('baz'),
    ],
]));

$bytes = $tx->build();

$serializedSignature = $keypair->signTransaction($bytes);
```
