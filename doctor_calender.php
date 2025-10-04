<?php
// Start session and check if doctor is logged in
session_start();
if (!isset($_SESSION['doctorId']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: logintest_doctor.php");
    exit();
}

$doctor_id = $_SESSION['doctorId'];

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

// Get current month and year from request or use current date
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Validate month and year
if ($month < 1 || $month > 12) $month = (int)date('m');
if ($year < 2020 || $year > 2030) $year = (int)date('Y');

// Calculate first day of the month and number of days
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberOfDays = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('N', $firstDayOfMonth); // 1 (Monday) to 7 (Sunday)

// Calculate previous and next month/year
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Fetch appointments for the selected month FOR THIS DOCTOR ONLY
$startDate = "$year-" . sprintf('%02d', $month) . "-01";
$endDate = "$year-" . sprintf('%02d', $month) . "-" . $numberOfDays;

$appointments = [];
$stmt = $conn->prepare("
    SELECT a.*, p.patientName as patient_name, d.doctorName
    FROM appointments a 
    LEFT JOIN patients p ON a.patientsId = p.patientsId 
    LEFT JOIN doctors d ON a.doctorId = d.doctorId
    WHERE a.doctorId = ? AND a.appointment_date BETWEEN ? AND ?
    ORDER BY a.appointment_date, a.appointment_time
");
$stmt->bind_param("iss", $doctor_id, $startDate, $endDate);
$stmt->execute();
$appointment_result = $stmt->get_result();

if ($appointment_result && $appointment_result->num_rows > 0) {
    while($row = $appointment_result->fetch_assoc()) {
        $appointments[] = $row;
    }
}
$stmt->close();

// Fetch upcoming appointments (next 30 days) FOR THIS DOCTOR ONLY
$today = date('Y-m-d');
$nextMonthDate = date('Y-m-d', strtotime('+30 days'));
$upcoming_appointments = [];
$stmt_upcoming = $conn->prepare("
    SELECT a.*, p.patientName as patient_name, d.doctorName
    FROM appointments a 
    LEFT JOIN patients p ON a.patientsId = p.patientsId 
    LEFT JOIN doctors d ON a.doctorId = d.doctorId
    WHERE a.doctorId = ? AND a.appointment_date BETWEEN ? AND ?
    ORDER BY a.appointment_date, a.appointment_time
");
$stmt_upcoming->bind_param("iss", $doctor_id, $today, $nextMonthDate);
$stmt_upcoming->execute();
$upcoming_result = $stmt_upcoming->get_result();

if ($upcoming_result && $upcoming_result->num_rows > 0) {
    while($row = $upcoming_result->fetch_assoc()) {
        $upcoming_appointments[] = $row;
    }
}
$stmt_upcoming->close();

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare System - Doctor Appointment Calendar</title>
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
            transition: all 0.2s;
        }

        .calendar-day:hover {
            background-color: #f5f7fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .calendar-day.today {
            background-color: #e3f2fd;
            border: 2px solid #3498db;
        }

        .calendar-day.empty {
            background-color: #f9f9f9;
            border: none;
        }

        .calendar-day.other-month {
            opacity: 0.4;
        }

        .calendar-day-number {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .calendar-day.today .calendar-day-number {
            color: #3498db;
        }

        .appointment-event {
            background-color: #e3f2fd;
            border-left: 3px solid #3498db;
            padding: 5px;
            margin-bottom: 5px;
            font-size: 12px;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .appointment-event:hover {
            background-color: #d1e7ff;
            transform: translateX(2px);
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
            transition: all 0.2s;
        }

        .appointment-item:hover {
            background-color: #f9f9f9;
            border-radius: 5px;
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

        .loading {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .modal-title {
            font-size: 20px;
            color: #2c3e50;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }

        .appointment-detail-item {
            margin-bottom: 15px;
        }

        .appointment-detail-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
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
                <h1>Welcome Dr. <?php echo htmlspecialchars($_SESSION['doctor_name']); ?></h1>
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
                    <a href="doctor_appoinment.php">
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
                <li class="nav-item">
                    <a href="logout_doctor.php">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
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
                    <div class="calendar-title"><?php echo date('F Y', $firstDayOfMonth); ?></div>
                    <div class="calendar-nav">
                        <button id="prevMonth" data-month="<?php echo $prevMonth; ?>" data-year="<?php echo $prevYear; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <button id="today">Today</button>
                        <button id="nextMonth" data-month="<?php echo $nextMonth; ?>" data-year="<?php echo $nextYear; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="calendar-grid">
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                    <div class="calendar-day-header">Sun</div>
                    
                    <!-- Calendar days -->
                    <?php
                    // Add empty cells for days before the first day of the month
                    for ($i = 1; $i < $firstDayOfWeek; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                    
                    // Get today's date for highlighting
                    $todayDate = date('Y-m-d');
                    
                    // Add cells for each day of the month
                    for ($day = 1; $day <= $numberOfDays; $day++) {
                        $date = "$year-" . sprintf('%02d', $month) . "-" . sprintf('%02d', $day);
                        $isToday = ($date == $todayDate) ? 'today' : '';
                        
                        echo '<div class="calendar-day ' . $isToday . '" data-date="' . $date . '">';
                        echo '<div class="calendar-day-number">' . $day . '</div>';
                        
                        // Show appointments for this day
                        foreach ($appointments as $appt) {
                            if ($appt['appointment_date'] == $date) {
                                echo '<div class="appointment-event" data-appointment-id="' . $appt['appointmentId'] . '" title="' . 
                                     htmlspecialchars($appt['patient_name']) . ' - ' . 
                                     htmlspecialchars($appt['reason']) . '">' . 
                                     htmlspecialchars($appt['patient_name']) . ' - ' . 
                                     date('g:i A', strtotime($appt['appointment_time'])) . 
                                     '</div>';
                            }
                        }
                        
                        echo '</div>';
                    }
                    
                    // Add empty cells to complete the grid (6 rows x 7 days = 42 cells total)
                    $totalCells = 42; // 6 rows x 7 days
                    $filledCells = $firstDayOfWeek - 1 + $numberOfDays;
                    $remainingCells = $totalCells - $filledCells;
                    
                    for ($i = 0; $i < $remainingCells; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
                    ?>
                </div>
            </div>

            <div class="appointment-details">
                <h3>My Upcoming Appointments</h3>
                <div id="upcoming-appointments-list">
                    <?php
                    $hasUpcomingAppointments = false;
                    
                    foreach ($upcoming_appointments as $appt) {
                        $hasUpcomingAppointments = true;
                        echo '<div class="appointment-item" data-appointment-id="' . $appt['appointmentId'] . '">';
                        echo '<div class="appointment-time">' . 
                             date('M j, Y', strtotime($appt['appointment_date'])) . 
                             ' - ' . 
                             date('g:i A', strtotime($appt['appointment_time'])) . 
                             '</div>';
                        echo '<div class="appointment-patient">' . 
                             'Patient: ' . 
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
                    
                    if (!$hasUpcomingAppointments) {
                        echo '<p class="no-appointments">No upcoming appointments found.</p>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Appointment Detail Modal -->
    <div class="modal" id="appointmentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Appointment Details</h3>
                <button class="close-modal">&times;</button>
            </div>
            <div id="modal-body">
                <!-- Content will be loaded via AJAX -->
                <div class="loading">Loading appointment details...</div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Month navigation
            $('#prevMonth, #nextMonth').on('click', function() {
                const month = $(this).data('month');
                const year = $(this).data('year');
                window.location.href = `?month=${month}&year=${year}`;
            });
            
            // Today button
            $('#today').on('click', function() {
                const today = new Date();
                const month = today.getMonth() + 1;
                const year = today.getFullYear();
                window.location.href = `?month=${month}&year=${year}`;
            });
            
            // Appointment click event - show modal with details
            $('.appointment-event, .appointment-item').on('click', function() {
                const appointmentId = $(this).data('appointment-id');
                showAppointmentDetails(appointmentId);
            });
            
            // Close modal
            $('.close-modal').on('click', function() {
                $('#appointmentModal').hide();
            });
            
            // Close modal if clicked outside
            $(window).on('click', function(event) {
                if ($(event.target).is('#appointmentModal')) {
                    $('#appointmentModal').hide();
                }
            });
            
            // Function to show appointment details
            function showAppointmentDetails(appointmentId) {
                $('#appointmentModal').show();
                $('#modal-body').html('<div class="loading">Loading appointment details...</div>');
                
                // AJAX call to get appointment details
                $.ajax({
                    url: 'get_appointment_details.php',
                    type: 'GET',
                    data: { 
                        appointment_id: appointmentId,
                        doctor_id: <?php echo $doctor_id; ?>
                    },
                    success: function(response) {
                        $('#modal-body').html(response);
                    },
                    error: function() {
                        $('#modal-body').html('<div class="error">Error loading appointment details.</div>');
                    }
                });
            }
        });
    </script>
</body>
</html>