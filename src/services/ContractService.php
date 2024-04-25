<?php
namespace Worken\Services;

use GuzzleHttp\Client;

class ContractService
{
    private $rpcClient;
    private $contractAddress;
    private $client;

    public function __construct($rpcClient, $contractAddress)
    {
        $this->rpcClient = $rpcClient;
        $this->contractAddress = $contractAddress;
        $this->client = new Client();
    }

    /**
     * Get contract status
     *
     * @return array
     */
    public function getContractStatus()
    {
        try {
            $result = [];

            $response = $this->client->post($this->rpcClient, [
                'json' => [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'getAccountInfo',
                    'params' => [
                        $this->contractAddress,
                        ['encoding' => 'jsonParsed']
                    ]
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);

            if (isset($responseData['result'])) {
                $result = true;
            } else {
                $result = false;
            }

            return $result;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get contract program data - to do
     *
     * @return string
     */
    // public function getContractFunction()
    // {
    //     try {
    //         $abi = "";

    //         $client = new Client();
    //         $response = $client->post($this->rpcClient, [
    //             'json' => [
    //                 'jsonrpc' => '2.0',
    //                 'id' => 1,
    //                 'method' => 'getAccountInfo',
    //                 'params' => [
    //                     $this->contractAddress,
    //                     ['encoding' => 'base64']
    //                 ]
    //             ]
    //         ]);

    //         $responseData = json_decode($response->getBody(), true);

    //         if (isset($responseData['result']['value']['data'][0])) {
    //             $abi = base64_decode($responseData['result']['value']['data'][0]);
    //         }

    //         return $abi;
    //     } catch (\Exception $e) {
    //         return ['error' => $e->getMessage()];
    //     }
    // }
}