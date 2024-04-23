<?php
namespace Worken\Services;

use GuzzleHttp\Client;
use Tighten\SolanaPhpSdk\KeyPair;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\Transaction;
use Worken\Utils\TokenProgram;

class TransactionService {
    private $rpcClient;
    private $contractAddress;

    public function __construct($rpcClient, $contractAddress) {
        $this->rpcClient = $rpcClient;
        $this->contractAddress = $contractAddress;
    }

    /**
     * Send transaction
     * 
     * @param string $privateKey Sender private key
     * @param string $from Sender address in Hex
     * @param string $to Receiver address in Hex
     * @param string $amount Amount to send in WEI
     * @return array
     */
    public function sendTransaction($sourcePrivateKey, string $destinationWallet, $amount) {
        //Generate keypair for tests
        // $keypair = Keypair::generate();
        // $fromSecretKey = $keypair->secretKey;
        $fromKeyPair = KeyPair::fromSecretKey($sourcePrivateKey);
        $toPublicKey = new PublicKey($destinationWallet);
        $mintAddress = new PublicKey($this->contractAddress); 
        
        $instruction = TokenProgram::transfer(
            $fromKeyPair->getPublicKey(),
            $toPublicKey,
            $mintAddress,
            $fromKeyPair->getPublicKey(), 
            $amount
        );

        $transaction = new Transaction($fromKeyPair->getPublicKey());
        $transaction->add($instruction);

        $client = new Client();
        $response = $client->post($this->rpcClient, [
            'json' => [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'sendTransaction',
                'params' => [
                    $transaction->serialize(),
                    ['encoding' => 'base64']
                ]
            ]
        ]);

        $result = json_decode($response->getBody(), true);
        return $result;
    }

    /**
     * Get transaction status
     * 
     * @param string $txHash Transaction hash
     * @return int true - success, false - error
     */
    public function getTransactionStatus(string $signature) {
        $client = new Client();
        try {
            $response = $client->post('', [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id'      => 1,
                    'method'  => 'getTransaction',
                    'params'  => [
                        $signature,
                        'json'
                    ]
                ]
            ]);

            $body = $response->getBody();
            $data = json_decode($body, true);

            if (isset($data['error'])) {
                throw new \Exception("Error returned by the API: " . $data['error']['message']);
            }

            if (isset($data['result']) && isset($data['result']['meta']) && $data['result']['meta']['err'] === null) {
                return true;
            } else {
                return false; 
            }
        } catch (\Exception $e) {
            error_log('Exception caught while getting transaction status: ' . $e->getMessage());
            return false;
        }
    }

    /** 
     * Get 10 recent transactions of the contract - need to switch to solana
     * 
     * @return array
     */
    public function getRecentTransactions() {
        // testnet
        $url = "https://api-testnet.polygonscan.com/api?module=account&action=txlist&address={$this->contractAddress}&startblock=0&endblock=99999999&page=1&offset=10&sort=desc&apikey={$this->apiKey}";
        // mainnet
        // $url = "https://api.polygonscan.com/api?module=account&action=txlist&address={$this->contractAddress}&startblock=0&endblock=99999999&page=1&offset=10&sort=desc&apikey={$this->apiKey}";
        $history = [];
        $response = file_get_contents($url);
        if ($response === FALSE) {
            $history['error'] = "Error while fetching data from Polygonscan.";
        }

        $result = json_decode($response, true);

        if ($result['status'] == '0') {
            if ($result['message'] == 'No transactions found') {
                return $history;
            }
            $result['error'] = $result['result'];
        }

        if ($result['status'] == '1' && !empty($result['result'])) {
            foreach ($result['result'] as $transaction) {
                $history[] = $transaction;
            }
        }
        return $history;
    }
}