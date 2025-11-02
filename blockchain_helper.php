<?php
require 'vendor/autoload.php';

use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;

class BlockchainHelper {
    private $web3;
    private $contract;
    private $ganacheUrl;

    public function __construct() {
        $this->ganacheUrl = "http://127.0.0.1:7545";
        $this->web3 = new Web3(new HttpProvider(new HttpRequestManager($this->ganacheUrl)));
    }

    // Get patient's transaction history
    public function getPatientTransactions($patientAddress) {
        try {
            $transactions = [];
            
            // Get latest block number
            $this->web3->eth->blockNumber(function ($err, $blockNumber) use (&$latestBlock) {
                if ($err !== null) {
                    throw new Exception("Error getting block number: " . $err->getMessage());
                }
                $latestBlock = $blockNumber->toString();
            });

            // Scan recent blocks for transactions involving this patient
            $startBlock = max(0, $latestBlock - 1000); // Last 1000 blocks
            
            for ($i = $startBlock; $i <= $latestBlock; $i++) {
                $this->web3->eth->getBlockByNumber('0x' . dechex($i), true, function ($err, $block) use ($patientAddress, &$transactions) {
                    if ($err === null && $block) {
                        foreach ($block->transactions as $tx) {
                            if (strtolower($tx->from) == strtolower($patientAddress) || 
                                strtolower($tx->to) == strtolower($patientAddress)) {
                                $transactions[] = [
                                    'hash' => $tx->hash,
                                    'from' => $tx->from,
                                    'to' => $tx->to,
                                    'value' => $tx->value->toString(),
                                    'blockNumber' => $block->number->toString(),
                                    'timestamp' => isset($block->timestamp) ? $block->timestamp->toString() : 'N/A'
                                ];
                            }
                        }
                    }
                });
            }
            
            return array_slice($transactions, 0, 10); // Return last 10 transactions
        } catch (Exception $e) {
            error_log("Blockchain error: " . $e->getMessage());
            return [];
        }
    }

    // Get transaction details
    public function getTransactionDetails($txHash) {
        try {
            $details = [];
            
            $this->web3->eth->getTransactionByHash($txHash, function ($err, $tx) use (&$details) {
                if ($err === null && $tx) {
                    $details = [
                        'hash' => $tx->hash,
                        'from' => $tx->from,
                        'to' => $tx->to,
                        'value' => $tx->value->toString(),
                        'gas' => $tx->gas->toString(),
                        'gasPrice' => $tx->gasPrice->toString(),
                        'blockNumber' => $tx->blockNumber->toString(),
                        'input' => $tx->input
                    ];
                }
            });

            return $details;
        } catch (Exception $e) {
            error_log("Transaction details error: " . $e->getMessage());
            return [];
        }
    }

    // Check if address is valid
    public function isValidAddress($address) {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }
}
?>