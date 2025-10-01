<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Data Sharing - Doctor Appointments</title>
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

        .search-bar {
            display: flex;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-size: 14px;
        }

        .search-bar button {
            padding: 12px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-bar button:hover {
            background-color: #2980b9;
        }

        .appointment-table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 20px;
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
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }

        .appointment-table tr:hover {
            background-color: #f5f7fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
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

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .view-btn {
            display: inline-block;
            padding: 8px 12px;
            background-color: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .view-btn:hover {
            background-color: #2980b9;
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

        /* Patient Details Modal */
        #patientModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        #patientModal .modal-content {
            width: 700px;
            max-width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .patient-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .detail-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .detail-card h4 {
            margin-bottom: 10px;
            color: #2c3e50;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .detail-value {
            padding: 8px;
            background: #f9f9f9;
            border-radius: 5px;
            border-left: 3px solid var(--primary);
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
            
            .appointment-table {
                display: block;
                overflow-x: auto;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }

        .stat-label {
            color: var(--gray);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Welcome Doctor</h1>
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
                    <a href="doctor_appoinment.php" class="active">
                        <i class="fas fa-user-injured"></i>
                        Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_calender.php">
                        <i class="fas fa-calendar-check"></i>
                        Appointments
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2>Doctor Appointment Management</h2>
                <div class="user-profile">
                    <span>Dr. Smith</span>
                </div>
            </div>

            <?php
            // Database connection parameters
            $servername = "localhost";
            $username = "root"; // Your MySQL username
            $password = ""; // Your MySQL password
            $dbname = "healthcare_system"; // Your database name

            // Create connection
            $conn = new mysqli($servername, $username, $password, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Initialize variables
            $success_message = "";
            $error_message = "";
            $appointments = [];
            $search = "";

            // Handle search
            if (isset($_GET['search'])) {
                $search = $conn->real_escape_string($_GET['search']);
            }

            // Handle appointment actions
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                if (isset($_POST['confirm_appointment'])) {
                    $appointment_id = $conn->real_escape_string($_POST['appointment_id']);
                    $sql = "UPDATE appointments SET status = 'Confirmed' WHERE appointmentId = $appointment_id";
                    
                    if ($conn->query($sql)) {
                        $success_message = "Appointment confirmed successfully!";
                    } else {
                        $error_message = "Error confirming appointment: " . $conn->error;
                    }
                } 
                elseif (isset($_POST['cancel_appointment'])) {
                    $appointment_id = $conn->real_escape_string($_POST['appointmentId']);
                    $sql = "UPDATE appointments SET status = 'Cancelled' WHERE appointmentId = $appointment_id";
                    
                    if ($conn->query($sql)) {
                        $success_message = "Appointment cancelled successfully!";
                    } else {
                        $error_message = "Error cancelling appointment: " . $conn->error;
                    }
                } 
                elseif (isset($_POST['complete_appointment'])) {
                    $appointment_id = $conn->real_escape_string($_POST['appointment_id']);
                    $sql = "UPDATE appointments SET status = 'Completed' WHERE appointmentId = $appointment_id";
                    
                    if ($conn->query($sql)) {
                        $success_message = "Appointment marked as completed!";
                    } else {
                        $error_message = "Error completing appointment: " . $conn->error;
                    }
                }
            }

            // Fetch appointments from database
            $sql = "SELECT a.*, patientName as patient_name 
                    FROM appointments a 
                    JOIN patients p ON a.patientsId = p.patientsId 
                    WHERE a.doctorId = 2";  // Assuming doctor ID 2 is Dr. Smith

            if (!empty($search)) {
                $sql .= " AND (patientName LIKE '%$search%' OR a.reason LIKE '%$search%' OR a.status LIKE '%$search%')";
            }

            $sql .= " ORDER BY a.appointment_date, a.appointment_time";

            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $appointments[] = $row;
                }
            }

            // Close connection
            $conn->close();
            ?>

            <!-- Display success/error messages -->
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-value"><?php echo count($appointments); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Scheduled</div>
                    <div class="stat-value">
                        <?php
                            $scheduled = 0;
                            foreach ($appointments as $appt) {
                                if ($appt['status'] == 'Scheduled') $scheduled++;
                            }
                            echo $scheduled;
                        ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Confirmed</div>
                    <div class="stat-value">
                        <?php
                            $confirmed = 0;
                            foreach ($appointments as $appt) {
                                if ($appt['status'] == 'Confirmed') $confirmed++;
                            }
                            echo $confirmed;
                        ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Completed</div>
                    <div class="stat-value">
                        <?php
                            $completed = 0;
                            foreach ($appointments as $appt) {
                                if ($appt['status'] == 'Completed') $completed++;
                            }
                            echo $completed;
                        ?>
                    </div>
                </div>
            </div>

            <div class="search-bar">
                <form method="GET" action="doctor_appoinment.php" style="display: flex; width: 100%;">
                    <input type="text" name="search" placeholder="Search appointments by patient name, reason, or status..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>

            <div class="appointment-table-container">
                <h3>Appointment List</h3>
                <?php if (count($appointments) > 0): ?>
                    <table class="appointment-table">
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($appointment['status']); ?>">
                                            <?php echo $appointment['status']; ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($appointment['status'] == 'Scheduled'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointmentId']; ?>">
                                                <button type="submit" name="confirm_appointment" class="btn btn-success">Confirm</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($appointment['status'] != 'Cancelled' && $appointment['status'] != 'Completed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointmentId']; ?>">
                                                <button type="submit" name="cancel_appointment" class="btn btn-danger">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($appointment['status'] == 'Confirmed'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointmentId']; ?>">
                                                <button type="submit" name="complete_appointment" class="btn btn-primary">Complete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">
                        <?php echo empty($search) ? "No appointments found." : "No appointments match your search criteria."; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Function to show patient details
        function showPatientDetails(patientId) {
            // In a real application, you would fetch patient details via AJAX
            // For this example, we'll just show a static modal
            document.getElementById('patientModal').style.display = 'flex';
        }

        // Function to close modals
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        $blockchain_data = [
            'appointmentId' => $appointment_id,
            'patientsId' => $_POST['patientsId'],
            'appointment_date' => $_POST['date'],
            'appointment_time' => $_POST['time'],
            'doctorId' => $_POST['doctorId'],
            'reason' => $_POST['reason'],
            'status' => 'scheduled'
        ];

        $ch = curl_init('http://localhost:3000/api/scheduleAppointment');
    </script>
</body>
</html>