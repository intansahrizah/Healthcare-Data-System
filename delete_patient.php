<?php
// delete_patient.php
require_once 'vendor/autoload.php';

use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;

session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Blockchain configuration
$blockchainEnabled = true; // Set to false if you want to disable blockchain
$ganacheUrl = "http://localhost:8545";
$contractAddress = "YOUR_CONTRACT_ADDRESS";
$contractABI = '[YOUR_CONTRACT_ABI_JSON]';
$adminPrivateKey = "YOUR_ADMIN_PRIVATE_KEY";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get patient ID from URL parameter
$patientsId = $_GET['patientsId'] ?? '';

if (empty($patientsId)) {
    die("No patient specified for deletion.");
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get the complete patient data before deletion
    $select_sql = "SELECT patientsId, patientName, ic_number, gender, email, phone, address FROM patients WHERE patientsId = ?";
    $stmt = $conn->prepare($select_sql);
    $stmt->bind_param("i", $patientsId);
    $stmt->execute();
    $patient_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$patient_data) {
        throw new Exception("Patient not found.");
    }

    // 2. Record deletion in MySQL audit table (your existing system)
    $last_block_sql = "SELECT * FROM deleted_patients_audit ORDER BY block_index DESC LIMIT 1";
    $last_block_result = $conn->query($last_block_sql);
    $last_block = $last_block_result->fetch_assoc();
    
    $previous_hash = $last_block ? $last_block['current_hash'] : '0';
    $block_index = $last_block ? $last_block['block_index'] + 1 : 1;
    
    $block_data = $patient_data['patientsId'] . 
                  $patient_data['patientName'] . 
                  $patient_data['ic_number'] . 
                  $patient_data['gender'] . 
                  $patient_data['email'] . 
                  $patient_data['phone'] . 
                  $patient_data['address'] . 
                  ($_SESSION['username'] ?? 'admin') . 
                  time() . 
                  $previous_hash;
    
    $current_hash = hash('sha256', $block_data);
    
    // Insert into MySQL audit table
    $audit_sql = "INSERT INTO deleted_patients_audit 
                  (patientsId, patientName, ic_number, gender, email, phone, address, deleted_by, previous_hash, current_hash, block_index) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $audit_stmt = $conn->prepare($audit_sql);
    $deleted_by = $_SESSION['username'] ?? 'admin';
    $audit_stmt->bind_param("isssssssssi", 
        $patient_data['patientsId'],
        $patient_data['patientName'],
        $patient_data['ic_number'],
        $patient_data['gender'],
        $patient_data['email'],
        $patient_data['phone'],
        $patient_data['address'],
        $deleted_by,
        $previous_hash,
        $current_hash,
        $block_index
    );
    $audit_stmt->execute();
    $audit_stmt->close();
    
    // 3. Record deletion on Blockchain (if enabled)
    if ($blockchainEnabled) {
        recordDeletionOnBlockchain($patient_data, $deleted_by);
    }
    
    // 4. Delete from main patients table
    $delete_sql = "DELETE FROM patients WHERE patientsId = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $patientsId);
    $delete_stmt->execute();
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    if ($affected_rows === 0) {
        throw new Exception("No patient found with the specified ID.");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Redirect back to patient list with success message
    header("Location: patient_list.php?message=Patient deleted successfully&type=success");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    header("Location: patient_list.php?message=Error deleting patient: " . $e->getMessage() . "&type=error");
    exit();
}

$conn->close();

/**
 * Record patient deletion on the blockchain
 */
function recordDeletionOnBlockchain($patient_data, $deleted_by) {
    global $ganacheUrl, $contractAddress, $contractABI;
    
    try {
        // Connect to Ganache
        $web3 = new Web3(new HttpProvider(new HttpRequestManager($ganacheUrl, 10)));
        
        // Load contract
        $contract = new Contract($web3->provider, $contractABI);
        $contract->at($contractAddress);
        
        // Call deletePatient function on the smart contract
        $contract->send('deletePatient', $patient_data['patientsId'], [
            'from' => '0xYourAdminAddress', // Replace with your admin address
            'gas' => 300000
        ], function ($err, $result) use ($patient_data) {
            if ($err !== null) {
                error_log("Blockchain deletion failed for patient {$patient_data['patientsId']}: " . $err->getMessage());
                // Don't throw exception - continue with MySQL deletion even if blockchain fails
            } else {
                error_log("Patient {$patient_data['patientsId']} deleted on blockchain. TX: " . $result);
            }
        });
        
    } catch (Exception $e) {
        error_log("Blockchain connection error: " . $e->getMessage());
        // Continue with MySQL deletion even if blockchain fails
    }
}
?>