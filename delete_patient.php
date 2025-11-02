<?php
// delete_patient.php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get patient ID from URL parameter
$patientsId = isset($_GET['patientsId']) ? intval($_GET['patientsId']) : 0;

if ($patientsId <= 0) {
    header("Location: patient_list.php?message=Invalid patient ID&type=error");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // 1. Get the complete patient data before deletion
    $select_sql = "SELECT patientsId, patientName, ic_number, gender, email, phone, address, blockchain_address FROM patients WHERE patientsId = ?";
    $stmt = $conn->prepare($select_sql);
    $stmt->bind_param("i", $patientsId);
    $stmt->execute();
    $patient_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$patient_data) {
        throw new Exception("Patient not found.");
    }

    $patientName = $patient_data['patientName'];

    // 2. Check if deleted_patients_audit table exists, create if not
    $check_audit_table = $conn->query("SHOW TABLES LIKE 'deleted_patients_audit'");
    if ($check_audit_table->num_rows == 0) {
        // Create audit table if it doesn't exist
        $create_audit_sql = "CREATE TABLE deleted_patients_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patientsId INT NOT NULL,
            patientName VARCHAR(255) NOT NULL,
            ic_number VARCHAR(20),
            gender VARCHAR(10),
            email VARCHAR(255),
            phone VARCHAR(20),
            address TEXT,
            blockchain_address VARCHAR(255),
            deleted_by VARCHAR(255),
            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            previous_hash VARCHAR(255),
            current_hash VARCHAR(255),
            block_index INT
        )";
        if (!$conn->query($create_audit_sql)) {
            throw new Exception("Failed to create audit table: " . $conn->error);
        }
    }

    // 3. Get last block for hash chain
    $last_block_sql = "SELECT * FROM deleted_patients_audit ORDER BY block_index DESC LIMIT 1";
    $last_block_result = $conn->query($last_block_sql);
    $last_block = $last_block_result ? $last_block_result->fetch_assoc() : null;
    
    $previous_hash = $last_block ? $last_block['current_hash'] : '0';
    $block_index = $last_block ? $last_block['block_index'] + 1 : 1;
    
    // Create hash chain data
    $block_data = $patient_data['patientsId'] . 
                  $patient_data['patientName'] . 
                  ($patient_data['ic_number'] ?? '') . 
                  ($patient_data['gender'] ?? '') . 
                  ($patient_data['email'] ?? '') . 
                  ($patient_data['phone'] ?? '') . 
                  ($patient_data['address'] ?? '') . 
                  ($patient_data['blockchain_address'] ?? '') .
                  ($_SESSION['username'] ?? 'admin') . 
                  time() . 
                  $previous_hash;
    
    $current_hash = hash('sha256', $block_data);
    
    // 4. Prepare data for audit table (ensure no null values)
    $audit_patientsId = $patient_data['patientsId'];
    $audit_patientName = $patient_data['patientName'] ?? '';
    $audit_ic_number = $patient_data['ic_number'] ?? '';
    $audit_gender = $patient_data['gender'] ?? '';
    $audit_email = $patient_data['email'] ?? '';
    $audit_phone = $patient_data['phone'] ?? '';
    $audit_address = $patient_data['address'] ?? '';
    $audit_blockchain_address = $patient_data['blockchain_address'] ?? '';
    $deleted_by = $_SESSION['username'] ?? 'admin';
    
    // 5. Insert into MySQL audit table
    $audit_sql = "INSERT INTO deleted_patients_audit 
                  (patientsId, patientName, ic_number, gender, email, phone, address, blockchain_address, deleted_by, previous_hash, current_hash, block_index) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $audit_stmt = $conn->prepare($audit_sql);
    $audit_stmt->bind_param("issssssssssi", 
        $audit_patientsId,
        $audit_patientName,
        $audit_ic_number,
        $audit_gender,
        $audit_email,
        $audit_phone,
        $audit_address,
        $audit_blockchain_address,
        $deleted_by,
        $previous_hash,
        $current_hash,
        $block_index
    );
    
    if (!$audit_stmt->execute()) {
        throw new Exception("Failed to create audit record: " . $audit_stmt->error);
    }
    $audit_stmt->close();
    
    // 6. Disable foreign key checks to avoid constraint issues
    $conn->query("SET FOREIGN_KEY_CHECKS=0");
    
    // 7. Delete from main patients table
    $delete_sql = "DELETE FROM patients WHERE patientsId = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $patientsId);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Delete failed: " . $delete_stmt->error);
    }
    
    $affected_rows = $delete_stmt->affected_rows;
    $delete_stmt->close();
    
    // 8. Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    if ($affected_rows === 0) {
        throw new Exception("No patient found with the specified ID.");
    }
    
    // Commit transaction
    $conn->commit();
    
    // Redirect back to patient list with success message
    header("Location: patient_list.php?message=Patient '" . urlencode($patientName) . "' deleted successfully&type=success");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Re-enable foreign key checks in case of error
    $conn->query("SET FOREIGN_KEY_CHECKS=1");
    
    header("Location: patient_list.php?message=Error deleting patient: " . urlencode($e->getMessage()) . "&type=error");
    exit();
}

$conn->close();
?>