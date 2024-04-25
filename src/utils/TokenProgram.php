<?php

namespace Worken\Utils;

use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Transaction;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
use Tighten\SolanaPhpSdk\Util\Buffer;
use StephenHill\Base58;
use GuzzleHttp\Client;

class TokenProgram
{
    const TRANSFER_INDEX = 3; // Transfer SPL instruction index
    const TOKEN_PROGRAM_ID = 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA';
    const SYSTEM_PROGRAM_ID = '11111111111111111111111111111111';
    const ASSOCIATED_TOKEN_PROGRAM_ID = 'ATokenGPvbdGVxr1b2hvZbsiqW5xWH25efTNsLJA8knL';
    const MINT_TOKEN = '9tnkusLJaycWpkzojAk5jmxkdkxBHRkFNVSsa7tPUgLb'; // Mint WORK token address
    const MINT_DECIMALS = 5; // WORK token decimals
    
    public static function transfer(
        PublicKey $fromPubkey,
        PublicKey $toPublicKey,
        int $tokenAmount
    ): TransactionInstruction
    {
        $amount = pack('V', $tokenAmount);

        $keys = [
            new AccountMeta($fromPubkey, true, true),
            new AccountMeta($toPublicKey, false, true)
        ];

        $data = pack('C', 2) . $amount;

        return new TransactionInstruction(
            new PublicKey(self::TOKEN_PROGRAM_ID),
            $keys,
            $data
        );
    }

    /**
     * Prepare transaction
     * 
     * @param string $sourcePrivateKey Sender private key in base58
     * @param string $destinationWallet Receiver wallet address
     * @param int $amount Amount to send in WORKEN
     * @param string $rpcClient RPC client
     * @param string $mintAddress Mint address
     * 
     * @return string Transaction hash
     */
    public static function prepareTransaction(string $sourcePrivateKey, string $destinationWallet, int $amount, string $rpcClient, string $mintAddress): string {
        $fromBase58 = Buffer::fromBase58($sourcePrivateKey);
        $senderKeyPair = KeyPair::fromSecretKey($fromBase58);
        $receiverPubKey = new PublicKey($destinationWallet);
        $mintPublicKey = new PublicKey($mintAddress);
        
        // Step 1: Get or create source ATA
        $sourceAccount = TokenProgram::getOrCreateAssociatedTokenAccount(
            $senderKeyPair, $mintPublicKey, $senderKeyPair->getPublicKey(), $rpcClient
        );

        // Step 2: Get or create destination ATA
        $destinationAccount = TokenProgram::getOrCreateAssociatedTokenAccount(
            $senderKeyPair, $mintPublicKey, $receiverPubKey, $rpcClient
        );

        // Step 3: Fetch the number of decimals for the mint
        $numberDecimals = self::MINT_DECIMALS; // Decimals of SPL token

        // Calculate the actual amount to send based on token decimals
        $tokenAmount = $amount * pow(10, $numberDecimals);

        // Step 4: Create and send the transaction
        $instruction = TokenProgram::transfer(
            $sourceAccount,
            $destinationAccount,
            $tokenAmount
        );

        $recentBlockhash = TokenProgram::getRecentBlockhash($rpcClient);
        $transaction = new Transaction($recentBlockhash, null, $sourceAccount);
        $transaction->add($instruction);

        $transaction->sign($senderKeyPair); 
        $rawBinaryString = $transaction->serialize(false);
        $hashString = sodium_bin2base64($rawBinaryString, SODIUM_BASE64_VARIANT_ORIGINAL);

        return $hashString;
    }

    public static function getNumberDecimals(PublicKey $mintAddress, $rpc) {
        $client = new Client();
        $response = $client->post($rpc, [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getParsedAccountInfo',
                'params' => [$mintAddress->toBase58()]
            ]
        ]);
    
        $result = json_decode($response->getBody(), true);
        if (isset($result['result']['value']['data']['parsed']['info']['decimals'])) {
            return $result['result']['value']['data']['parsed']['info']['decimals'];
        } else {
            throw new \Exception("Failed to fetch token decimals.");
        }
    }

    /**
     * Fetches or creates an associated token account for a given mint and owner.
     * 
     * @param KeyPair $ownerKeys The keypair of the account that will own the newly created token account.
     * @param PublicKey $mintPublicKey The public key of the token for which an account will be created.
     * @param PublicKey $accountPublicKey The public key of the account that will own the newly created token account.
     * @param string $rpc The RPC endpoint URL
     * @return PublicKey The public key of the associated token account
     */
    public static function getOrCreateAssociatedTokenAccount(KeyPair $ownerKeys, PublicKey $mintPublicKey, PublicKey $accountPublicKey, $rpc)
    {
        $client = new Client();
        // Fetch existing associated token accounts
        $response = $client->post($rpc, [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getTokenAccountsByOwner',
                'params' => [
                    $accountPublicKey->toBase58(),
                    ['mint' => $mintPublicKey->toBase58()],
                    ['encoding' => 'jsonParsed']
                ]
            ]
        ]);
    
        $result = json_decode($response->getBody(), true);
        if (isset($result['error'])) {
            throw new \Exception("Failed to fetch token accounts by owner: " . $result['error']['message']);
        }
    
        // Check if there are any associated accounts
        $associatedAccounts = $result['result']['value'];
        if (!empty($associatedAccounts)) {
            // Assuming we get the first associated account if multiple
            $associatedAccountAddress = $associatedAccounts[0]['pubkey'];
            return new PublicKey($associatedAccountAddress);
        }
    
        // No associated account exists, create a new one
        $instruction = self::createAssociatedTokenAccountInstruction(
            $accountPublicKey,
            $ownerKeys->getPublicKey(), // Owner is the fee payer
            $mintPublicKey,
        );
    
        $recentBlockhash = self::getRecentBlockhash($rpc);
    
        // Create and sign transaction
        $transaction = new Transaction($recentBlockhash, null, $ownerKeys->getPublicKey()); // Owner is the fee payer
        $transaction->add($instruction);
    
        $transaction->sign($ownerKeys);
    
        // Send transaction
        $serializedTransaction = sodium_bin2base64($transaction->serialize(), SODIUM_BASE64_VARIANT_ORIGINAL);
        $response = $client->post($rpc, [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'sendTransaction',
                'params' => [
                    $serializedTransaction,
                    ['encoding' => 'base64']
                ]
            ]
        ]);
    
        $sendResult = json_decode($response->getBody(), true);
        if (isset($sendResult['error'])) {
            throw new \Exception("Failed to send transaction: " . $sendResult['error']['message']);
        }
    
        // Assuming transaction is confirmed and the new ATA address can be calculated or fetched
        $newAssociatedAccountAddress = self::findAssociatedTokenAddress($accountPublicKey, $mintPublicKey); 
        return new PublicKey($newAssociatedAccountAddress);
    }

    /**
     * Creates an instruction to create an associated token account.
     *
     * @param PublicKey $funderPublicKey The public key of the account funding the creation.
     * @param PublicKey $ownerPublicKey The public key of the account that will own the newly created token account.
     * @param PublicKey $mintPublicKey The public key of the token for which an account will be created.
     * @return TransactionInstruction
     */
    public static function createAssociatedTokenAccountInstruction(
        PublicKey $funderPublicKey,
        PublicKey $ownerPublicKey,
        PublicKey $mintPublicKey
    ): TransactionInstruction {
        $associatedTokenAccountPublicKey = self::findAssociatedTokenAddress($ownerPublicKey, $mintPublicKey);
    
        $keys = [
            new AccountMeta($funderPublicKey, true, true), // Funder account, is signer and writable
            new AccountMeta($associatedTokenAccountPublicKey, false, true), // Associated Token Account, not signer, is writable
            new AccountMeta($ownerPublicKey, false, false), // Owner account, not signer, not writable
            new AccountMeta($mintPublicKey, false, false), // Mint account, not signer, not writable
            new AccountMeta(new PublicKey(self::SYSTEM_PROGRAM_ID), false, false), // System Program, not signer, not writable
            new AccountMeta(new PublicKey(self::TOKEN_PROGRAM_ID), false, false), // Token Program, not signer, not writable
            new AccountMeta(new PublicKey(self::ASSOCIATED_TOKEN_PROGRAM_ID), false, false), // Associated Token Program, not signer, not writable
        ];
    
        // Opcode for 'Create Associated Token Account' is usually 1
        $data = pack('C', 1);
    
        return new TransactionInstruction(
            new PublicKey(self::ASSOCIATED_TOKEN_PROGRAM_ID), 
            $keys,
            $data
        );
    }

    /**
     * Finds the address of the associated token account for a given mint and owner.
     *
     * @param PublicKey $ownerPublicKey
     * @param PublicKey $mintPublicKey
     * @return PublicKey
     */
    public static function findAssociatedTokenAddress(PublicKey $ownerPublicKey, PublicKey $mintPublicKey): PublicKey {
        $base58 = new Base58();
        $binaryKey = new PublicKey($base58->decode(self::TOKEN_PROGRAM_ID));
        
        return PublicKey::findProgramAddress([
            $ownerPublicKey->toBytes(),
            $binaryKey->toBytes(),
            $mintPublicKey->toBytes()
        ], new PublicKey(self::ASSOCIATED_TOKEN_PROGRAM_ID))[0];
    }

    /**
     * Fetches the recent blockhash from the RPC endpoint.
     * 
     * @param string $rpc The RPC endpoint URL
     * @return string The recent blockhash
     */
    public static function getRecentBlockhash($rpc) {
        $client = new Client();
        $response = $client->post($rpc, [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'getRecentBlockhash'
            ]
        ]);
    
        $blockhashResponse = json_decode($response->getBody(), true);
        if (!isset($blockhashResponse['error']) && isset($blockhashResponse['result']['value']['blockhash'])) {
            return $blockhashResponse['result']['value']['blockhash'];
        } else {
            throw new \Exception("Failed to fetch recent blockhash.");
        }
    }
}
