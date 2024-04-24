<?php
namespace Worken\Services;

use GuzzleHttp\Client;
use Worken\Utils\TokenProgram;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\Util\Buffer;

class TransactionService {
    private $rpcClient;
    private $contractAddress;

    public function __construct($rpcClient, $contractAddress) {
        $this->rpcClient = $rpcClient;
        $this->contractAddress = $contractAddress;
    }

    /**
     * Send transaction in Worken SPL token
     * 
     * @param string $sourcePubKey Sender private key in base58
     * @param string $destinationWallet Receiver wallet address
     * @param int $amount Amount to send
     * @return array
     */
    public function sendTransaction($sourcePrivateKey, string $destinationWallet, $amount) {
        try {
            $fromBase58 = Buffer::fromBase58($sourcePrivateKey);
            $sourceKeyPair = KeyPair::fromSecretKey($fromBase58);
    
            $transaction = TokenProgram::prepareTransaction($sourceKeyPair->getPublicKey(), $destinationWallet, $amount, $this->contractAddress, $this->rpcClient);
    
            $transaction->sign($sourceKeyPair); 

            $rawBinaryString = $transaction->serialize(false);
            $hashString = sodium_bin2base64($rawBinaryString, SODIUM_BASE64_VARIANT_ORIGINAL);
    
            $client = new Client();
            $response = $client->post($this->rpcClient, [
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
     * Get transaction status
     * 
     * @param string $signature Transaction hash
     * @return int true - success, false - error
     */
    public function getTransactionStatus(string $signature) {
        try {
            $client = new Client();
            $response = $client->post($this->rpcClient, [
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
            $client = new Client();
            // Fetching transaction signatures involving the wallet address
            $signatureResponse = $client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getSignaturesForAddress',
                    'params' => [
                        $this->contractAddress,
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