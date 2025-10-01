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

// Process form submission if POST request
$alertMessage = '';
$alertType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientsId = $_POST['patientsId'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $doctor_notes = $_POST['doctor_notes'];
    
    // Insert new medical history
    $insertSql = "INSERT INTO medical_history (patientsId, diagnosis, treatment, doctor_notes) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("isss", $patientsId, $diagnosis, $treatment, $doctor_notes);
    
    if ($insertStmt->execute()) {
        $alertMessage = "Medical history added successfully!";
        $alertType = "success";
    } else {
        $alertMessage = "Error adding medical history: " . $conn->error;
        $alertType = "error";
    }
}

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($patient['patientName']); ?> - Medical History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Your existing CSS styles remain unchanged */
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
                    <a href="doc_patientlist.php">
                        <i class="fas fa-user-injured"></i> Patients
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
                <h2><i class="fas fa-history"></i> Patient Medical Information </h2>
                <div>
                    <a href="doc_listpatient.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="patient_detail.php?patientName=<?php echo urlencode($patient['patientName']); ?>" class="btn">
                        <i class="fas fa-user"></i> Patient Details
                    </a>
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
                    <span class="summary-label">Phone Number</span>
                    <span class="summary-value"><?php echo htmlspecialchars($patient['phone']); ?></span>
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
                            <i class="fas fa-plus"></i> Add Entry
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
                                        <span class="history-id">Entry #<?php echo $entry['history_id']; ?></span>
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
            $('#submit-button').prop('disabled', true).html('<span class="spinner"></span> Adding...');
            
            // Submit the form via AJAX to avoid page reload
            $.ajax({
                url: window.location.href, // Submit to the same page
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    // Extract the HTML from the response
                    var html = $(response);
                    
                    // Replace the content
                    $('#alert-container').html(html.find('#alert-container').html());
                    $('#history-list-container').html(html.find('#history-list-container').html());
                    
                    // Reset the form
                    $('#medical-history-form')[0].reset();
                    
                    // Reset button state
                    $('#submit-button').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Entry');
                    
                    // Scroll to top to see the alert
                    window.scrollTo(0, 0);
                },
                error: function(xhr, status, error) {
                    // Show error message
                    showAlert('error', 'An error occurred: ' + error);
                    
                    // Reset button state
                    $('#submit-button').prop('disabled', false).html('<i class="fas fa-plus"></i> Add Entry');
                }
            });
        });
        
        // Function to show alert messages
        function showAlert(type, message) {
            var alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            
            var alertHtml = '<div class="alert ' + alertClass + '">' +
                '<i class="fas ' + icon + '"></i> ' + message +
                '</div>';
            
            $('#alert-container').html(alertHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('#alert-container').empty();
            }, 5000);
        }
    });
    </script>
</body>
</html>