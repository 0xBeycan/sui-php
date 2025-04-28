# SUI PHP SDK

## About

The SUI PHP SDK is a PHP library that provides convenient access to the SUI blockchain. This SDK allows PHP developers to interact with SUI blockchain nodes, create transactions, serialize and deserialize data using Binary Canonical Serialization (BCS), and manage accounts without writing low-level code.

Key features:
- Network communication with SUI nodes
- BCS serialization and deserialization
- Transaction building and signing
- Account management

## Installation

```bash
composer require sui-php/sdk
```

## Usage

```php
require 'vendor/autoload.php';

use Sui\Client;
use Sui\Bcs\Bcs;

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

var_dump($expected, $parsed);
var_dump($rustBcs, $serialized->toBase64());
```
