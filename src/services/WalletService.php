<?php
namespace Worken\Services;

use Tighten\SolanaPhpSdk\Keypair;
use Tighten\SolanaPhpSdk\PublicKey;
use GuzzleHttp\Client;

class WalletService {
    private $rpcClient;
    private $contractAddress;

    public function __construct($rpcClient, $contractAddress) {
        $this->rpcClient = $rpcClient;
        $this->contractAddress = $contractAddress;
    }

    /**
     * Get balance of WORK token for a given wallet address
     * 
     * @param string $address
     * @return array Balance in lamports, SOL, and Hex value
     */
    public function getBalance(string $address) {
        try {
            $client = new Client();

            $response = $client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getTokenAccountsByOwner',
                    'params' => [
                        $address,
                        ["mint" => $this->contractAddress],
                        ["encoding" => "jsonParsed"]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            
            if(isset($result['error'])) {
                return ['error' => $result['error']];
            }

            if (isset($result['result'])) {
                $value = $result['result']['value'][0] ?? null;
            
                if ($value && isset($value['account']['data']['parsed']['info']['tokenAmount'])) {
                    $amount = $value['account']['data']['parsed']['info']['tokenAmount']['amount'] ?? null;
                    $decimals = $value['account']['data']['parsed']['info']['tokenAmount']['decimals'] ?? null;
                    $uiAmount = $value['account']['data']['parsed']['info']['tokenAmount']['uiAmount'] ?? null;
                    $uiAmountString = $value['account']['data']['parsed']['info']['tokenAmount']['uiAmountString'] ?? null;
            
                    $tokenAmount = [
                        'amount' => $amount,
                        'decimals' => $decimals,
                        'uiAmount' => $uiAmount,
                        'uiAmountString' => $uiAmountString
                    ];
            
                    return $tokenAmount;
                } else {
                    return [
                        'amount' => '0',
                        'decimals' => 0,
                        'uiAmount' => 0,
                        'uiAmountString' => '0'
                    ];
                }
            } else {
                return [
                    'amount' => '0',
                    'decimals' => 0,
                    'uiAmount' => 0,
                    'uiAmountString' => '0'
                ];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get information about a Solana wallet
     * 
     * @param string $address
     * @return array
     */
    public function getInformation(string $address) {
        try {
            $client = new Client();

            $response = $client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getAccountInfo',
                    'params' => [
                        $address,
                        ["encoding" => "base58"]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            
            if(isset($result['error'])) {
                return ['error' => $result['error']];
            }

            if (isset($result['result'])) {
                return $result['result']['value'] ?? null;
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Create a new SOL wallet
     * 
     * @return array Wallet information (private key, public key)
     */
    public function createWallet() {
        $keypair = Keypair::generate();
        return [
            'privateKey' => $keypair->getSecretKey(), // Serialize the key for storage/transmission
            'publicKey' => $keypair->getPublicKey()->toBase58(),
        ];
    }

    /**
     * Get history of transactions for a given address
     * 
     * @param string $address
     * @return array
     */
    // public function getHistory(string $address) {
    //     try {
    //         $client = new Client();

    //         $response = $client->post($this->rpcClient, [
    //             'json' => [
    //                 'jsonrpc' => '2.0',
    //                 'id' => 1,
    //                 'method' => 'getSignaturesForAddress',
    //                 'params' => [
    //                     $address,
    //                     ["limit" => 5]
    //                 ]
    //             ]
    //         ]);

    //         $result = json_decode($response->getBody(), true);
            
    //         if(isset($result['error'])) {
    //             return ['error' => $result['error']];
    //         }

    //         if (isset($result['result'])) {
    //             return $result['result'] ?? null;
    //         }
    //     } catch (\Exception $e) {
    //         return ['error' => $e->getMessage()];
    //     }
    // }
}