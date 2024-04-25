<?php
namespace Worken\Services;

use GuzzleHttp\Client;
use Worken\Utils\TokenProgram;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\Transaction;


class TransactionService {
    private $rpcClient;
    private $mintAddress;
    private $client;

    public function __construct($rpcClient, $mintAddress) {
        $this->rpcClient = $rpcClient;
        $this->mintAddress = $mintAddress;
        $this->client = new Client();
    }

    /**
     * Send transaction in Worken SPL token
     * 
     * @param string $sourcePrivateKey Sender private key in base58
     * @param string $destinationWallet Receiver wallet address
     * @param int $amount Amount to send in WORKEN
     * @return array
     */
    public function sendTransaction(string $sourcePrivateKey, string $destinationWallet, int $amount) {
        try {
            $hashString = TokenProgram::prepareTransaction($sourcePrivateKey, $destinationWallet, $amount, $this->mintAddress, $this->rpcClient);
            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'sendTransaction',
                    'params' => [
                        $hashString,
                        ['encoding' => 'base64']
                    ]
                ]
            ]);
    
            $result = json_decode($response->getBody(), true);
            if (isset($result['error'])) {
                return ['error' => $result['error']];
            }
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    
    /**
     * Get estimated fee for the transaction
     * 
     * @param string $sourcePrivateKey Sender private key in base58
     * @param string $destinationWallet Receiver wallet address
     * @param int $amount Amount to send in WORKEN
     * 
     * @return array
     */
    public function getEstimatedFee(string $sourcePrivateKey, string $destinationWallet, int $amount) {
        try {
            $hashString = TokenProgram::prepareTransaction($sourcePrivateKey, $destinationWallet, $amount, $this->mintAddress, $this->rpcClient);
            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getFeeForMessage',
                    'params' => [
                        $hashString,
                        ['encoding' => 'base64', "commitment" => "processed"]
                    ]
                ]
            ]);
    
            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['error'])) {
                return ['error' => $result['error']];
            }
            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get transaction status
     * 
     * @param string $signature Transaction hash
     * @return int true - success, false - error
     */
    public function getTransactionStatus(string $signature) {
        try {
            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'method'  => 'getSignatureStatuses',
                    'params'  => [
                        [$signature],
                        ['searchTransactionHistory' => true]
                    ]
                ]
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

            if (isset($data['error'])) {
                throw new \Exception("Error returned by the API: " . $data['error']['message']);
            }

            if (isset($data['result']) && isset($data['result']['value']) && $data['result']['value'][0]['err'] === null) {
                return true;
            } else {
                return false; 
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /** 
     * Get 10 recent transactions of the Worken SPL token
     * 
     * @return array
     */
    public function getRecentTransactions() {
        try {
            // Fetching transaction signatures involving the wallet address
            $signatureResponse = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getSignaturesForAddress',
                    'params' => [
                        $this->mintAddress,
                        [
                            'limit' => 10, // Adjust the limit as necessary
                        ]
                    ]
                ]
            ]);
    
            $signatures = json_decode($signatureResponse->getBody()->getContents(), true);
    
            if (isset($signatures['error'])) {
                return ['error' => $signatures['error']];
            }
            return $signatures['result'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}