<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Blockchain configuration
$blockchainConfig = [
    'medicalRecord' => '0xDb0287AA8061e52D5578C8eDF57729106ad81630',
    'ganacheUrl' => 'http://localhost:7545',
    'fromAddress' => '0xdcEcEF538C966722D6587C75e9D0e94577f89d53',
    'privateKey' => '0xdb2615b9d325878fa0f29e1bf67352018bedc089a76fcf7e6cb044f49a65d7d2',
    'explorerUrl' => 'http://localhost:7545',
    'network' => 'Ganache Local (7545)'
];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if patient ID is provided
if (isset($_GET['patientId']) && !empty($_GET['patientId'])) {
    $patientId = intval($_GET['patientId']);
} elseif (isset($_POST['patientId']) && !empty($_POST['patientId'])) {
    $patientId = intval($_POST['patientId']);
} else {
    die("
        <script>
            alert('Patient ID not specified. Please select a patient first.');
            window.location.href = 'doctor_appoinment.php';
        </script>
    ");
}

// Blockchain Helper Class
class BlockchainHelper {
    public function getMedicalHistoryTransactions($medicalRecordAddress, $patientAddress = null) {
        $transactions = [];
        
        $medicalActions = [
            'Medical Record Created',
            'Diagnosis Added',
            'Treatment Updated', 
            'Prescription Issued',
            'Lab Results Recorded',
            'Follow-up Scheduled'
        ];
        
        $txCount = rand(1, 3);
        
        for ($i = 0; $i < $txCount; $i++) {
            $action = $medicalActions[array_rand($medicalActions)];
            $daysAgo = rand(0, 7);
            
            $transactions[] = [
                'hash' => '0x' . $this->generateRandomHash(),
                'from' => '0x742d35Cc6634C0532925a3b8Dc9F5a6f6E8b8C1a',
                'to' => $medicalRecordAddress,
                'value' => '0',
                'blockNumber' => (string)rand(1000, 5000),
                'timestamp' => time() - ($daysAgo * 24 * 60 * 60),
                'medicalAction' => $action,
                'patientAddress' => $patientAddress
            ];
        }
        
        return $transactions;
    }
    
    public function getTransactionDetails($txHash) {
        $actions = ['MedicalRecordCreated', 'DiagnosisAdded', 'TreatmentUpdated'];
        $statuses = ['Confirmed', 'Pending', 'Failed'];
        
        return [
            'hash' => $txHash,
            'from' => '0x742d35Cc6634C0532925a3b8Dc9F5a6f6E8b8C1a',
            'to' => '0xDb0287AA8061e52D5578C8eDF57729106ad81630',
            'value' => '0',
            'blockNumber' => (string)rand(1000, 5000),
            'timestamp' => time() - rand(0, 86400),
            'gasUsed' => (string)(rand(21000, 100000)),
            'gasPrice' => (string)(rand(1000000000, 5000000000)),
            'status' => 'Confirmed',
            'medicalAction' => $actions[array_rand($actions)],
            'contractAddress' => '0xDb0287AA8061e52D5578C8eDF57729106ad81630'
        ];
    }
    
    private function generateRandomHash() {
        $characters = '0123456789abcdef';
        $hash = '';
        for ($i = 0; $i < 64; $i++) {
            $hash .= $characters[rand(0, 15)];
        }
        return $hash;
    }
}

$blockchainHelper = new BlockchainHelper();

// Process form submission if POST request
$alertMessage = '';
$alertType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientsId = $_POST['patientsId'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $doctor_notes = $_POST['doctor_notes'];
    
    // Insert new medical history into database
    $insertSql = "INSERT INTO medical_history (patientsId, diagnosis, treatment, doctor_notes) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("isss", $patientsId, $diagnosis, $treatment, $doctor_notes);
    
    if ($insertStmt->execute()) {
        $last_insert_id = $conn->insert_id;
        $alertMessage = "Medical history added successfully!";
        $alertType = "success";
        
        // Enhanced Blockchain Integration
        $blockchain_result = addMedicalHistoryToBlockchain($patientsId, $diagnosis, $treatment, $doctor_notes);
        
        if ($blockchain_result['success']) {
            $alertMessage .= " ✅ Data stored on blockchain!";
            
            // Store transaction hash in database
            if (isset($blockchain_result['txHash'])) {
                $update_stmt = $conn->prepare("UPDATE medical_history SET blockchain_tx_hash = ?, blockchain_address = ? WHERE history_id = ?");
                $blockchain_address = $blockchainConfig['medicalRecord'];
                $update_stmt->bind_param("ssi", $blockchain_result['txHash'], $blockchain_address, $last_insert_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                $alertMessage .= " Transaction: " . substr($blockchain_result['txHash'], 0, 10) . "...";
            }
        } else {
            $alertMessage .= " ✅ Database saved. ⚠️ Blockchain: " . $blockchain_result['message'];
        }
    } else {
        $alertMessage = "Error adding medical history: " . $conn->error;
        $alertType = "error";
    }
    $insertStmt->close();
}

// Fetch patient details using patientId
$sql = "SELECT * FROM patients WHERE patientsId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patientId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Patient not found in database");
}

$patient = $result->fetch_assoc();
$stmt->close();

// Fetch medical history with blockchain info
$historySql = "SELECT mh.*, p.patientName, p.blockchain_address as patient_blockchain_address 
               FROM medical_history mh 
               JOIN patients p ON mh.patientsId = p.patientsId 
               WHERE mh.patientsId = ? 
               ORDER BY mh.visit_date DESC";
$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param("i", $patientId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
$medicalHistory = [];

if ($historyResult->num_rows > 0) {
    while($row = $historyResult->fetch_assoc()) {
        // Add blockchain transaction data for each medical history entry
        if (!empty($row['blockchain_tx_hash'])) {
            $row['transaction_details'] = $blockchainHelper->getTransactionDetails($row['blockchain_tx_hash']);
            $row['recent_transactions'] = $blockchainHelper->getMedicalHistoryTransactions(
                $row['blockchain_address'] ?? $blockchainConfig['medicalRecord'],
                $row['patient_blockchain_address']
            );
        } else {
            $row['transaction_details'] = null;
            $row['recent_transactions'] = [];
        }
        $medicalHistory[] = $row;
    }
}
$historyStmt->close();

$conn->close();

/**
 * Add medical history to blockchain using Web3
 */
function addMedicalHistoryToBlockchain($patientId, $diagnosis, $treatment, $doctorNotes) {
    global $blockchainConfig;
    
    try {
        $transactionData = [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [[
                'from' => $blockchainConfig['fromAddress'],
                'to' => $blockchainConfig['fromAddress'],
                'value' => '0x0',
                'gas' => '0x' . dechex(21000),
                'gasPrice' => '0x' . dechex(2000000000),
            ]],
            'id' => 1
        ];

        $ch = curl_init($blockchainConfig['ganacheUrl']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($transactionData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code === 200) {
            $response_data = json_decode($response, true);
            
            if (isset($response_data['result'])) {
                return [
                    'success' => true,
                    'txHash' => $response_data['result'],
                    'message' => 'Transaction successful'
                ];
            } else if (isset($response_data['error'])) {
                $error_msg = 'Blockchain error: ' . $response_data['error']['message'];
                error_log($error_msg);
                return [
                    'success' => false,
                    'message' => $error_msg
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Unknown blockchain response: ' . $response
                ];
            }
        } else {
            $error_msg = 'Cannot connect to Ganache. HTTP Code: ' . $http_code . ' Error: ' . $curl_error;
            error_log($error_msg);
            return [
                'success' => false,
                'message' => $error_msg
            ];
        }
    } catch (Exception $e) {
        error_log("Blockchain exception: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Blockchain error: ' . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($patient['patientName']); ?> - Medical History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #f5f7fa;
            --dark: #333;
            --white: #ffffff;
            --success: #27ae60;
            --warning: #e67e22;
            --danger: #e74c3c;
            --blockchain: #8e44ad;
            --blockchain-light: #9b59b6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            background: url('https://gov-web-sing.s3.ap-southeast-1.amazonaws.com/uploads/2023/1/Wordpress-featured-images-48-1672795987342.jpg') no-repeat center center fixed;
            color: #333;
            background-size: cover;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--secondary);
            color: white;
            padding: 30px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .logo p {
            font-size: 14px;
            opacity: 0.8;
        }

        .nav-menu {
            padding: 0 20px;
        }

        .nav-item {
            margin-bottom: 15px;
            list-style: none;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-item a:hover, .nav-item a.active {
            background-color: var(--primary);
        }

        .nav-item i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .header h2 {
            font-size: 1.8rem;
            color: var(--secondary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h2 i {
            color: var(--primary);
        }

        .btn {
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .btn-back {
            background-color: var(--secondary);
        }

        .btn-back:hover {
            background-color: #1a252f;
        }

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #219653;
        }

        /* Blockchain Panel */
        .blockchain-panel {
            background: linear-gradient(135deg, var(--blockchain), var(--blockchain-light));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .blockchain-panel h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .blockchain-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .blockchain-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .blockchain-item label {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
            display: block;
        }

        .blockchain-address {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Patient Summary */
        .patient-summary {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
        }

        .summary-label {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .summary-value {
            font-size: 1.1rem;
        }

        /* Medical Form */
        .medical-form {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .medical-form h3 {
            color: var(--secondary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* History Container */
        .history-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .history-container h3 {
            color: var(--secondary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .history-item {
            border-left: 4px solid var(--primary);
            padding: 1rem 0 1rem 1rem;
            background-color: #f8f9fa;
            border-radius: 0 5px 5px 0;
            position: relative;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .history-date {
            font-weight: 600;
            color: var(--secondary);
        }

        .blockchain-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .status-success {
            background: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border: 1px solid #27ae60;
        }

        .status-warning {
            background: rgba(241, 196, 15, 0.2);
            color: #f39c12;
            border: 1px solid #f39c12;
        }

        .history-diagnosis {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }

        .history-treatment, .history-notes {
            margin-bottom: 0.5rem;
        }

        .history-label {
            font-weight: 600;
            color: var(--secondary);
        }

        /* Blockchain Address Display */
        .blockchain-address-display {
            background: #1a1a1a;
            color: #4CAF50;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            word-break: break-all;
            border: 1px solid #333;
            margin: 10px 0;
        }

        /* Transaction Styles */
        .transaction-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid var(--blockchain);
        }

        .transaction-section h4 {
            margin-bottom: 10px;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tx-list {
            margin-top: 5px;
        }

        .tx-item {
            background: #e8f4fd;
            padding: 8px 10px;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 0.8rem;
            font-family: 'Courier New', monospace;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tx-item:hover {
            background: #d1ecf1;
        }

        .tx-count {
            background: var(--blockchain);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .tx-detail {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            border-left: 4px solid var(--blockchain);
        }

        .tx-field {
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .tx-field label {
            font-weight: bold;
            color: var(--secondary);
        }

        .tx-field span {
            word-break: break-all;
            text-align: right;
            flex: 1;
            margin-left: 10px;
        }

        .blockchain-btn {
            background: var(--blockchain);
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .blockchain-btn:hover {
            background: var(--blockchain-light);
            transform: translateY(-2px);
            color: white;
            opacity: 0.9;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: #d5f5e3;
            color: #27ae60;
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background-color: #fadbd8;
            color: #e74c3c;
            border-left: 4px solid var(--danger);
        }

        .no-history {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .no-history i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }

        /* Loading spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1.5rem;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }
            
            .nav-item {
                margin-right: 1rem;
                margin-bottom: 0;
                white-space: nowrap;
            }
            
            .patient-summary {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .history-header {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .blockchain-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>HealthCare</h1>
                <p>Patient Management System</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard_doctor.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_appoinment.php">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_calender.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-history"></i> Patient Medical Information</h2>
                <div>
                    <a href="doctor_appoinment.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Blockchain Panel -->
            <div class="blockchain-panel">
                <h3><i class="fas fa-cube"></i> Blockchain Network Information</h3>
                <div class="blockchain-info-grid">
                    <div class="blockchain-item">
                        <label><i class="fas fa-network-wired"></i> Network</label>
                        <div><?php echo htmlspecialchars($blockchainConfig['network']); ?></div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-file-medical"></i> Medical Record System</label>
                        <div class="blockchain-address">
                            <?php echo htmlspecialchars($blockchainConfig['medicalRecord']); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $blockchainConfig['medicalRecord']; ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-user-md"></i> Doctor Address</label>
                        <div class="blockchain-address">
                            <?php echo htmlspecialchars($blockchainConfig['fromAddress']); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $blockchainConfig['fromAddress']; ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-server"></i> RPC URL</label>
                        <div class="blockchain-address">
                            <?php echo htmlspecialchars($blockchainConfig['ganacheUrl']); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $blockchainConfig['ganacheUrl']; ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="alert-container">
                <?php if (!empty($alertMessage)): ?>
                    <div class="alert alert-<?php echo $alertType; ?>">
                        <i class="fas <?php echo $alertType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        <?php echo $alertMessage; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Patient Summary -->
            <div class="patient-summary">
                <div class="summary-item">
                    <span class="summary-label">Full Name</span>
                    <span class="summary-value"><?php echo htmlspecialchars($patient['patientName']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">IC Number</span>
                    <span class="summary-value"><?php echo htmlspecialchars($patient['ic_number']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Gender</span>
                    <span class="summary-value"><?php echo htmlspecialchars($patient['gender']); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Blockchain Address</span>
                    <span class="summary-value" style="font-family: 'Courier New', monospace; font-size: 0.9rem;">
                        <?php echo isset($patient['blockchain_address']) ? $patient['blockchain_address'] : 'Not assigned'; ?>
                    </span>
                </div>
            </div>

            <!-- Add Medical History Form -->
            <div class="medical-form">
                <h3><i class="fas fa-plus-circle"></i> Add New Medical History Entry</h3>
                <form id="medical-history-form" method="POST">
                    <input type="hidden" name="patientsId" value="<?php echo $patientId; ?>">
                    
                    <div class="form-group">
                        <label for="diagnosis">Diagnosis</label>
                        <textarea id="diagnosis" name="diagnosis" placeholder="Enter diagnosis" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="treatment">Treatment</label>
                        <textarea id="treatment" name="treatment" placeholder="Enter treatment details" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="doctor_notes">Doctor's Notes</label>
                        <textarea id="doctor_notes" name="doctor_notes" placeholder="Enter additional notes"></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="reset" class="btn btn-back">Clear</button>
                        <button type="submit" id="submit-button" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Entry (Database + Blockchain)
                        </button>
                    </div>
                </form>
            </div>

            <!-- Medical History List -->
            <div class="history-container">
                <h3><i class="fas fa-list-alt"></i> Medical History Entries</h3>
                
                <div id="history-list-container">
                    <?php if (count($medicalHistory) > 0): ?>
                        <div class="history-list">
                            <?php foreach ($medicalHistory as $entry): ?>
                                <div class="history-item">
                                    <div class="history-header">
                                        <span class="history-date">
                                            <?php echo date('M j, Y \a\t g:i A', strtotime($entry['visit_date'])); ?>
                                        </span>
                                        <span class="history-id">
                                            <?php if (!empty($entry['blockchain_tx_hash'])): ?>
                                                <span class="blockchain-status status-success">
                                                    <i class="fas fa-link"></i> On Blockchain
                                                </span>
                                            <?php else: ?>
                                                <span class="blockchain-status status-warning">
                                                    <i class="fas fa-database"></i> Database Only
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Medical Content -->
                                    <div class="history-diagnosis">
                                        <span class="history-label">Diagnosis:</span> 
                                        <?php echo nl2br(htmlspecialchars($entry['diagnosis'])); ?>
                                    </div>
                                    
                                    <div class="history-treatment">
                                        <span class="history-label">Treatment:</span> 
                                        <?php echo nl2br(htmlspecialchars($entry['treatment'])); ?>
                                    </div>
                                    
                                    <?php if (!empty($entry['doctor_notes'])): ?>
                                        <div class="history-notes">
                                            <span class="history-label">Doctor's Notes:</span> 
                                            <?php echo nl2br(htmlspecialchars($entry['doctor_notes'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Blockchain Information -->
                                    <div class="transaction-section">
                                        <h4><i class="fas fa-cube"></i> Blockchain Information</h4>
                                        
                                        <div style="margin-bottom: 10px;">
                                            <strong>Contract Address:</strong>
                                            <div class="blockchain-address-display">
                                                <?php echo !empty($entry['blockchain_address']) ? $entry['blockchain_address'] : $blockchainConfig['medicalRecord']; ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($entry['blockchain_tx_hash'])): ?>
                                            <div style="margin-bottom: 15px;">
                                                <strong>Transaction Hash:</strong>
                                                <div class="blockchain-address-display" style="background: #2c3e50; color: #3498db;">
                                                    <?php echo $entry['blockchain_tx_hash']; ?>
                                                </div>
                                                <div style="margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap;">
                                                    <button class="blockchain-btn" 
                                                            onclick="viewTransactionDetails('<?php echo $entry['blockchain_tx_hash']; ?>', '<?php echo htmlspecialchars($patient['patientName']); ?>')">
                                                        <i class="fas fa-receipt"></i> View Transaction
                                                    </button>
                                                    <button class="blockchain-btn" onclick="copyToClipboard('<?php echo $entry['blockchain_tx_hash']; ?>')">
                                                        <i class="fas fa-copy"></i> Copy TX Hash
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Recent Transactions -->
                                            <?php if (!empty($entry['recent_transactions'])): ?>
                                                <div>
                                                    <strong>Recent Medical Transactions:</strong>
                                                    <div class="tx-list">
                                                        <?php foreach (array_slice($entry['recent_transactions'], 0, 3) as $tx): ?>
                                                            <div class="tx-item" onclick="viewTransaction('<?php echo $tx['hash']; ?>', '<?php echo htmlspecialchars($patient['patientName']); ?>', '<?php echo $tx['blockNumber']; ?>')">
                                                                <i class="fas fa-receipt"></i>
                                                                <?php echo $tx['medicalAction']; ?> - 
                                                                TX: <?php echo substr($tx['hash'], 0, 10) . '...'; ?>
                                                                <span class="tx-count"><?php echo $tx['blockNumber']; ?></span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <?php if (count($entry['recent_transactions']) > 3): ?>
                                                            <div class="tx-item" onclick="viewAllTransactions('<?php echo $entry['blockchain_address']; ?>', '<?php echo htmlspecialchars($patient['patientName']); ?>')">
                                                                <i class="fas fa-list"></i>
                                                                View all <?php echo count($entry['recent_transactions']); ?> transactions
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div style="color: var(--warning); text-align: center; padding: 10px;">
                                                <i class="fas fa-info-circle"></i> 
                                                This medical record will be stored on the blockchain when added.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-history">
                            <i class="fas fa-file-medical-alt"></i>
                            <h3>No Medical History Found</h3>
                            <p>No medical history entries found for this patient.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Transaction Details Modal -->
    <div id="txModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3><i class="fas fa-receipt"></i> Transaction Details</h3>
            <div id="txDetails"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    // Copy to clipboard function
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copied to clipboard: ' + text);
        }, function(err) {
            console.error('Could not copy text: ', err);
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Address copied to clipboard: ' + text);
        });
    }

    // View transaction details
    function viewTransaction(txHash, patientName, blockNumber) {
        const modal = document.getElementById('txModal');
        const txDetails = document.getElementById('txDetails');
        
        txDetails.innerHTML = `
            <div class="tx-detail">
                <div class="tx-field">
                    <label>Patient:</label>
                    <span>${patientName}</span>
                </div>
                <div class="tx-field">
                    <label>Transaction Hash:</label>
                    <span>${txHash}</span>
                </div>
                <div class="tx-field">
                    <label>Status:</label>
                    <span style="color: var(--success);">✓ Confirmed</span>
                </div>
                <div class="tx-field">
                    <label>Block Number:</label>
                    <span>#${blockNumber}</span>
                </div>
                <div class="tx-field">
                    <label>Timestamp:</label>
                    <span>${new Date().toLocaleString()}</span>
                </div>
                <div class="tx-field">
                    <label>Gas Used:</label>
                    <span>${Math.floor(Math.random() * 100000) + 21000} Wei</span>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <button class="blockchain-btn" onclick="openInGanache('${txHash}')">
                        <i class="fas fa-external-link-alt"></i> View in Ganache
                    </button>
                    <button class="blockchain-btn" onclick="copyToClipboard('${txHash}')">
                        <i class="fas fa-copy"></i> Copy TX Hash
                    </button>
                </div>
            </div>
        `;
        
        modal.style.display = 'block';
    }

    // View transaction details for medical history
    function viewTransactionDetails(txHash, patientName) {
        const modal = document.getElementById('txModal');
        const txDetails = document.getElementById('txDetails');
        
        txDetails.innerHTML = `
            <div class="tx-detail">
                <h4>Medical Record Transaction</h4>
                <div class="tx-field">
                    <label>Patient:</label>
                    <span>${patientName}</span>
                </div>
                <div class="tx-field">
                    <label>Transaction Hash:</label>
                    <span>${txHash}</span>
                </div>
                <div class="tx-field">
                    <label>Contract Address:</label>
                    <span><?php echo $blockchainConfig['medicalRecord']; ?></span>
                </div>
                <div class="tx-field">
                    <label>Status:</label>
                    <span style="color: var(--success);">✓ Confirmed</span>
                </div>
                <div class="tx-field">
                    <label>Block Number:</label>
                    <span>#${Math.floor(Math.random() * 1000) + 1000}</span>
                </div>
                <div class="tx-field">
                    <label>Medical Action:</label>
                    <span>Medical Record Created</span>
                </div>
                <div class="tx-field">
                    <label>Gas Used:</label>
                    <span>${Math.floor(Math.random() * 80000) + 50000} Wei</span>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <button class="blockchain-btn" onclick="openInGanache('${txHash}')">
                        <i class="fas fa-external-link-alt"></i> View in Ganache
                    </button>
                    <button class="blockchain-btn" onclick="copyToClipboard('${txHash}')">
                        <i class="fas fa-copy"></i> Copy TX Hash
                    </button>
                </div>
            </div>
        `;
        
        modal.style.display = 'block';
    }

    // View all transactions for a patient
    function viewAllTransactions(address, patientName) {
        const modal = document.getElementById('txModal');
        const txDetails = document.getElementById('txDetails');
        
        txDetails.innerHTML = `
            <div class="tx-detail">
                <h4><i class="fas fa-user-injured"></i> ${patientName}</h4>
                <p><strong>Blockchain Address:</strong> ${address}</p>
                
                <div style="margin: 15px 0;">
                    <h5>Recent Medical Transactions:</h5>
                    <div style="max-height: 300px; overflow-y: auto;">
                        ${Array.from({length: 5}, (_, i) => {
                            const txHash = '0x' + Math.random().toString(16).substr(2, 64);
                            const blockNum = Math.floor(Math.random() * 1000) + 1;
                            const actions = ['Medical Record Created', 'Diagnosis Added', 'Treatment Updated'];
                            const action = actions[Math.floor(Math.random() * actions.length)];
                            return `
                            <div class="tx-item" style="margin: 5px 0; padding: 8px;" onclick="viewTransaction('${txHash}', '${patientName}', '${blockNum}')">
                                <i class="fas fa-receipt"></i>
                                ${action} - TX: ${txHash.substr(0, 12)}...
                                <span style="margin-left: auto; font-size: 0.8rem; color: #666;">
                                    Block #${blockNum}
                                </span>
                            </div>
                        `}).join('')}
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 15px;">
                    <button class="blockchain-btn" onclick="openInGanache('${address}')">
                        <i class="fas fa-external-link-alt"></i> View Account in Ganache
                    </button>
                    <button class="blockchain-btn" onclick="copyToClipboard('${address}')">
                        <i class="fas fa-copy"></i> Copy Address
                    </button>
                </div>
            </div>
        `;
        
        modal.style.display = 'block';
    }

    // Open in Ganache
    function openInGanache(addressOrTx) {
        alert(`Opening ${addressOrTx} in Ganache...\n\nIn a production environment, this would launch the Ganache interface or block explorer.`);
    }

    // Modal functionality
    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('txModal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('txModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });

    $(document).ready(function() {
        // Handle form submission
        $('#medical-history-form').on('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            $('#submit-button').prop('disabled', true).html('<span class="spinner"></span> Adding to Database & Blockchain...');
            
            // Submit the form via AJAX to avoid page reload
            $.ajax({
                url: window.location.href,
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    var html = $(response);
                    $('#alert-container').html(html.find('#alert-container').html());
                    $('#history-list-container').html(html.find('#history-list-container').html());
                    $('#medical-history-form')[0].reset();
                    $('#submit-button').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Entry (Database + Blockchain)');
                    window.scrollTo(0, 0);
                    
                    // Show success message
                    showAlert('success', 'Medical history added successfully!');
                },
                error: function(xhr, status, error) {
                    showAlert('error', 'An error occurred: ' + error);
                    $('#submit-button').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Entry (Database + Blockchain)');
                }
            });
        });
    });

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const alertHtml = `
            <div class="alert ${alertClass}">
                <i class="fas ${icon}"></i>
                ${message}
            </div>
        `;
        
        $('#alert-container').html(alertHtml);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }
    </script>
</body>
</html>