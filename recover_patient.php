<?php
// recover_patient.php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Include blockchain backup helper
require_once 'blockchain_backup.php';

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize blockchain backup
$blockchainBackup = new BlockchainBackupHelper();

// Handle recovery request
if (isset($_POST['recover_patient'])) {
    $auditId = intval($_POST['audit_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Get patient data from audit table
        $audit_sql = "SELECT * FROM deleted_patients_audit WHERE id = ?";
        $audit_stmt = $conn->prepare($audit_sql);
        $audit_stmt->bind_param("i", $auditId);
        $audit_stmt->execute();
        $audit_data = $audit_stmt->get_result()->fetch_assoc();
        $audit_stmt->close();
        
        if (!$audit_data) {
            throw new Exception("Audit record not found.");
        }
        
        // 2. Verify blockchain backup exists
        $blockchainBackupData = $blockchainBackup->findBackupByPatientId($audit_data['patientsId']);
        if (!$blockchainBackupData) {
            throw new Exception("Blockchain backup not found for this patient.");
        }
        
        // 3. Check if patient already exists
        $check_sql = "SELECT patientsId FROM patients WHERE patientsId = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $audit_data['patientsId']);
        $check_stmt->execute();
        $existing_patient = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if ($existing_patient) {
            throw new Exception("Patient with ID " . $audit_data['patientsId'] . " already exists.");
        }
        
        // 4. Restore patient to main table
        $restore_sql = "INSERT INTO patients 
                       (patientsId, patientName, ic_number, gender, email, phone, address, blockchain_address) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $restore_stmt = $conn->prepare($restore_sql);
        $restore_stmt->bind_param("isssssss",
            $audit_data['patientsId'],
            $audit_data['patientName'],
            $audit_data['ic_number'],
            $audit_data['gender'],
            $audit_data['email'],
            $audit_data['phone'],
            $audit_data['address'],
            $audit_data['blockchain_address']
        );
        
        if (!$restore_stmt->execute()) {
            throw new Exception("Failed to restore patient: " . $restore_stmt->error);
        }
        $restore_stmt->close();
        
        // 5. Remove from audit table (optional - or keep for history)
        // $delete_audit_sql = "DELETE FROM deleted_patients_audit WHERE id = ?";
        // $delete_audit_stmt = $conn->prepare($delete_audit_sql);
        // $delete_audit_stmt->bind_param("i", $auditId);
        // $delete_audit_stmt->execute();
        // $delete_audit_stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = "Patient '" . $audit_data['patientName'] . "' recovered successfully from blockchain backup!";
        $_SESSION['message_type'] = 'success';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['message'] = "Recovery failed: " . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    header("Location: patient_archive.php");
    exit();
}

// Get all deleted patients for display
$deleted_patients_sql = "SELECT * FROM deleted_patients_audit ORDER BY deleted_at DESC";
$deleted_patients_result = $conn->query($deleted_patients_sql);
$deleted_patients = [];

if ($deleted_patients_result && $deleted_patients_result->num_rows > 0) {
    while($row = $deleted_patients_result->fetch_assoc()) {
        // Check if blockchain backup exists
        $row['blockchain_backup_exists'] = $blockchainBackup->findBackupByPatientId($row['patientsId']) !== null;
        $deleted_patients[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Deleted Patients - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Add the same styles from patient_list.php */
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
            --error: #e74c3c;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--secondary);
            color: white;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            color: white;
        }
        
        .btn-success {
            background-color: var(--success);
        }
        
        .btn-warning {
            background-color: var(--warning);
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .blockchain-badge {
            background-color: #8e44ad;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-undo"></i> Recover Deleted Patients</h1>
            <a href="patient_list.php" class="btn" style="background-color: var(--primary);">
                <i class="fas fa-arrow-left"></i> Back to Patient List
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>IC Number</th>
                        <th>Email</th>
                        <th>Deleted On</th>
                        <th>Blockchain Backup</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($deleted_patients) > 0): ?>
                        <?php foreach ($deleted_patients as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['patientsId']); ?></td>
                                <td><?php echo htmlspecialchars($patient['patientName']); ?></td>
                                <td><?php echo htmlspecialchars($patient['ic_number']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['deleted_at']); ?></td>
                                <td>
                                    <?php if ($patient['blockchain_backup_exists']): ?>
                                        <span class="blockchain-badge">
                                            <i class="fas fa-check-circle"></i> Available
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--error);">
                                            <i class="fas fa-times-circle"></i> Not Found
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patient['blockchain_backup_exists']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="audit_id" value="<?php echo $patient['id']; ?>">
                                            <button type="submit" name="recover_patient" class="btn btn-success" 
                                                    onclick="return confirm('Are you sure you want to recover <?php echo addslashes($patient['patientName']); ?>?')">
                                                <i class="fas fa-undo"></i> Recover
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn" style="background-color: #95a5a6;" disabled>
                                            <i class="fas fa-ban"></i> Cannot Recover
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-archive" style="font-size: 3rem; color: #95a5a6; margin-bottom: 1rem;"></i>
                                <h3>No Deleted Patients Found</h3>
                                <p>There are no patients in the archive.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>