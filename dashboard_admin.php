<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login_page.php");
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle duty status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doctorId']) && isset($_POST['on_duty'])) {
    $doctorId = intval($_POST['doctorId']);
    $on_duty = intval($_POST['on_duty']);
    
    $update_sql = "UPDATE doctors SET on_duty = $on_duty WHERE doctorId = $doctorId";
    if ($conn->query($update_sql)) {
        $message = "Doctor status updated successfully!";
    } else {
        $error = "Error updating doctor status: " . $conn->error;
    }
}

// Get dashboard statistics
$total_patients = 0;
$today_appointments = 0;
$pending_appointments = 0;
$total_doctors = 0;
$patients_with_blockchain = 0;

// Total patients
$total_patients_sql = "SELECT COUNT(*) as total FROM patients";
$total_patients_result = $conn->query($total_patients_sql);
$total_patients = $total_patients_result->fetch_assoc()['total'];

// Today's appointments
$today = date('Y-m-d');
$today_appointments_sql = "SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = '$today'";
$today_appointments_result = $conn->query($today_appointments_sql);
$today_appointments = $today_appointments_result->fetch_assoc()['total'];

// Pending appointments (Scheduled status)
$pending_appointments_sql = "SELECT COUNT(*) as total FROM appointments WHERE status = 'Scheduled'";
$pending_appointments_result = $conn->query($pending_appointments_sql);
$pending_appointments = $pending_appointments_result->fetch_assoc()['total'];

// Total doctors
$total_doctors_sql = "SELECT COUNT(*) as total FROM doctors";
$total_doctors_result = $conn->query($total_doctors_sql);
$total_doctors = $total_doctors_result->fetch_assoc()['total'];

// Patients with blockchain addresses
$blockchain_patients_sql = "SELECT COUNT(*) as total FROM patients WHERE blockchain_address IS NOT NULL AND blockchain_address != ''";
$blockchain_patients_result = $conn->query($blockchain_patients_sql);
$patients_with_blockchain = $blockchain_patients_result->fetch_assoc()['total'];

// Get all recent patients (last 5)
$recent_patients = [];
$sql = "SELECT patientsId, patientName, email, phone, blockchain_address, created_at 
        FROM patients 
        ORDER BY created_at DESC 
        LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $recent_patients[] = $row;
}

// Get all recent activity from all doctors (appointments)
$recent_activity = [];
$sql = "SELECT a.*, p.patientName, d.doctorName 
        FROM appointments a 
        JOIN patients p ON a.patientsId = p.patientsId 
        JOIN doctors d ON a.doctorId = d.doctorId 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC 
        LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}

// Get all doctors for the doctors panel - FIXED QUERY (removed specialization column)
$doctors = [];
$sql = "SELECT doctorId, doctorName, on_duty FROM doctors ORDER BY on_duty DESC, doctorName LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

$conn->close();

// Blockchain config
$blockchainConfig = [
    'patientRecordSystem' => '0x1F572dfb0120c0aa7484EFb84B7B0680DFA51966',
    'medicalRecord' => '0xDb0287AA8061e52D5578C8eDF57729106ad81630',
    'network' => 'Ganache Local (5777)',
    'rpcUrl' => 'http://127.0.0.1:7545'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --warning: #f39c12;
            --light: #f5f7fa;
            --dark: #333;
            --gray: #95a5a6;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --blockchain: #8e44ad;
            --blockchain-light: #9b59b6;
            --info: #17a2b8;
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

        .container {
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
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .header h2 {
            font-size: 28px;
            color: #2c3e50;
            margin: 0;
        }

        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card.patients {
            border-left-color: var(--primary);
        }

        .stat-card.appointments {
            border-left-color: var(--success);
        }

        .stat-card.pending {
            border-left-color: var(--warning);
        }

        .stat-card.doctors {
            border-left-color: var(--info);
        }

        .stat-card.blockchain {
            border-left-color: var(--blockchain);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-card.patients .stat-icon {
            color: var(--primary);
        }

        .stat-card.appointments .stat-icon {
            color: var(--success);
        }

        .stat-card.pending .stat-icon {
            color: var(--warning);
        }

        .stat-card.doctors .stat-icon {
            color: var(--info);
        }

        .stat-card.blockchain .stat-icon {
            color: var(--blockchain);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
            color: var(--secondary);
        }

        .stat-label {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        /* Dashboard Sections */
        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .dashboard-sections {
                grid-template-columns: 1fr;
            }
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .section-header h3 {
            font-size: 1.3rem;
            color: var(--secondary);
            margin: 0;
        }

        .section-header a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .section-header a:hover {
            text-decoration: underline;
        }

        /* Patient List Styles */
        .patient-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .patient-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: var(--transition);
        }

        .patient-item:hover {
            background-color: #f8f9fa;
        }

        .patient-item:last-child {
            border-bottom: none;
        }

        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 15px;
        }

        .patient-info {
            flex: 1;
        }

        .patient-name {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
        }

        .patient-details {
            font-size: 0.85rem;
            color: var(--gray);
        }

        .patient-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-blockchain {
            background: var(--blockchain);
            color: white;
        }

        /* Recent Activity */
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: var(--primary);
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Doctors List Styles */
        .doctors-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .doctor-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: var(--transition);
        }

        .doctor-item:hover {
            background-color: #f8f9fa;
        }

        .doctor-item:last-child {
            border-bottom: none;
        }

        .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--info);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 15px;
        }

        .doctor-info {
            flex: 1;
        }

        .doctor-name {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 5px;
        }

        .doctor-specialty {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .duty-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .status-on {
            background-color: #eaf7ee;
            color: var(--success);
        }

        .status-off {
            background-color: #fde8e6;
            color: var(--danger);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .action-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .action-title {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 10px;
        }

        .action-description {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* Blockchain Panel */
        .blockchain-panel {
            background: linear-gradient(135deg, var(--blockchain), var(--blockchain-light));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .blockchain-panel h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .blockchain-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
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
            background: rgba(0, 0, 0, 0.2);
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
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

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            margin-bottom: 15px;
        }

        /* Messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .message.success {
            background-color: #eaf7ee;
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .message.error {
            background-color: #fde8e6;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                padding: 20px;
            }
            .nav-menu {
                display: flex;
                overflow-x: auto;
                padding-bottom: 10px;
            }
            .nav-item {
                margin-right: 15px;
                margin-bottom: 0;
                white-space: nowrap;
            }
            
            .stats-container {
                grid-template-columns: 1fr 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .patient-actions, .doctor-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Welcome Admin</h1>
                <p>Healthcare Management System</p>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard_admin.php" class="active">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_list.php">
                        <i class="fas fa-user-injured"></i>
                        Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_list.php">
                        <i class="fas fa-user-md"></i>
                        Doctors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="test_appoiment.php">
                        <i class="fas fa-calendar-check"></i>
                        Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout_page.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2>Admin Dashboard</h2>
                <div class="user-profile">
                    <span>Welcome, Admin</span>
                    <a href="#" class="btn btn-blockchain" onclick="showBlockchainInfo()" style="margin-left: 10px;">
                        <i class="fas fa-link"></i> Blockchain Info
                    </a>
                </div>
            </div>

            <!-- Display messages -->
            <?php if (isset($message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Blockchain Information Panel -->
            <div class="blockchain-panel">
                <h3><i class="fas fa-cube"></i> Blockchain Network Information</h3>
                <div class="blockchain-info">
                    <div class="blockchain-item">
                        <label><i class="fas fa-network-wired"></i> Network</label>
                        <div><?php echo htmlspecialchars($blockchainConfig['network']); ?></div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-hospital-user"></i> Patient Record System</label>
                        <div class="blockchain-address">
                            <?php echo htmlspecialchars($blockchainConfig['patientRecordSystem']); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $blockchainConfig['patientRecordSystem']; ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
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
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card patients">
                    <div class="stat-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_patients; ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
                
                <div class="stat-card appointments">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value"><?php echo $today_appointments; ?></div>
                    <div class="stat-label">Today's Appointments</div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $pending_appointments; ?></div>
                    <div class="stat-label">Pending Appointments</div>
                </div>
                
                <div class="stat-card doctors">
                    <div class="stat-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_doctors; ?></div>
                    <div class="stat-label">Total Doctors</div>
                </div>


            </div>

            <!-- Dashboard Sections -->
            <div class="dashboard-sections">
                <!-- Recent Patients -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-user-injured"></i> Recent Patients</h3>
                        <a href="patient_list.php">View All</a>
                    </div>
                    <div class="patient-list">
                        <?php if (count($recent_patients) > 0): ?>
                            <?php foreach ($recent_patients as $patient): ?>
                                <div class="patient-item">
                                    <div class="patient-avatar">
                                        <?php echo strtoupper(substr($patient['patientName'], 0, 1)); ?>
                                    </div>
                                    <div class="patient-info">
                                        <div class="patient-name"><?php echo htmlspecialchars($patient['patientName']); ?></div>
                                        <div class="patient-details">
                                            <?php echo htmlspecialchars($patient['email']); ?>
                                            <?php if (!empty($patient['phone'])): ?>
                                                • <?php echo htmlspecialchars($patient['phone']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="patient-actions">
                                        <a href="patient_detail.php?patientId=<?php echo $patient['patientsId']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!empty($patient['blockchain_address'])): ?>
                                            <button class="btn btn-blockchain" onclick="viewPatientBlockchain('<?php echo $patient['blockchain_address']; ?>', '<?php echo htmlspecialchars($patient['patientName']); ?>')">
                                                <i class="fas fa-link"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-injured"></i>
                                <p>No patients found</p>
                                <p class="text-muted">Patient records will appear here once they are added to the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        <a href="test_appoiment.php">View All</a>
                    </div>
                    <div class="activity-list">
                        <?php if (count($recent_activity) > 0): ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title">
                                            Appointment: <?php echo htmlspecialchars($activity['patientName']); ?> with Dr. <?php echo htmlspecialchars($activity['doctorName']); ?>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo date('M j, Y', strtotime($activity['appointment_date'])); ?> 
                                            at <?php echo date('g:i A', strtotime($activity['appointment_time'])); ?>
                                            • <span class="duty-status <?php echo $activity['status'] === 'Scheduled' ? 'status-on' : 'status-off'; ?>">
                                                <?php echo $activity['status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <p>No recent activity</p>
                                <p class="text-muted">Appointment activities will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Additional Section: Active Doctors -->
            <div class="dashboard-sections">
                <!-- Active Doctors -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-user-md"></i> Active Doctors</h3>
                        <a href="doctor_list.php">View All</a>
                    </div>
                    <div class="doctors-list">
                        <?php if (count($doctors) > 0): ?>
                            <?php foreach ($doctors as $doctor): ?>
                                <div class="doctor-item">
                                    <div class="doctor-avatar">
                                        <?php echo strtoupper(substr($doctor['doctorName'], 0, 1)); ?>
                                    </div>
                                    <div class="doctor-info">
                                        <div class="doctor-name">
                                            Dr. <?php echo htmlspecialchars($doctor['doctorName']); ?>
                                            <span class="duty-status <?php echo $doctor['on_duty'] ? 'status-on' : 'status-off'; ?>">
                                                <?php echo $doctor['on_duty'] ? 'ON DUTY' : 'OFF DUTY'; ?>
                                            </span>
                                        </div>
                                        <div class="doctor-specialty">
                                            Healthcare Professional
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-md"></i>
                                <p>No doctors found</p>
                                <p class="text-muted">Doctor records will appear here once they are added to the system.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="section-card">
                    <div class="section-header">
                        <h3><i class="fas fa-chart-bar"></i> System Overview</h3>
                    </div>
                    <div class="patient-list">
                        <div class="patient-item">
                            <div class="patient-info">
                                <div class="patient-name">System Health</div>
                                <div class="patient-details">All systems operational</div>
                            </div>
                            <div class="patient-actions">
                                <span class="duty-status status-on">Good</span>
                            </div>
                        </div>
                        <div class="patient-item">
                            <div class="patient-info">
                                <div class="patient-name">Database Status</div>
                                <div class="patient-details">Connected and running</div>
                            </div>
                            <div class="patient-actions">
                                <span class="duty-status status-on">Active</span>
                            </div>
                        </div>
                        <div class="patient-item">
                            <div class="patient-info">
                                <div class="patient-name">Blockchain Network</div>
                                <div class="patient-details">Ganache Local</div>
                            </div>
                            <div class="patient-actions">
                                <span class="duty-status status-on">Connected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="action-card" onclick="location.href='patient_list.php'">
                    <div class="action-icon">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="action-title">Manage Patients</div>
                    <div class="action-description">View and manage all patients in the system</div>
                </div>
                
                <div class="action-card" onclick="location.href='doctor_list.php'">
                    <div class="action-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="action-title">Manage Doctors</div>
                    <div class="action-description">View and manage all doctors</div>
                </div>
                
                <div class="action-card" onclick="location.href='test_appoiment.php'">
                    <div class="action-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="action-title">Appointments</div>
                    <div class="action-description">Manage all appointments</div>
                </div>
                
                <div class="action-card" onclick="showBlockchainInfo()">
                    <div class="action-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="action-title">Blockchain</div>
                    <div class="action-description">View blockchain network info</div>
                </div>
            </div>
        </main>
    </div>

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

        // View patient blockchain info
        function viewPatientBlockchain(address, patientName) {
            alert('Patient: ' + patientName + '\nBlockchain Address: ' + address + '\n\nThis patient is registered on the blockchain network.');
        }

        // Show blockchain info
        function showBlockchainInfo() {
            alert('Blockchain Network Information:\n\n' +
                  'Network: <?php echo $blockchainConfig['network']; ?>\n' +
                  'Patient Record System: <?php echo $blockchainConfig['patientRecordSystem']; ?>\n' +
                  'Medical Record System: <?php echo $blockchainConfig['medicalRecord']; ?>\n' +
                  'RPC URL: <?php echo $blockchainConfig['rpcUrl']; ?>');
        }
    </script>
</body>
</html>