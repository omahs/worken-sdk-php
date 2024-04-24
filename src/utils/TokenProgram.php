<?php

namespace Worken\Utils;

use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\TransactionInstruction;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Transaction;
use Tighten\SolanaPhpSdk\Util\AccountMeta;
use GuzzleHttp\Client;

class TokenProgram
{
    const TRANSFER_INDEX = 3; // Transfer SPL instruction index

    public static function transfer(
        PublicKey $fromPubkey,
        PublicKey $toPublicKey,
        PublicKey $mintPublicKey,
        int $amount
    ): TransactionInstruction
    {
        $data = pack('C', self::TRANSFER_INDEX) . pack('V', $amount);

        $keys = [
            new AccountMeta($fromPubkey, true, true), // From account (signer)
            new AccountMeta($toPublicKey, false, true), // To account
            new AccountMeta($mintPublicKey, false, false), // Mint account
            new AccountMeta($fromPubkey, false, true), // Authority account (same as from account)
        ];

        return new TransactionInstruction(
            self::tokenProgramId(),
            $keys,
            $data
        );
    }

    public static function tokenProgramId()
    {
        return new PublicKey("TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA"); // Token program ID
    }

    public static function prepareTransaction($sourcePublicKey, $destinationWallet, $amount, $contractAddress, $rpc) {
        $fromPublicKey = new PublicKey($sourcePublicKey);
        $toPublicKey = new PublicKey($destinationWallet);
        $mintAddress = new PublicKey($contractAddress); 
        
        $instruction = TokenProgram::transfer(
            $fromPublicKey,
            $toPublicKey,
            $mintAddress,
            $amount
        );

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
            $recentBlockhash = $blockhashResponse['result']['value']['blockhash'];
        } else {
            // obsługa błędu, gdy blockhash nie może być pobrany
            throw new \Exception("Failed to fetch recent blockhash.");
        }

        $transaction = new Transaction($recentBlockhash, null, $fromPublicKey);
        $transaction->add($instruction);

        return $transaction;
    }
}
