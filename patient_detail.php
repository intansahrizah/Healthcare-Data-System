<?php
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

// Check if patient name is provided
if (!isset($_GET['patientName']) || empty($_GET['patientName'])) {
    die("Patient name not specified");
}

$patientName = $_GET['patientName'];

// Fetch patient details using patientName
$sql = "SELECT * FROM patients WHERE patientName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $patientName);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Patient not found");
}

$patient = $result->fetch_assoc();
$patientId = $patient['patientsId']; // Get the patient ID for medical history

// Fetch medical history
$historySql = "SELECT * FROM medical_history WHERE patientsId = ? ORDER BY visit_date DESC";
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

        /* Blockchain Info */
        .blockchain-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
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
                    <a href="dashboard_admin.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_list.php">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="test_appoiment.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_list.php">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-history"></i> Patient Medical Information</h2>
                <div>
                    <a href="patient_list.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
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
                    <span class="summary-label">Phone Number</span>
                    <span class="summary-value"><?php echo htmlspecialchars($patient['phone']); ?></span>
                </div>
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
</body>
</html>

<?php
$conn->close();
?>