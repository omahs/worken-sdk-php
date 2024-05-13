<?php
namespace Worken\Services;

use GuzzleHttp\Client;
use Worken\Utils\TokenProgram;
use Worken\Utils\Constants;

class TransactionService {
    private $rpcClient;
    private $mintAddress;
    private $client;

    public function __construct($rpcClient) {
        $this->rpcClient = $rpcClient;
        $this->mintAddress = Constants::MINT_TOKEN;
        $this->client = new Client();
    }

    /**
     * Prepare transaction in Worken SPL token
     * 
     * @param string $sourcePrivateKey Sender private key in base58
     * @param string $sourceWallet Sender wallet address
     * @param string $destinationWallet Receiver wallet address
     * @param float $amount Amount to send in WORKEN | 0.00001 Worken = 1, 0.0001 Worken = 10, 0.001 Worken = 100, 0.01 Worken = 1000, 0.1 Worken = 10000, 1 Worken = 100000
     * @return string
     */
    public function prepareTransaction(string $sourcePrivateKey, string $sourceWallet, string $destinationWallet, int $amount): array {
        try {
            $hashString = TokenProgram::prepareTransaction($sourcePrivateKey, $sourceWallet, $destinationWallet, $amount, $this->rpcClient);
            return ['success' => true, 'data' => $hashString];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    /**
     * Prepare transaction with burn in Worken SPL token (possible to send SOL too)
     * 
     * @param string $sourcePrivateKey Sender private key in base58
     * @param string $sourceWallet Sender wallet address
     * @param string $destinationWallet Receiver wallet address
     * @param float $sendAmount Amount to send in WORKEN
     * @param float $burnAmount Amount to burn in WORKEN
     * @param float $solAmount Amount to send in SOL (optional)
     * 
     * @return string
     */
    public function prepareTransactionWithBurn(string $sourcePrivateKey, string $sourceWallet, string $destinationWallet, int $sendAmount, int $burnAmount, int $solAmount = 0): array {
        try {
            $hashString = TokenProgram::prepareTransactionWithBurn($sourcePrivateKey, $sourceWallet, $destinationWallet, $sendAmount, $burnAmount, $this->rpcClient, $solAmount);
            return ['success' => true, 'data' => $hashString];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    /**
     * Send prepared transaction
     * 
     * @param string $hashString prepared transaction hash
     * 
     */
    public function sendTransaction(array $hashString) {
        try {
            if($hashString['success'] == false) {
                return $hashString;
            }
            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'sendTransaction',
                    'params' => [
                        $hashString['data'],
                        ['encoding' => 'base64']
                    ]
                ]
            ]);
    
            $result = json_decode($response->getBody(), true);
            if (isset($result['error'])) {
                return ['success' => false, 'data' => $result['error']];
            }
            $signature = $result['result'];
            return ['success' => true, 'data' => $signature];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    
    /**
     * Get estimated fee for the transaction
     * 
     * @param string $hashString prepared transaction hash
     * 
     */
    public function getEstimatedFee(string $hashString) {
        try {
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

            $fee = $result['result']['value']['fee'];
            return $fee;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get transaction status
     * 
     * @param string $signature Transaction hash
     * @return boolean true - success, false - error
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

            if (isset($data['result']) && 
                isset($data['result']['value']) && 
                is_array($data['result']['value']) && 
                isset($data['result']['value'][0]) && 
                isset($data['result']['value'][0]['err']) && 
                $data['result']['value'][0]['err'] === null) {
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