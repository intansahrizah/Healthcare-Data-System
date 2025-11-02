<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Blockchain configuration - UPDATE THESE WITH YOUR ACTUAL GANACHE DETAILS
$blockchainConfig = [
    'medicalRecord' => '0xDb0287AA8061e52D5578C8eDF57729106ad81630', // Your medical contract address
    'ganacheUrl' => 'http://localhost:7545',
    'fromAddress' => '0xdcEcEF538C966722D6587C75e9D0e94577f89d53', // Your Ganache account
    'privateKey' => '0xdb2615b9d325878fa0f29e1bf67352018bedc089a76fcf7e6cb044f49a65d7d2', // Add your private key
    'explorerUrl' => 'http://localhost:7545'
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
        $medicalHistory[] = $row;
    }
}
$historyStmt->close();

$conn->close();

/**
 * Add medical history to blockchain using Web3
 */
/**
 * Add medical history to blockchain using Web3 - CORRECTED VERSION
 */
function addMedicalHistoryToBlockchain($patientId, $diagnosis, $treatment, $doctorNotes) {
    global $blockchainConfig;
    
    try {
        // First, let's try a simple transaction to test connectivity
        $transactionData = [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [[
                'from' => $blockchainConfig['fromAddress'],
                'to' => $blockchainConfig['fromAddress'], // Send to self for testing
                'value' => '0x0', // 0 ETH
                'gas' => '0x' . dechex(21000), // Standard gas for simple transfer
                'gasPrice' => '0x' . dechex(2000000000), // 2 gwei (much lower)
            ]],
            'id' => 1
        ];

        // Send transaction to Ganache
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
        
        // Debug: Log the response
        error_log("Ganache Response: " . $response);
        error_log("HTTP Code: " . $http_code);
        
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
/**
 * Alternative: Sign transaction with private key
 */
function addMedicalHistoryToBlockchainSigned($patientId, $diagnosis, $treatment, $doctorNotes) {
    global $blockchainConfig;
    
    try {
        // First, get the nonce
        $nonceData = [
            'jsonrpc' => '2.0',
            'method' => 'eth_getTransactionCount',
            'params' => [$blockchainConfig['fromAddress'], 'latest'],
            'id' => 1
        ];
        
        $ch = curl_init($blockchainConfig['ganacheUrl']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($nonceData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $nonceResponse = curl_exec($ch);
        $nonceData = json_decode($nonceResponse, true);
        curl_close($ch);
        
        if (!isset($nonceData['result'])) {
            return ['success' => false, 'message' => 'Could not get nonce'];
        }
        
        $nonce = $nonceData['result'];
        
        // Create transaction
        $transaction = [
            'nonce' => $nonce,
            'from' => $blockchainConfig['fromAddress'],
            'to' => $blockchainConfig['medicalRecord'],
            'value' => '0x0',
            'gas' => '0x' . dechex(100000),
            'gasPrice' => '0x' . dechex(2000000000),
            'data' => '0x' . bin2hex("MedicalRecord:" . $patientId)
        ];
        
        // For now, use eth_sendTransaction (Ganache allows unsigned for development)
        $transactionData = [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [$transaction],
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
        curl_close($ch);
        
        if ($http_code === 200) {
            $response_data = json_decode($response, true);
            if (isset($response_data['result'])) {
                return [
                    'success' => true,
                    'txHash' => $response_data['result'],
                    'message' => 'Transaction successful'
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Transaction failed'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
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
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .blockchain-address-display {
            background: #1a1a1a;
            color: #4CAF50;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin: 5px 0;
            word-break: break-all;
            border: 1px solid #333;
        }
        
        .blockchain-link {
            color: #4CAF50;
            text-decoration: none;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .blockchain-link:hover {
            text-decoration: underline;
            color: #45a049;
        }
        
        .contract-address {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid #4CAF50;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        /* Blockchain-specific styles */
        .blockchain-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        .blockchain-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
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

        /* Medical History Form */
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

        .medical-form h3 i {
            color: var(--primary);
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

        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Medical History List */
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

        .history-container h3 i {
            color: var(--primary);
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

        .history-id {
            color: #6c757d;
            font-size: 0.9rem;
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

        /* Blockchain Transaction Styles */
        .blockchain-transaction {
            background: rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-family: 'Courier New', monospace;
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .transaction-label {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.9rem;
        }

        .transaction-hash {
            font-size: 0.85rem;
            word-break: break-all;
            color: var(--secondary);
        }

        .transaction-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .transaction-link:hover {
            text-decoration: underline;
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
            
            .transaction-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
                <h2><i class="fas fa-history"></i> Patient Medical Information </h2>
                <div>
                    <a href="doctor_appoinment.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="patient_detail.php?patientId=<?php echo $patientId; ?>" class="btn">
                        <i class="fas fa-user"></i> Patient Details
                    </a>
                </div>
            </div>

            <!-- Enhanced Blockchain Panel -->
            <div class="blockchain-panel">
                <h3><i class="fas fa-cube"></i> Blockchain Medical Records</h3>
                <p>All medical records are permanently stored on the Ethereum blockchain via Ganache.</p>
                
                <div class="blockchain-info-grid">
                    <div class="blockchain-item">
                        <label><i class="fas fa-network-wired"></i> Network</label>
                        <div>Ganache Local (7545)</div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-file-medical"></i> Medical Contract</label>
                        <div class="blockchain-address"><?php echo $blockchainConfig['medicalRecord']; ?></div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-user-md"></i> Doctor Address</label>
                        <div class="blockchain-address"><?php echo $blockchainConfig['fromAddress']; ?></div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-user-injured"></i> Patient Blockchain ID</label>
                        <div class="blockchain-address"><?php echo isset($patient['blockchain_address']) ? $patient['blockchain_address'] : 'Not assigned'; ?></div>
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
                            <span class="blockchain-status status-success">
                                <i class="fas fa-shield-alt"></i> Secure
                            </span>
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
                                                <span class="blockchain-status status-success" style="margin-left: 10px;">
                                                    <i class="fas fa-link"></i> On Blockchain
                                                </span>
                                            <?php else: ?>
                                                <span class="blockchain-status status-warning" style="margin-left: 10px;">
                                                    <i class="fas fa-database"></i> Database Only
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Blockchain Address Display - Similar to your image -->
                                    <div class="blockchain-address-section" style="margin-bottom: 15px;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                                            <strong style="color: var(--secondary); font-size: 14px;">
                                                <i class="fas fa-cube"></i> BLOCKCHAIN ADDRESS:
                                            </strong>
                                            <?php if (!empty($entry['blockchain_tx_hash'])): ?>
                                                <span class="blockchain-status status-success" style="font-size: 12px;">
                                                    <i class="fas fa-check-circle"></i> VERIFIED
                                                </span>
                                            <?php else: ?>
                                                
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="blockchain-address-display" style="background: #1a1a1a; color: #4CAF50; padding: 12px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 13px; word-break: break-all; border: 1px solid #333; margin-bottom: 8px;">
                                            <?php if (!empty($entry['blockchain_address'])): ?>
                                                <?php echo $entry['blockchain_address']; ?>
                                            <?php else: ?>
                                                <?php echo $blockchainConfig['medicalRecord']; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($entry['blockchain_tx_hash'])): ?>
                                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                                <div style="font-size: 12px; color: #666;">
                                                    <strong>TX HASH:</strong> 
                                                    <span style="font-family: 'Courier New', monospace;">
                                                        <?php echo substr($entry['blockchain_tx_hash'], 0, 20) . '...' . substr($entry['blockchain_tx_hash'], -20); ?>
                                                    </span>
                                                </div>
                                                <a href="<?php echo $blockchainConfig['explorerUrl']; ?>" 
                                                target="_blank" 
                                                class="btn" 
                                                style="padding: 6px 12px; font-size: 12px; background: var(--primary);">
                                                    <i class="fas fa-external-link-alt"></i> View Transaction
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size: 12px; color: var(--warning);">
                                                <i class="fas fa-info-circle"></i> This record will be stored on blockchain when added
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
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
                    $('#submit-button').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Entry (Database + Blockchain) <span class="blockchain-status status-success"><i class="fas fa-shield-alt"></i> Secure</span>');
                    window.scrollTo(0, 0);
                },
                error: function(xhr, status, error) {
                    showAlert('error', 'An error occurred: ' + error);
                    $('#submit-button').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Entry (Database + Blockchain) <span class="blockchain-status status-success"><i class="fas fa-shield-alt"></i> Secure</span>');
                }
            });
        });
        
        function showAlert(type, message) {
            var alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            var alertHtml = '<div class="alert ' + alertClass + '">' +
                '<i class="fas ' + icon + '"></i> ' + message +
                '</div>';
            
            $('#alert-container').html(alertHtml);
            
            setTimeout(function() {
                $('#alert-container').empty();
            }, 5000);
        }
    });
    </script>
</body>
</html>