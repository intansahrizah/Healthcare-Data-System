<?php
// patient_verify.php
$patient_id = $_GET['id'] ?? null;

if (!$patient_id) {
    echo "<div style='color: red; padding: 20px;'>❌ Error: No patient ID provided</div>";
    echo "<p>Usage: patient_verify.php?id=1001</p>";
    exit;
}

// Get blockchain data with error handling
$blockchain_url = "http://localhost:3000/api/patient/{$patient_id}";
$response = @file_get_contents($blockchain_url);

if ($response === FALSE) {
    echo "<div style='color: red; padding: 20px;'>❌ Error: Cannot connect to blockchain API</div>";
    echo "<p>Make sure the blockchain middleware is running on port 3000</p>";
    exit;
}

$blockchain_data = json_decode($response, true);

if (!$blockchain_data['success']) {
    echo "<div style='color: orange; padding: 20px;'>⚠️ Patient not found on blockchain</div>";
    echo "<p>Patient ID: {$patient_id} is not registered on the blockchain</p>";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Blockchain Verification - Patient <?php echo $patient_id; ?></title>
    <style>
        .verification-badge {
            border: 3px solid #4CAF50;
            padding: 30px;
            margin: 20px;
            border-radius: 15px;
            text-align: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        .verified { color: #4CAF50; font-size: 24px; font-weight: bold; }
        .block-hash { 
            font-family: 'Courier New', monospace; 
            background: #2c3e50; 
            color: white; 
            padding: 10px; 
            border-radius: 5px;
            word-break: break-all;
        }
        .patient-info { 
            background: white; 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="verification-badge">
        <div class="verified">✅ BLOCKCHAIN VERIFIED</div>
        <h2>Patient Medical Record</h2>
        <p>This record is permanently stored on the healthcare blockchain</p>
        
        <div class="patient-info">
            <p><strong>Patient ID:</strong> <?php echo $blockchain_data['patient']['patientsId']; ?></p>
            <p><strong>Name:</strong> <?php echo $blockchain_data['patient']['patientName']; ?></p>
            <p><strong>IC Number:</strong> <?php echo $blockchain_data['patient']['ic_number']; ?></p>
            <p><strong>Registered:</strong> <?php echo date('Y-m-d H:i:s', strtotime($blockchain_data['patient']['created_at'])); ?></p>
        </div>

        <div style="margin: 20px 0;">
            <h3>🔐 Blockchain Verification Hash</h3>
            <div class="block-hash"><?php echo $blockchain_data['patient']['blockHash']; ?></div>
            <p><small>This unique hash verifies the authenticity of this medical record</small></p>
        </div>

        <div style="background: #e8f5e8; padding: 15px; border-radius: 8px;">
            <h4>📊 Blockchain Statistics</h4>
            <p>Medical Records: <?php echo count($blockchain_data['patient']['medicalHistory']); ?> entries</p>
            <p>Appointments: <?php echo count($blockchain_data['patient']['appointments']); ?> scheduled</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="blockchain_dashboard.php" style="background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            View Full Blockchain Dashboard
        </a>
    </div>
</body>
</html>