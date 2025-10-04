<?php

class BlockchainService {
    private $fabricGateway;
    private $walletPath;
    private $connectionProfile;

    public function __construct() {
        $this->walletPath = __DIR__ . '/wallet';
        $this->connectionProfile = __DIR__ . '/connection.json';
        $this->initializeFabric();
    }

    private function initializeFabric() {
        // Fabric gateway initialization would go here
        // This is a simplified version
    }

    public function storePatientRecord($patientId, $medicalData) {
        try {
            // Hash the medical data for integrity verification
            $medicalDataHash = hash('sha256', json_encode($medicalData));
            
            // Encrypt sensitive data (you might want to use stronger encryption)
            $encryptedData = base64_encode(openssl_encrypt(
                json_encode($medicalData),
                'AES-256-CBC',
                env('BLOCKCHAIN_ENCRYPTION_KEY'),
                0,
                substr(hash('sha256', env('BLOCKCHAIN_IV')), 0, 16)
            ));

            // Prepare the blockchain transaction
            $transactionData = [
                'patientId' => $patientId,
                'medicalDataHash' => $medicalDataHash,
                'encryptedData' => $encryptedData,
                'timestamp' => time()
            ];

            // Execute chaincode transaction
            $result = $this->submitTransaction('createPatient', [
                $patientId,
                $medicalDataHash,
                $encryptedData
            ]);

            return [
                'success' => true,
                'transactionId' => $result->transactionId,
                'blockHash' => $medicalDataHash
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function verifyPatientRecord($patientId) {
        try {
            $result = $this->evaluateTransaction('readPatient', [$patientId]);
            $patientRecord = json_decode($result, true);
            
            return [
                'success' => true,
                'exists' => true,
                'record' => $patientRecord
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getPatientHistory($patientId) {
        try {
            $result = $this->evaluateTransaction('getPatientHistory', [$patientId]);
            return [
                'success' => true,
                'history' => json_decode($result, true)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function submitTransaction($functionName, $args) {
        // Implementation for submitting transactions to Fabric
        // This would use the Fabric SDK
        $command = "node " . __DIR__ . "/fabric-client/submitTransaction.js " . 
                   escapeshellarg($functionName) . " " . 
                   implode(" ", array_map('escapeshellarg', $args));
        
        $output = shell_exec($command);
        return json_decode($output);
    }

    private function evaluateTransaction($functionName, $args) {
        // Implementation for evaluating transactions (query)
        $command = "node " . __DIR__ . "/fabric-client/evaluateTransaction.js " . 
                   escapeshellarg($functionName) . " " . 
                   implode(" ", array_map('escapeshellarg', $args));
        
        $output = shell_exec($command);
        return $output;
    }
}