<p align="center">
  <img src="https://zrcdn.net/static/img/logos/paidwork/paidwork-logo-github.png" alt="Paidwork" />
</p>

<h3 align="center">
  Send & Receive secure Blockchain transactions on Solana with Worken
</h3>
<p align="center">
  🚀 Over 20M+ Users using Worken!
</p>

<p align="center">
  <a href="https://github.com/paidworkco/worken-sdk-php">
    <img alt="GitHub Repository Stars Count" src="https://img.shields.io/github/stars/paidworkco/worken-sdk-php?style=social" />
  </a>
    <a href="https://x.com/paidworkco">
        <img alt="Follow Us on X" src="https://img.shields.io/twitter/follow/paidworkco?style=social" />
    </a>
</p>
<p align="center">
    <a href="http://commitizen.github.io/cz-cli/">
        <img alt="Commitizen friendly" src="https://img.shields.io/badge/commitizen-friendly-brightgreen.svg" />
    </a>
    <a href="https://github.com/paidworkco/worken-sdk-php">
        <img alt="License" src="https://img.shields.io/github/license/paidworkco/worken-sdk-php" />
    </a>
    <a href="https://github.com/paidworkco/worken-sdk-php/pulls">
        <img alt="PRs Welcome" src="https://img.shields.io/badge/PRs-welcome-brightgreen.svg" />
    </a>
</p>

SDK library providing access to make easy and secure Blockchain transactions with Worken. <a href="https://www.paidwork.com/worken?utm_source=github.com&utm_medium=referral&utm_campaign=readme" target="_blank">Read more</a> about Worken Token.

Feel free to try out our provided Postman collection. Simply click the button below to fork the collection and start testing.<br>

[<img src="https://run.pstmn.io/button.svg" alt="Run In Postman" style="width: 128px; height: 32px;">](https://god.gw.postman.com/run-collection/32839969-fd54da1c-0e5b-43e8-9d89-8330e9bebf17?action=collection%2Ffork&source=rip_markdown&collection-url=entityId%3D32839969-fd54da1c-0e5b-43e8-9d89-8330e9bebf17%26entityType%3Dcollection%26workspaceId%3Dbeab0417-9a12-472d-8f22-3c7c478123a9)

## Install

Via Composer (tba.)

```
$ composer require paidworkco/worken-sdk
```

## Usage

#### Initialization & Configuration
```php
use Worken\Worken;
$worken = new Worken("MAINNET"); // Create worken object, you can use MAINNET, TESTNET, DEVNET and LOCALNET
```
- `MAINNET` - https://api.mainnet-beta.solana.com
- `TESTNET` - https://api.testnet.solana.com
- `DEVNET` - https://api.devnet.solana.com
- `LOCAL` - http://localhost:8899
### Wallet
#### Get wallet balance
```php
$worken->wallet->getBalance(string $address)
```
| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `address` | `string` | **Required**. Your wallet address |

This structure details the balance of a wallet in terms of the WORK token specified in contract.

**Output**

- `amount` (string): The balance of the wallet expressed in WORK tokens, which are the unit of your token. Due to its size, the balance is represented as a string to maintain precision. Example: 841378428
- `decimals` (int): The number of decimal places used to accurately specify the balance of WORK tokens. Example: 5
- `uiAmount` (float): The balance of the wallet converted to WORK tokens, providing a more readable representation of the balance. Example: 8413.78428
- `uiAmountString` (string): The balance of the wallet converted to WORK tokens, represented as a string. Example: 8413.78428

#### Get wallet information
```php
$worken->wallet->getInformation(string $address)
```
| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `address` | `string` | **Required**. Your wallet address |

The `getInformation` function returns information about a specified wallet in the Solana blockchain. The data structure returned by this function includes the following types of information:

**Output**

- `data` (array): An array containing additional data related to the wallet. It includes two elements:
  - Index 0: Additional data associated with the wallet, if available.
  - Index 1: The encoding format of the wallet address (e.g., Base58).
- `executable` (boolean): Indicates whether the wallet contains executable code.
- `lamports` (integer): The number of lamports owned by the wallet. Lamports are the smallest unit of currency in the Solana blockchain.
- `owner` (string): The public key of the entity that owns the wallet. It's represented as a 32-character string.
- `rentEpoch` (float): The epoch at which the current rent state was computed for this account.
- `space` (integer): The number of bytes of memory allocated to the wallet.

*TO DO: more informations if needed*

#### Get wallet transaction history
```php
$worken->wallet->getHistory(string $address)
```
| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `address` | `string` | **Required**. Your wallet address |

The `getHistory` function returns an array containing the transaction history of a specified wallet in the Solana blockchain. Each transaction record within the array consists of the following fields:

**Output**

- `blockTime` (integer): The timestamp of the block in which the transaction was included, represented as Unix time (seconds since the Unix epoch).
- `confirmationStatus` (string): The transaction's cluster confirmation status. The status can either be processed, confirmed, or finalized
- `err` (mixed): Details any error encountered during the processing of the transaction, if applicable. If the transaction was successful, this field will be null.
- `memo` (mixed): Additional information or comments associated with the transaction, if available.
- `signature` (string): The signature of the transaction, which uniquely identifies it on the blockchain.
- `slot` (integer): The slot in which the transaction was included in the Solana blockchain.

#### Create new wallet
```php
$worken->wallet->createWallet(int $words = 12)
```
| Parameter | Type     | Description                       |
| :-------  | :------- | :-------------------------------- |
| `words`   | `int` | **Required**. Choose amount of seedphrase words (12 or 24) |

The `createWallet` function returns an array containing the following key-value pairs representing the details of the newly created wallet:

**Output**

- `seedPhrase` (array): The seedphrase for recovering wallet. 12 or 24 words
- `privateKeyBase58` (string): The private key of the wallet encoded in Base58 format. This key is essential for signing transactions and accessing the wallet's funds securely.
- `publicKey` (string): The public key of the wallet, which serves as its unique identifier on the Solana blockchain. This key is used to receive funds and verify transactions associated with the wallet.

### Contract

#### Show contract status 
```php
$worken->contract->getContractStatus()
```
**Output**

- `status(boolean)`: `true` - contract active, `false` - contract unactive & freezed

<!-- #### Show contract functions
```php
$worken->contract->getContractFunctions()
```
This function give all ABI functions of Worken contract in `string`. -->

### Transactions

#### Example sending transaction in Worken using SDK
```php
<?php
require_once 'vendor/autoload.php';
use Worken\Worken;

$worken = new Worken('MAINNET');
$hashString = $worken->transaction->prepareTransaction("21ZcK4YbmSPF2dDSBwZ6dSMktcPv7vRREEi86woq8tj3NCxefZfTMFfh5KebNsLrFmCsKchXxPfHSokX24aXtmRK", "DBdtqVQcby2YoPVVAH4jXgubeSR9HANvPrSo24DVUQB5", 5); // example data
$fee = $worken->transaction->getEstimatedFee($hashString);
$signature = $worken->transaction->sendTransaction($hashString);
```

#### Prepare transaction 
```php
$worken->transaction->prepareTransaction(string $sourcePrivateKey, string $sourceWallet, string $destinationWallet, float $amount)
```
| Parameter     | Type        | Description                                                      |
| :------------ | :---------- | :--------------------------------------------------------------- |
| `sourcePrivateKey`  | `string`    | **Required**. Sender wallet private key to authorize transaction in base58 |                      |
| `sourceWallet`  | `string`    | **Required**. Sender wallet address |                      |
| `destinationWallet`          | `string`    | **Required**. Receiver wallet address                      |
| `amount`      | `int`    | **Required**. Amount of WORK token - 1 = 0.00001 WORK, 100000 = 1 WORK                       |

This function prepare transaction in WORK token using Solana blockchain.

- `hashString (string)`: Transaction hashString

#### Send transaction 
```php
$worken->transaction->sendTransaction(string $hashString)
```
| Parameter     | Type        | Description                                                      |
| :------------ | :---------- | :--------------------------------------------------------------- |
| `hashString`  | `string`    | **Required**. Prepared transaction in base64 |                      |

This function send prepared transaction using Solana blockchain.

- `signature (string)`: Transaction signature

#### Show estimated fee
```php
$worken->network->getEstimatedFee(string $hashString)
```
| Parameter     | Type        | Description                                                      |
| :------------ | :---------- | :--------------------------------------------------------------- |
| `hashString`  | `string`    | **Required**. Prepared transaction in base64 |                      |

This structure provides the estimated gas required for a transaction on the Solana network.

- `fee (int)`: This field represents the estimated fee in lamports that the network will charge for processing the transaction. The fee is calculated based on the current network congestion, the computational resources the transaction consumes, and the current fee schedule of the Solana network.

#### Show transaction status
```php
$worken->transaction->getTransactionStatus(string $signature)
```
| Parameter  | Type     | Description                    |
| :--------- | :------- | :----------------------------- |
| `signature`   | `string` | **Required**. Transaction signature |

**Output**

- `boolean`: true - Success, false - Failed or not found

#### Show recent transactions (10)
```php
$worken->transaction->getRecentTransactions()
```
This function gives latest 10 transactions on Worken contract. Each transaction contains the variables described below.

**Output**

The getRecentTransactions function returns an array containing up to 10 recent transaction objects. Each transaction object has the following structure:

- `blockTime` (int): The timestamp representing the time when the transaction was confirmed in a block on the Solana blockchain.
- `confirmationStatus` (string): The status of the transaction confirmation, indicating whether the transaction is finalized.
- `err` (mixed): An optional field indicating any error associated with the transaction. It may contain error details if the transaction encountered an issue during execution.
- `memo` (mixed): An optional field that may contain additional information or notes associated with the transaction, if provided.
- `signature` (string): The unique identifier or signature of the transaction, which can be used to reference or track the transaction on the blockchain.
- `slot` (int): The slot number in which the transaction was included, representing its position within the Solana blockchain's transaction history.
Each transaction object provides essential details about a specific transaction, including its confirmation status, timestamp, and any associated errors or memos.

### Network

#### Show block information
```php
$worken->network->getBlockInformation(int $blockNumber)
```
| Parameter     | Type     | Description                   |
| :------------ | :------- | :---------------------------- |
| `blockNumber` | `int`    | **Required**. Number of block |

The getBlockInformation function returns an array containing information about a specific block on the Solana blockchain. The structure of the returned array is as follows:

**Output**

- `blockHeight` (int): The height or number of the block within the blockchain.
- `blockTime` (int): The timestamp representing the time when the block was created or finalized.
- `blockhash` (string): The unique identifier or hash of the block.
- `parentSlot` (int): The slot number of the parent block, indicating the relationship between blocks in the blockchain.
- `previousBlockhash` (string): The hash of the previous block in the blockchain, establishing the link between consecutive blocks.

#### Show network status
```php
$worken->network->getNetworkStatus()
```
This function returns an array containing the following keys and values about Polygon network:

- `latestBlock (int)`: The number of the most recent block in the network.
Example: `282854036`

- `feeRateLamportsPerSignature (int)`: This key represents the current fee rate in lamports required for each signature in a transaction. This fee is necessary for calculating the total transaction cost when submitting transactions to the network.
Example: `5000`

#### Show monitor network congestion
```php
$worken->network->getMonitorCongestion()
```

The `getMonitorCongestion` function returns an array containing performance samples for monitoring congestion on the Solana blockchain. The structure of the returned array is as follows:

- `performanceSamples` (array): An array of performance samples, each containing the following information:
  - `numNonVoteTransactions` (int): The number of non-vote transactions processed during the sample period.
  - `numSlots` (int): The number of slots processed during the sample period.
  - `numTransactions` (int): The total number of transactions processed during the sample period.
  - `samplePeriodSecs` (int): The duration of the sample period in seconds.
  - `slot` (int): The slot number corresponding to the end of the sample period.
