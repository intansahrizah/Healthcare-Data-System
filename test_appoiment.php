<?php
// Database connection
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

// Check if appointments table has auto_increment
$table_check = $conn->query("SHOW COLUMNS FROM appointments LIKE 'appointmentId'");
if ($table_check->num_rows > 0) {
    $column = $table_check->fetch_assoc();
    if (strpos($column['Extra'], 'auto_increment') === false) {
        $error_message = "Database error: appointmentId column is not set to auto_increment. Please run: ALTER TABLE appointments MODIFY appointmentId INT AUTO_INCREMENT PRIMARY KEY";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_appointment'])) {
    $patientsId = $_POST['patientsId'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $doctorId = $_POST['doctorId'];
    $reason = $_POST['reason'];
    $notes = $_POST['notes'];
    $status = 'Scheduled'; // Default status

    $sql = "INSERT INTO appointments (patientsId, appointment_date, appointment_time, doctorId, reason, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $patientsId, $appointment_date, $appointment_time, $doctorId, $reason, $notes, $status);
    
    if ($stmt->execute()) {
        $success_message = "Appointment scheduled successfully!";
    } else {
        $error_message = "Error: " . $stmt->error;
        // Check if it's the auto_increment error
        if (strpos($stmt->error, "doesn't have a default value") !== false) {
            $error_message .= "<br>Please run this SQL command: ALTER TABLE appointments MODIFY appointmentId INT AUTO_INCREMENT PRIMARY KEY";
        }
    }
    
    $stmt->close();
}

// Fetch patients for dropdown
$patients = [];
$patient_result = $conn->query("SELECT patientsId, patientName FROM patients ORDER BY patientName");
if ($patient_result && $patient_result->num_rows > 0) {
    while($row = $patient_result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Fetch doctors from database
$doctors = [];
$doctor_result = $conn->query("SELECT doctorId, doctorName FROM doctors ORDER BY doctorName");
if ($doctor_result && $doctor_result->num_rows > 0) {
    while($row = $doctor_result->fetch_assoc()) {
        $doctors[] = $row;
    }
}

// Fetch appointments for display
$appointments = [];
$appointment_result = $conn->query("
    SELECT a.*, p.patientName as patient_name, d.doctorName
    FROM appointments a 
    LEFT JOIN patients p ON a.patientsId = p.patientsId 
    LEFT JOIN doctors d ON a.doctorId = d.doctorId
    ORDER BY a.appointment_date, a.appointment_time
");
if ($appointment_result && $appointment_result->num_rows > 0) {
    while($row = $appointment_result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Data Sharing - Appointments</title>
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #f5f7fa;
            --dark: #333;
            --white: #ffffff;
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
        }

        .header h2 {
            font-size: 28px;
            color: #2c3e50;
        }

        .search-bar {
            display: flex;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-size: 14px;
        }

        .search-bar button {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }

        .appointment-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .calendar-view {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .calendar {
            flex: 3;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }

        .calendar-nav button {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 0 10px;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 10px;
            color: #2c3e50;
        }

        .calendar-day {
            height: 80px;
            padding: 5px;
            border: 1px solid #eee;
            border-radius: 5px;
            overflow-y: auto;
        }

        .calendar-day.empty {
            background-color: #f9f9f9;
            border: none;
        }

        .calendar-day-number {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .appointment-event {
            background-color: #e3f2fd;
            border-left: 3px solid #3498db;
            padding: 3px;
            margin-bottom: 3px;
            font-size: 12px;
            border-radius: 3px;
            cursor: pointer;
        }

        .appointment-list {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .appointment-list h3 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .appointment-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }

        .appointment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .appointment-time {
            font-weight: 600;
            color: #3498db;
            margin-bottom: 5px;
        }

        .appointment-patient {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .appointment-purpose {
            color: #7f8c8d;
            font-size: 13px;
        }

        .appointment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .appointment-table th, 
        .appointment-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .appointment-table th {
            background-color: #2c3e50;
            font-weight: 600;
            color: #eee;
        }

        .appointment-table {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-scheduled {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .action-btn {
            padding: 5px 10px;
            margin-right: 5px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }

        .action-btn:hover {
            background-color: #e9ecef;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            width: 500px;
            max-width: 90%;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Message Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
            .calendar-view {
                flex-direction: column;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
                    <a href="dashboard_doctor.php">
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
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="active">
                        <i class="fas fa-calendar-check"></i>
                        Appointments
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2>Appointment Management</h2>
                <div class="user-profile">
                    <!-- User profile/notification can be added here -->
                </div>
            </div>

            <!-- Display success/error messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="search-bar">
                <input type="text" placeholder="Search appointments...">
                <button><i class="fas fa-search"></i></button>
            </div>

            <div class="appointment-actions">
                <button class="btn" id="newAppointmentBtn">
                    <i class="fas fa-plus"></i> New Appointment
                </button>
                <div>
                    <button class="btn btn-secondary">
                        <i class="fas fa-calendar-day"></i> Today
                    </button>
                    <button class="btn btn-secondary">
                        <i class="fas fa-calendar-week"></i> Week
                    </button>
                    <button class="btn btn-secondary">
                        <i class="fas fa-calendar-alt"></i> Month
                    </button>
                </div>
            </div>

            <div class="calendar-view">
                <div class="calendar">
                    <div class="calendar-header">
                        <div class="calendar-title"><?php echo date('F Y'); ?></div>
                        <div class="calendar-nav">
                            <button><i class="fas fa-chevron-left"></i></button>
                            <button><i class="fas fa-chevron-right"></i></button>
                        </div>
                    </div>
                    <div class="calendar-grid">
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>
                        
                        <!-- Calendar days -->
                        <?php
                        // Get first day of month and number of days
                        $firstDay = date('N', strtotime(date('Y-m-01')));
                        $daysInMonth = date('t');
                        
                        // Add empty cells for days before the first day of the month
                        for ($i = 1; $i < $firstDay; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        
                        // Add cells for each day of the month
                        for ($day = 1; $day <= $daysInMonth; $day++) {
                            $date = date('Y-m-') . sprintf('%02d', $day);
                            $hasAppointment = false;
                            
                            // Check if this day has any appointments
                            foreach ($appointments as $appt) {
                                if ($appt['appointment_date'] == $date) {
                                    $hasAppointment = true;
                                    break;
                                }
                            }
                            
                            echo '<div class="calendar-day">';
                            echo '<div class="calendar-day-number">' . $day . '</div>';
                            
                            // Show appointments for this day
                            foreach ($appointments as $appt) {
                                if ($appt['appointment_date'] == $date) {
                                    echo '<div class="appointment-event">' . 
                                         htmlspecialchars($appt['patient_name']) . ' - ' . 
                                         date('g:i A', strtotime($appt['appointment_time'])) . 
                                         '</div>';
                                }
                            }
                            
                            echo '</div>';
                        }
                        
                        // Add empty cells to complete the grid
                        $lastCell = $firstDay + $daysInMonth - 1;
                        $remainingCells = 42 - $lastCell; // 6 rows x 7 days = 42 cells
                        
                        for ($i = 1; $i <= $remainingCells; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        ?>
                    </div>
                </div>

                <div class="appointment-list">
                    <h3>Today's Appointments</h3>
                    <?php
                    $today = date('Y-m-d');
                    $hasTodayAppointments = false;
                    
                    foreach ($appointments as $appt) {
                        if ($appt['appointment_date'] == $today) {
                            $hasTodayAppointments = true;
                            echo '<div class="appointment-item">';
                            echo '<div class="appointment-time">' . 
                                 date('g:i A', strtotime($appt['appointment_time'])) . 
                                 '</div>';
                            echo '<div class="appointment-patient">' . 
                                 htmlspecialchars($appt['patient_name']) . 
                                 '</div>';
                            echo '<div class="appointment-purpose">' . 
                                 htmlspecialchars($appt['reason']) . 
                                 '</div>';
                            echo '<span class="status-badge status-' . 
                                 strtolower($appt['status']) . '">' . 
                                 $appt['status'] . 
                                 '</span>';
                            echo '</div>';
                        }
                    }
                    
                    if (!$hasTodayAppointments) {
                        echo '<p>No appointments scheduled for today.</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="appointment-table-container">
                <h3>Upcoming Appointments</h3>
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Purpose</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($appointments) > 0) {
                            foreach ($appointments as $appt) {
                                echo '<tr>';
                                echo '<td>' . 
                                     date('M j, Y', strtotime($appt['appointment_date'])) . 
                                     ' - ' . 
                                     date('g:i A', strtotime($appt['appointment_time'])) . 
                                     '</td>';
                                echo '<td>' . htmlspecialchars($appt['patient_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($appt['reason']) . '</td>';
                                echo '<td>' . htmlspecialchars($appt['doctorName']) . '</td>';
                                echo '<td><span class="status-badge status-' . 
                                     strtolower($appt['status']) . '">' . 
                                     $appt['status'] . 
                                     '</span></td>';
                                echo '<td>';
                                echo '<button class="action-btn"><i class="fas fa-edit"></i></button>';
                                echo '<button class="action-btn"><i class="fas fa-times"></i></button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="6" style="text-align: center;">No appointments found.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- New Appointment Modal -->
    <div class="modal" id="appointmentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">New Appointment</h3>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment-patient">Patient</label>
                        <select id="appointment-patient" name="patientsId" required>
                            <option value="">Select Patient</option>
                            <?php
                            foreach ($patients as $patient) {
                                echo '<option value="' . $patient['patientsId'] . '">' . 
                                     htmlspecialchars($patient['patientName']) . 
                                     '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="appointment-date">Date</label>
                        <input type="date" id="appointment-date" name="appointment_date" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment-time">Time</label>
                        <input type="time" id="appointment-time" name="appointment_time" required>
                    </div>
                    <div class="form-group">
                        <label for="appointment-doctor">Doctor</label>
                        <select id="appointment-doctor" name="doctorId" required>
                            <option value="">Select Doctor</option>
                            <?php
                            foreach ($doctors as $doctor) {
                                echo '<option value="' . $doctor['doctorId'] . '">' . 
                                     htmlspecialchars($doctor['doctorName']) . 
                                     '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="appointment-purpose">Reason</label>
                    <input type="text" id="appointment-purpose" name="reason" placeholder="Enter appointment reason" required>
                </div>
                
                <div class="form-group">
                    <label for="appointment-notes">Notes</label>
                    <textarea id="appointment-notes" name="notes" placeholder="Additional notes..."></textarea>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelAppointment">Cancel</button>
                    <button type="submit" class="btn" name="save_appointment">Save Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Simple modal toggle functionality
        document.getElementById('newAppointmentBtn').addEventListener('click', function() {
            document.getElementById('appointmentModal').style.display = 'flex';
        });

        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('appointmentModal').style.display = 'none';
        });

        document.getElementById('cancelAppointment').addEventListener('click', function() {
            document.getElementById('appointmentModal').style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('appointmentModal')) {
                document.getElementById('appointmentModal').style.display = 'none';
            }
        });

        // Set today's date as default for the date field
        document.getElementById('appointment-date').valueAsDate = new Date();

    </script>
</body>
</html>