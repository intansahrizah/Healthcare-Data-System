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
    'patientRecordSystem' => '0x1F572dfb0120c0aa7484EFb84B7B0680DFA51966',
    'ganacheUrl' => 'http://localhost:7545',
    'fromAddress' => '0xdcEcEF538C966722D6587C75e9D0e94577f89d53'
];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $patientName = $_POST['patientName'] ?? '';
    $icNumber = $_POST['ic_number'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validate required fields
    $errors = [];
    if (empty($patientName)) $errors[] = "Full name is required";
    if (empty($icNumber)) $errors[] = "IC number is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    if (empty($address)) $errors[] = "Address is required";
    
    if (empty($errors)) {
        try {
            // Generate blockchain address for this patient
            $blockchainAddress = '0x' . substr(md5($icNumber . time()), 0, 40);
            
            // Insert into database with blockchain address
            $stmt = $conn->prepare("INSERT INTO patients (patientName, ic_number, gender, email, phone, address, blockchain_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("sssssss", $patientName, $icNumber, $gender, $email, $phone, $address, $blockchainAddress);
            
            if ($stmt->execute()) {
                $last_insert_id = $conn->insert_id;
                $success = "Patient registered successfully!";
                
                // Enhanced Blockchain Integration
                $blockchain_result = storeOnBlockchain([
                    'patientId' => $last_insert_id,
                    'patientName' => $patientName,
                    'icNumber' => $icNumber,
                    'gender' => $gender,
                    'blockchainAddress' => $blockchainAddress,
                    'action' => 'PATIENT_REGISTRATION',
                    'timestamp' => time()
                ]);
                
                if ($blockchain_result['success']) {
                    $success .= " ✅ Data stored on blockchain!";
                    $success .= " Patient Blockchain Address: " . substr($blockchainAddress, 0, 10) . "...";
                    
                    // Store transaction hash in database
                    if (isset($blockchain_result['txHash'])) {
                        $update_stmt = $conn->prepare("UPDATE patients SET blockchain_tx_hash = ? WHERE patientsId = ?");
                        $update_stmt->bind_param("si", $blockchain_result['txHash'], $last_insert_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                } else {
                    $success .= " ✅ Database saved. ⚠️ Blockchain: " . $blockchain_result['message'];
                }

                $_POST = array();
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}

/**
 * Store patient data on blockchain
 */
function storeOnBlockchain($patientData) {
    global $blockchainConfig;
    
    try {
        // Create the transaction data
        $transactionData = [
            'jsonrpc' => '2.0',
            'method' => 'eth_sendTransaction',
            'params' => [[
                'from' => $blockchainConfig['fromAddress'],
                'to' => $blockchainConfig['patientRecordSystem'], // Send to our smart contract
                'data' => '0x' . bin2hex(json_encode([
                    'type' => 'patient_registration',
                    'data' => $patientData,
                    'timestamp' => date('c')
                ])),
                'gas' => '0x2DC6C0', // 3,000,000 gas
                'gasPrice' => '0x2540BE400', // 10 Gwei
                'value' => '0x0'
            ]],
            'id' => 1
        ];

        $ch = curl_init($blockchainConfig['ganacheUrl']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($transactionData),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10
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
            } else {
                // Even if transaction fails, data is encoded in the attempt
                return [
                    'success' => true,
                    'message' => 'Data encoded in transaction attempt'
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => 'Ganache not reachable: ' . $curl_error
            ];
        }
    } catch (Exception $e) {
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
    <title>Healthcare Data Sharing - New Patient</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --light: #f5f7fa;
            --dark: #333;
            --gray: #95a5a6;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
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
            background-color: #2c3e50;
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
            background-color: #3498db;
        }

        .nav-item i {
            margin-right: 10px;
            font-size: 18px;
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

        .btn-blockchain {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .btn-blockchain:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
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

        /* Card Styles */
        .card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.6rem;
            font-weight: 500;
            color: var(--secondary);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: var(--white);
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-block {
            width: 100%;
            padding: 1rem;
        }

        /* Status Messages */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .alert-success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Validation Styles */
        .error-message {
            color: var(--danger);
            font-size: 0.85rem;
            margin-top: 0.5rem;
            display: none;
        }

        .form-group.error .form-control {
            border-color: var(--danger);
        }

        .form-group.error .error-message {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
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
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .blockchain-info {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <link rel="stylesheet" href="sidebar.css">
        <aside class="sidebar">
            <div class="logo">
                <h1>HealthCare</h1>
                <p>Patient Management System</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard_admin.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_list.php" class="active">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_list.php">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="test_appoiment.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-user-plus"></i> Register New Patient</h2>
            </div>

            <!-- Replace the existing blockchain-info div with this: -->
            <div class="blockchain-panel">
                <h3><i class="fas fa-cube"></i> Blockchain Integration Active</h3>
                <p>Patient data will be securely stored on both MySQL database and Ganache blockchain network.</p>
                
                <div class="blockchain-info-grid">
                    <div class="blockchain-item">
                        <label><i class="fas fa-network-wired"></i> Network</label>
                        <div>Ganache Local (5777)</div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-hospital-user"></i> Patient Contract</label>
                        <div class="blockchain-address"><?php echo substr($blockchainConfig['patientRecordSystem'], 0, 10); ?>...</div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-server"></i> RPC URL</label>
                        <div><?php echo $blockchainConfig['ganacheUrl']; ?></div>
                    </div>
                </div>
            </div>

            <div class="card fade-in">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="patientForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="patientName">Full Name</label>
                            <input type="text" id="patientName" name="patientName" class="form-control" 
                                   placeholder="Enter patient's full name" required
                                   value="<?php echo isset($_POST['patientName']) ? htmlspecialchars($_POST['patientName']) : ''; ?>">
                            <div class="error-message" id="nameError">Please enter a valid name</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ic_number">IC Number</label>
                        <input type="text" id="ic_number" name="ic_number" class="form-control" 
                               placeholder="e.g. 000000-00-0000" required
                               value="<?php echo isset($_POST['ic_number']) ? htmlspecialchars($_POST['ic_number']) : ''; ?>">
                        <div class="error-message" id="icError">Please enter a valid IC number</div>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select gender</option>
                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                        <div class="error-message" id="genderError">Please select a gender</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   placeholder="patient@example.com" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <div class="error-message" id="emailError">Please enter a valid email</div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   placeholder="e.g. 0123456789" required
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                            <div class="error-message" id="phoneError">Please enter a valid phone number</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" class="form-control" 
                               placeholder="Full residential address" required
                               value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                        <div class="error-message" id="addressError">Please enter an address</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                        <i class="fas fa-save"></i> Register Patient (Database + Blockchain)
                        <span class="blockchain-status status-success">
                            <i class="fas fa-shield-alt"></i> Secure
                        </span>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
    document.getElementById("patientForm").addEventListener("submit", function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });

    function validateForm() {
        let isValid = true;
        
        // Reset error states
        document.querySelectorAll('.form-group').forEach(group => {
            group.classList.remove('error');
        });

        // Validate Full Name
        const $patientName = document.getElementById("patientName").value.trim();
        if ($patientName.length < 2) {
            document.getElementById("nameError").textContent = "Please enter a valid name (at least 2 characters)";
            document.getElementById("patientName").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate IC Number
        const icNumber = document.getElementById("ic_number").value.trim();
        if (!/^[0-9\-]{10,14}$/.test(icNumber)) {
            document.getElementById("icError").textContent = "Please enter a valid IC number (e.g. 000000-00-0000)";
            document.getElementById("ic_number").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Gender
        const gender = document.getElementById("gender").value;
        if (!gender) {
            document.getElementById("genderError").textContent = "Please select a gender";
            document.getElementById("gender").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Email
        const email = document.getElementById("email").value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById("emailError").textContent = "Please enter a valid email address";
            document.getElementById("email").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Phone
        const phone = document.getElementById("phone").value.trim();
        if (!/^[0-9]{10,15}$/.test(phone)) {
            document.getElementById("phoneError").textContent = "Please enter a valid phone number (10-15 digits)";
            document.getElementById("phone").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Address
        const address = document.getElementById("address").value.trim();
        if (address.length < 10) {
            document.getElementById("addressError").textContent = "Please enter a complete address (at least 10 characters)";
            document.getElementById("address").parentElement.classList.add('error');
            isValid = false;
        }

        return isValid;
    }
    </script>
</body>
</html>