<?php
// blockchain_backup.php
class BlockchainBackupHelper {
    private $ganacheUrl;
    private $contractAddress;
    
    public function __construct() {
        $this->ganacheUrl = "http://127.0.0.1:7545";
        $this->contractAddress = "0x742d35Cc6634C0532925a3b8Dc9F5a6f6E8b8C1a";
    }
    
    /**
     * Store patient data hash on blockchain
     */
    public function backupPatientToBlockchain($patientData) {
        try {
            // Create a unique hash of patient data
            $patientHash = $this->createPatientHash($patientData);
            
            // Simulate storing on blockchain (in real implementation, use web3.php)
            $txData = [
                'patientId' => $patientData['patientsId'],
                'patientName' => $patientData['patientName'],
                'dataHash' => $patientHash,
                'timestamp' => time(),
                'action' => 'PATIENT_BACKUP',
                'txHash' => '0x' . $this->generateRandomHash(),
                'blockNumber' => $this->getCurrentBlockNumber(),
                'contractAddress' => $this->contractAddress
            ];
            
            // Store in local backup file (simulating blockchain storage)
            $this->saveToLocalBlockchain($txData);
            
            return $txData;
            
        } catch (Exception $e) {
            error_log("Blockchain backup failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create SHA-256 hash of patient data
     */
    private function createPatientHash($patientData) {
        $dataString = implode('|', [
            $patientData['patientsId'],
            $patientData['patientName'],
            $patientData['ic_number'] ?? '',
            $patientData['gender'] ?? '',
            $patientData['email'] ?? '',
            $patientData['phone'] ?? '',
            $patientData['address'] ?? '',
            $patientData['blockchain_address'] ?? '',
            time()
        ]);
        
        return hash('sha256', $dataString);
    }
    
    /**
     * Save backup to local file (simulating blockchain)
     */
    private function saveToLocalBlockchain($txData) {
        $backupFile = 'blockchain_backups.json';
        $backups = [];
        
        if (file_exists($backupFile)) {
            $backups = json_decode(file_get_contents($backupFile), true) ?? [];
        }
        
        $backups[] = $txData;
        file_put_contents($backupFile, json_encode($backups, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get all blockchain backups
     */
    public function getAllBackups() {
        $backupFile = 'blockchain_backups.json';
        if (file_exists($backupFile)) {
            return json_decode(file_get_contents($backupFile), true) ?? [];
        }
        return [];
    }
    
    /**
     * Find backup by patient ID
     */
    public function findBackupByPatientId($patientId) {
        $backups = $this->getAllBackups();
        foreach ($backups as $backup) {
            if ($backup['patientId'] == $patientId) {
                return $backup;
            }
        }
        return null;
    }
    
    /**
     * Verify data integrity against blockchain backup
     */
    public function verifyDataIntegrity($patientData) {
        $backup = $this->findBackupByPatientId($patientData['patientsId']);
        if (!$backup) {
            return false;
        }
        
        $currentHash = $this->createPatientHash($patientData);
        return $currentHash === $backup['dataHash'];
    }
    
    private function generateRandomHash() {
        $characters = '0123456789abcdef';
        $hash = '';
        for ($i = 0; $i < 64; $i++) {
            $hash .= $characters[rand(0, 15)];
        }
        return $hash;
    }
    
    private function getCurrentBlockNumber() {
        return rand(1000, 10000);
    }
}
?>