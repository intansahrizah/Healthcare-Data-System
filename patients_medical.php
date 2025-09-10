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

// Get patient ID from URL parameter
$patientId = isset($_GET['patientsId']) ? intval($_GET['patientsId']) : 0;

// Fetch patient details
$patient = null;
$medicalHistory = [];

if ($patientId > 0) {
    // Get patient basic information
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patientsId = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
    }
    $stmt->close();

    // Get medical history for this patient
    $stmt = $conn->prepare("SELECT * FROM medical_history WHERE patientsId = ? ORDER BY visit_date DESC");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $medicalHistory[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();

// If no patient found, redirect back to list
if (!$patient) {
    header("Location: patient_list.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($patient['fullName']); ?> - Medical History</title>
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
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
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
            background-color: #f9fafb;
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

        .back-btn {
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
        }

        .back-btn:hover {
            background-color: var(--primary-dark);
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

        /* Patient Info Card */
        .patient-info-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .patient-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .patient-id {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--dark);
        }

        /* Medical History Section */
        .history-section {
            margin-top: 2rem;
        }

        .section-title {
            font-size: 1.3rem;
            color: var(--secondary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary);
        }

        .history-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .history-date {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .history-details {
            margin-top: 1rem;
        }

        .detail-item {
            margin-bottom: 0.8rem;
        }

        .detail-label {
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 0.2rem;
            font-size: 0.9rem;
        }

        .detail-content {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid var(--primary);
        }

        .no-history {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
        }

        .no-history i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Add New History Form */
        .add-history-form {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
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
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .patient-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .patient-id {
                margin-top: 0.5rem;
            }
            
            .history-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .history-date {
                margin-top: 0.5rem;
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
                    <a href="HDS_HomePage.html">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_list.php">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="active">
                        <i class="fas fa-file-medical"></i> Records
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <div>
                    <a href="patient_list.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Patients
                    </a>
                    <h2><i class="fas fa-file-medical"></i> Medical History</h2>
                </div>
            </div>

            <!-- Patient Information -->
            <div class="patient-info-card">
                <div class="patient-header">
                    <div>
                        <div class="patient-name"><?php echo htmlspecialchars($patient['fullName']); ?></div>
                        <div class="patient-id">Patient ID: <?php echo htmlspecialchars($patient['patientsId']); ?></div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">IC Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($patient['ic_number']); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?php echo htmlspecialchars($patient['gender']); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($patient['phone']); ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($patient['address']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Medical History -->
            <div class="history-section">
                <h3 class="section-title">Medical History</h3>

                <?php if (count($medicalHistory) > 0): ?>
                    <?php foreach ($medicalHistory as $history): ?>
                        <div class="history-card">
                            <div class="history-header">
                                <div class="history-title">Medical Visit</div>
                                <div class="history-date"><?php echo date('M j, Y g:i A', strtotime($history['visit_date'])); ?></div>
                            </div>

                            <div class="history-details">
                                <div class="detail-item">
                                    <div class="detail-label">Diagnosis</div>
                                    <div class="detail-content"><?php echo htmlspecialchars($history['diagnosis']); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Treatment</div>
                                    <div class="detail-content"><?php echo htmlspecialchars($history['treatment']); ?></div>
                                </div>

                                <div class="detail-item">
                                    <div class="detail-label">Doctor's Notes</div>
                                    <div class="detail-content"><?php echo htmlspecialchars($history['doctor_notes']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-history">
                        <i class="fas fa-file-medical-alt"></i>
                        <h3>No Medical History Found</h3>
                        <p>No medical history records found for this patient.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add New History Form -->
            <div class="add-history-form">
                <h3 class="section-title">Add New Medical Entry</h3>
                <form action="add_medical_history.php" method="POST">
                    <input type="hidden" name="patientsId" value="<?php echo $patientId; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="diagnosis">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="treatment">Treatment</label>
                        <textarea class="form-control" id="treatment" name="treatment" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="doctor_notes">Doctor's Notes</label>
                        <textarea class="form-control" id="doctor_notes" name="doctor_notes"></textarea>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-plus"></i> Add Medical Entry
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>