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
    <title>Healthcare System - Patient Appointment Calendar</title>
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

        .calendar-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .calendar-title {
            font-size: 24px;
            font-weight: 600;
            color: #2c3e50;
        }

        .calendar-nav button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 0 5px;
            transition: background 0.3s;
        }

        .calendar-nav button:hover {
            background: #2980b9;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 15px 10px;
            color: #2c3e50;
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .calendar-day {
            min-height: 100px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 5px;
            overflow-y: auto;
            background-color: white;
        }

        .calendar-day.empty {
            background-color: #f9f9f9;
            border: none;
        }

        .calendar-day-number {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .appointment-event {
            background-color: #e3f2fd;
            border-left: 3px solid #3498db;
            padding: 5px;
            margin-bottom: 5px;
            font-size: 12px;
            border-radius: 3px;
            cursor: pointer;
        }

        .appointment-details {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .appointment-details h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
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

        .status-badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-top: 5px;
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

        .no-appointments {
            text-align: center;
            color: #7f8c8d;
            padding: 20px;
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
            .calendar-grid {
                gap: 5px;
            }
            
            .calendar-day {
                min-height: 80px;
                padding: 5px;
                font-size: 12px;
            }
            
            .appointment-event {
                font-size: 10px;
                padding: 3px;
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
                <h1>Welcome Patient</h1>
                <p>Healthcare Management System</p>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard_doctor.php..">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doc_listpatient.php">
                        <i class="fas fa-user-injured"></i>
                        Patients
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
                <h2>My Appointments</h2>
                <div class="user-profile">
                    <!-- User profile/notification can be added here -->
                </div>
            </div>

            <div class="calendar-container">
                <div class="calendar-header">
                    <div class="calendar-title"><?php echo date('F Y'); ?></div>
                    <div class="calendar-nav">
                        <button id="prevMonth"><i class="fas fa-chevron-left"></i> Previous</button>
                        <button id="nextMonth">Next <i class="fas fa-chevron-right"></i></button>
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
                                echo '<div class="appointment-event" title="' . 
                                     htmlspecialchars($appt['patient_name']) . ' - ' . 
                                     htmlspecialchars($appt['reason']) . '">' . 
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

            <div class="appointment-details">
                <h3>My Upcoming Appointments</h3>
                <?php
                $hasUpcomingAppointments = false;
                $today = date('Y-m-d');
                
                foreach ($appointments as $appt) {
                    if ($appt['appointment_date'] >= $today) {
                        $hasUpcomingAppointments = true;
                        echo '<div class="appointment-item">';
                        echo '<div class="appointment-time">' . 
                             date('M j, Y', strtotime($appt['appointment_date'])) . 
                             ' - ' . 
                             date('g:i A', strtotime($appt['appointment_time'])) . 
                             '</div>';
                        echo '<div class="appointment-patient">' . 
                             'Appointment with Dr. ' . 
                             htmlspecialchars($appt['doctorName']) . 
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
                
                if (!$hasUpcomingAppointments) {
                    echo '<p class="no-appointments">No upcoming appointments found.</p>';
                }
                ?>
            </div>
        </main>
    </div>

    <script>
        // Simple month navigation
        document.getElementById('prevMonth').addEventListener('click', function() {
            // In a real implementation, this would navigate to the previous month
            alert('Previous month navigation would be implemented here');
        });

        document.getElementById('nextMonth').addEventListener('click', function() {
            // In a real implementation, this would navigate to the next month
            alert('Next month navigation would be implemented here');
        });
    </script>
</body>
</html>