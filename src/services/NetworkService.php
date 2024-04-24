<?php
namespace Worken\Services;

use GuzzleHttp\Client;
use Tighten\SolanaPhpSdk\Util\Buffer;
use Tighten\SolanaPhpSdk\Keypair;
use Worken\Utils\TokenProgram;

class NetworkService {
    private $rpcClient;
    private $contractAddress;
    private $client;

    public function __construct($rpcClient, $contractAddress) {
        $this->rpcClient = $rpcClient;
        $this->contractAddress = $contractAddress;
        $this->client = new Client();
    }

    /**
     * Get block information
     * 
     * @param string $blockNumber block number 
     * @return array
     */
    public function getBlockInformation(int $blockNumber) {
        try {
            $client = new Client();
            $response = $client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getBlock',
                    'params' => [
                        $blockNumber,
                        [
                            "encoding" => "jsonParsed",
                            "transactionDetails" => "none",
                            "maxSupportedTransactionVersion" => 0,
                            "rewards" => false
                        ]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            return $result['result'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get estimated fee for transaction
     * 
     * @param string $from Sender private key in base58
     * @param string $to Receiver address
     * @param string $amount Amount to send
     * @return array
     */
    public function getEstimatedFee(string $fromPrivateKey, string $to, int $amount) {
        try {
            $fromBase58 = Buffer::fromBase58($fromPrivateKey);
            $sourceKeyPair = KeyPair::fromSecretKey($fromBase58);
            $transaction = TokenProgram::prepareTransaction($sourceKeyPair->getPublicKey(), $to, $amount, $this->contractAddress, $this->rpcClient);

            $transaction->sign($sourceKeyPair); 
            $rawBinaryString = $transaction->serialize(false);
            $hashString = sodium_bin2base64($rawBinaryString, SODIUM_BASE64_VARIANT_ORIGINAL);
            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getFeeForMessage',
                    'params' => [
                        $hashString,
                        ['encoding' => 'base64', 'preflightCommitment' => 'confirmed'] 
                    ]
                ]
            ]);
        
            $result = json_decode($response->getBody()->getContents(), true);
            return var_dump($result);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }   
    }

    /**
     * Get network status information (block height, fee rate)
     * 
     * @return array
     */
    public function getNetworkStatus() {
        try {
            $status = [];

            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getBlockHeight'
                ]
            ]);
            $blockData = json_decode($response->getBody(), true);
            if (isset($blockData['result'])) {
                $status['latestBlock'] = $blockData['result'];
            } else {
                $status['latestBlock'] = 'Error fetching block height';
            }

            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getFeeCalculatorForBlockhash',
                    'params' => ["recent", ["commitment" => "finalized"]]
                ]
            ]);
            $feeData = json_decode($response->getBody(), true);
            if (isset($feeData['result']['value']['feeCalculator'])) {
                $status['feeRateLamportsPerSignature'] = $feeData['result']['value']['feeCalculator']['lamportsPerSignature'];
            } else {
                $status['feeRateLamportsPerSignature'] = 'Error fetching fee calculator';
            }

            return $status;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get congestion status of the network
     * 
     * @return array
     */
    public function getMonitorCongestion() {
        try {
            $status = [];

            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getRecentPerformanceSamples',
                    'params' => [5]  
                ]
            ]);
            $congestionData = json_decode($response->getBody(), true);
            if (isset($congestionData['result'])) {
                $status['performanceSamples'] = $congestionData['result'];
            } else {
                $status['performanceSamples'] = 'Error fetching performance data';
            }

            return $status;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}