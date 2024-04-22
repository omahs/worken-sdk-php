<?php

namespace Worken;

use Worken\Services\WalletService;
use Worken\Services\TransactionService;
use Worken\Services\ContractService;
use Worken\Services\NetworkService;
use Tighten\SolanaPhpSdk\SolanaRpcClient;

class Worken {
    public $wallet;
    public $transaction;
    public $contract;
    public $network;
    private $solana;

    const LOCAL_ENDPOINT = 'http://localhost:8899';
    const DEVNET_ENDPOINT = 'https://api.devnet.solana.com';
    const TESTNET_ENDPOINT = 'https://api.testnet.solana.com';
    const MAINNET_ENDPOINT = 'https://api.mainnet-beta.solana.com';

    /**
     * Worken-SDK constructor
     */
    public function __construct($rpcChoice) {
        $contractAddress = "9tnkusLJaycWpkzojAk5jmxkdkxBHRkFNVSsa7tPUgLb";
        $nodeUrl = $this->resolveRpcUrl($rpcChoice);

        $this->wallet = new WalletService($nodeUrl, $contractAddress);
        //$this->contract = new ContractService($this->web3, $this->contractAddress, $this->apiKey);
        //$this->network = new NetworkService($this->web3, $this->contractAddress, $this->apiKey);
        //$this->transaction = new TransactionService($this->web3, $this->wallet, $this->network, $this->contractAddress, $this->apiKey);
    }

    private function resolveRpcUrl($choice) {
        switch ($choice) {
            case 'MAINNET':
                return self::MAINNET_ENDPOINT;
            case 'TESTNET':
                return self::TESTNET_ENDPOINT;
            case 'DEVNET':
                return self::DEVNET_ENDPOINT;
            case 'LOCALNET':
                return self::LOCAL_ENDPOINT;
            default:
                throw new \InvalidArgumentException("Invalid RPC choice. Available options are: MAINNET, TESTNET, DEVNET, LOCALNET.");
        }
    }
}