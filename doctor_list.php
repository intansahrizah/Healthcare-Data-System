
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

// --- Handle POST Requests ---

// Handle toggle duty status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_duty']) && isset($_POST['doctor_id'])) {
    $doctor_id = $_POST['doctor_id'];
    
    // Get current status
    $stmt = $conn->prepare("SELECT on_duty FROM doctors WHERE doctorId = ?");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $currentStatus = $result->fetch_assoc()['on_duty'];
    $newStatus = $currentStatus ? 0 : 1;
    
    // Update status
    $updateStmt = $conn->prepare("UPDATE doctors SET on_duty = ? WHERE doctorId = ?");
    $updateStmt->bind_param("ii", $newStatus, $doctor_id);
    $updateStmt->execute();
    
    // Redirect to avoid form resubmission
    header("Location: doctor_list.php?" . http_build_query($_GET));
    exit();
}

// Handle single day schedule management
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_schedule'])) {
    $doctor_id = $_POST['doctor_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Check if schedule already exists
    $checkStmt = $conn->prepare("SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?");
    $checkStmt->bind_param("is", $doctor_id, $day_of_week);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing schedule
        $updateStmt = $conn->prepare("UPDATE doctor_schedules SET start_time = ?, end_time = ?, is_available = ? WHERE doctor_id = ? AND day_of_week = ?");
        $updateStmt->bind_param("ssiis", $start_time, $end_time, $is_available, $doctor_id, $day_of_week);
        $updateStmt->execute();
    } else {
        // Insert new schedule
        $insertStmt = $conn->prepare("INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->bind_param("isssi", $doctor_id, $day_of_week, $start_time, $end_time, $is_available);
        $insertStmt->execute();
    }
    
    // Redirect to avoid form resubmission
    header("Location: doctor_list.php?" . http_build_query($_GET));
    exit();
}

// Handle edit doctor request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_doctor'])) {
    $doctor_id = $_POST['doctor_id'];
    // Redirect to edit page
    header("Location: edit_doctor.php?doctorId=" . $doctor_id);
    exit();
}

// --- Data Retrieval for Page Display ---

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$shift_filter = isset($_GET['shift']) ? $_GET['shift'] : 'all';

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

// Build SQL query for doctor list with filters
$sql = "SELECT doctorId, doctorName, license_hash, email, phone, on_duty, shift FROM doctors WHERE 1=1";
$params = [];
$types = '';

if (!empty($search)) {
    $sql .= " AND (doctorName LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= 'ss';
}

if ($status_filter != 'all') {
    $on_duty_value = ($status_filter == 'available') ? 1 : 0;
    $sql .= " AND on_duty = ?";
    $params[] = $on_duty_value;
    $types .= 'i';
}

if ($shift_filter != 'all') {
    $sql .= " AND shift = ?";
    $params[] = $shift_filter;
    $types .= 's';
}

$sql .= " ORDER BY doctorName";

// Prepare and execute the statement for the doctor list
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$doctorsResult = $stmt->get_result();

// Get statistics
$total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'];
$available_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE on_duty = 1")->fetch_assoc()['count'];
$off_duty_doctors = $total_doctors - $available_doctors;

// Fetch all doctor schedules
$schedules = [];
$schedule_result = $conn->query("
    SELECT ds.*, d.doctorName 
    FROM doctor_schedules ds 
    LEFT JOIN doctors d ON ds.doctor_id = d.doctorId
    WHERE ds.is_available = 1
    ORDER BY ds.day_of_week, ds.start_time
");

if ($schedule_result && $schedule_result->num_rows > 0) {
    while($row = $schedule_result->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Get today's day name for filtering
$todayDayName = strtolower(date('l')); // e.g., "monday"
$todays_schedules = [];

// Filter schedules for today
foreach ($schedules as $schedule) {
    if (strtolower($schedule['day_of_week']) === $todayDayName) {
        $todays_schedules[] = $schedule;
    }
}

// Close connection
$conn->close();

// Function to get schedules for a specific day of the week
function getSchedulesForDay($schedules, $dayName) {
    $daySchedules = [];
    foreach ($schedules as $schedule) {
        if (strtolower($schedule['day_of_week']) === strtolower($dayName)) {
            $daySchedules[] = $schedule;
        }
    }
    return $daySchedules;
}

// Days of week for calendar display
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - Healthcare System</title>
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
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #17a2b8;
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

        .btn-success {
            background-color: var(--success);
        }
        
        .btn-success:hover {
            background-color: #219653;
        }

        .btn-danger {
            background-color: var(--danger);
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: white;
        }

        .stat-icon.doctors {
            background-color: var(--primary);
        }

        .stat-icon.available {
            background-color: var(--success);
        }

        .stat-icon.off-duty {
            background-color: var(--info);
        }

        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            color: var(--secondary);
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Panel Styles */
        .panel {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .panel-header {
            background-color: var(--secondary);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header h3 {
            font-size: 18px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-body {
            padding: 20px;
        }

        /* Search and Filter Styles */
        .search-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: var(--white);
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: var(--shadow);
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            border: none;
            outline: none;
            margin-left: 10px;
            width: 100%;
            background: transparent;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background: var(--white);
            box-shadow: var(--shadow);
        }

        /* Table Styles */
        .table-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .doctor-table {
            width: 100%;
            border-collapse: collapse;
        }

        .doctor-table th {
            background-color: var(--secondary);
            color: white;
            text-align: left;
            padding: 15px;
            font-weight: 500;
        }

        .doctor-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .doctor-table tr:hover {
            background-color: #f5f5f5;
        }

        /* Doctor Avatar */
        .doctor-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            margin-right: 12px;
            font-weight: 600;
        }

        .doctor-info {
            display: flex;
            align-items: center;
        }

        .doctor-name {
            font-weight: 600;
            color: var(--dark);
        }

        .doctor-license {
            font-size: 13px;
            color: #7f8c8d;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .status-available {
            background-color: #e8f5e9;
            color: var(--success);
        }

        .status-off-duty {
            background-color: #ffebee;
            color: var(--danger);
        }

        .shift-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .shift-morning {
            background-color: #e8f5e9;
            color: var(--success);
        }

        .shift-afternoon {
            background-color: #fff3e0;
            color: var(--warning);
        }

        .shift-night {
            background-color: #fbe9e7;
            color: #ff5722;
        }

        /* Action Buttons */
        .action-btns {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            color: var(--dark);
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .action-btn:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Calendar Styles */
        .calendar-view {
            display: flex;
            gap: 20px;
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
            color: var(--secondary);
        }

        .calendar-nav button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 0 5px;
            transition: background 0.3s;
        }

        .calendar-nav button:hover {
            background: var(--primary-dark);
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
            color: var(--secondary);
            background-color: #f8f9fa;
            border-radius: 5px;
        }

        .calendar-day {
            min-height: 120px;
            padding: 8px;
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
            border: 2px solid var(--primary);
        }

        .calendar-day.empty {
            background-color: #f9f9f9;
            border: none;
            cursor: default;
        }

        .calendar-day-number {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--secondary);
            font-size: 14px;
        }

        .calendar-day.today .calendar-day-number {
            color: var(--primary);
        }

        .schedule-event {
            background-color: #e3f2fd;
            border-left: 3px solid var(--primary);
            padding: 4px;
            margin-bottom: 4px;
            font-size: 11px;
            border-radius: 3px;
            cursor: pointer;
        }

        .schedule-event:hover {
            background-color: #d1e7ff;
        }

        .schedule-list {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            max-height: 500px;
            overflow-y: auto;
        }

        .schedule-list h3 {
            margin-bottom: 20px;
            color: var(--secondary);
        }

        .schedule-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            margin-bottom: 8px;
            transition: all 0.2s;
        }

        .schedule-item:hover {
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .schedule-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .schedule-time {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 5px;
            font-size: 14px;
        }

        .schedule-doctor {
            font-weight: 500;
            margin-bottom: 3px;
            font-size: 13px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.2);
            width: 85%;
            max-width: 500px;
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 22px;
            font-weight: 600;
            color: var(--dark);
        }

        .close {
            color: #7f8c8d;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: var(--dark);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
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

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .form-check input {
            margin-right: 8px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .calendar-view {
                flex-direction: column;
            }
        }

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
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .search-box, .filter-select {
                width: 100%;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .doctor-table {
                min-width: 600px;
            }
            
            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 5px;
            }
            
            .calendar-day {
                min-height: 100px;
                font-size: 12px;
                padding: 5px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .schedule-event {
                font-size: 10px;
                padding: 3px;
            }
        }

        @media (max-width: 576px) {
            .action-btns {
                flex-wrap: wrap;
            }
            
            .calendar-day-header, .calendar-day {
                font-size: 10px;
                padding: 3px;
            }
            
            .calendar-day {
                min-height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Welcome Admin</h1>
                <p>Healthcare Management System</p>
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
                    <a href="doctor_list.php" class="active">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="test_appoiment.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout_page.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-user-md"></i> Doctor Management</h2>
                <a href="registest_doctors.php" class="btn">
                    <i class="fas fa-plus"></i> Add New Doctor
                </a>
            </div>

            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon doctors">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo htmlspecialchars($total_doctors); ?></h3>
                        <p>Total Doctors</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon available">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo htmlspecialchars($available_doctors); ?></h3>
                        <p>Available Today</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon off-duty">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo htmlspecialchars($off_duty_doctors); ?></h3>
                        <p>Off Duty</p>
                    </div>
                </div>
            </div>

            <!-- Calendar View -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-calendar-alt"></i> Weekly Doctor Schedule - <?php echo date('F Y', $firstDayOfMonth); ?></h3>
                    <div class="calendar-nav">
                        <button id="prevMonth" data-month="<?php echo $prevMonth; ?>" data-year="<?php echo $prevYear; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <button id="todayBtn" class="btn">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                        <button id="nextMonth" data-month="<?php echo $nextMonth; ?>" data-year="<?php echo $nextYear; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="calendar-view">
                        <div class="calendar">
                            <div class="calendar-grid">
                                <?php foreach($daysOfWeek as $day): ?>
                                    <div class="calendar-day-header"><?php echo substr($day, 0, 3); ?></div>
                                <?php endforeach; ?>
                                
                                <?php foreach($daysOfWeek as $day): ?>
                                    <div class="calendar-day <?php echo strtolower($day) === $todayDayName ? 'today' : ''; ?>" data-day="<?php echo strtolower($day); ?>">
                                        <div class="calendar-day-number"><?php echo $day; ?></div>
                                        <?php
                                        $daySchedules = getSchedulesForDay($schedules, $day);
                                        foreach ($daySchedules as $schedule): 
                                        ?>
                                            <div class="schedule-event" title="<?php echo htmlspecialchars($schedule['doctorName'] . ' - ' . date('g:i A', strtotime($schedule['start_time'])) . ' to ' . date('g:i A', strtotime($schedule['end_time']))); ?>">
                                                <strong><?php echo htmlspecialchars($schedule['doctorName']); ?></strong><br>
                                                <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>-<?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="schedule-list">
                            <h3>Today's Schedules (<?php echo ucfirst($todayDayName); ?>)</h3>
                            <?php if (count($todays_schedules) > 0): ?>
                                <?php foreach($todays_schedules as $schedule): ?>
                                    <div class="schedule-item">
                                        <div class="schedule-time">
                                            <?php echo date('g:i A', strtotime($schedule['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                                        </div>
                                        <div class="schedule-doctor"> <?php echo htmlspecialchars($schedule['doctorName']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No doctor schedules for today.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctors List Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-list"></i> All Registered Doctors</h3>
                </div>
                <div class="panel-body">
                    <form method="GET" action="">
                        <input type="hidden" name="month" value="<?php echo $month; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                        <div class="search-container">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search doctors..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <select class="filter-select" name="status">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="available" <?php echo $status_filter == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="off-duty" <?php echo $status_filter == 'off-duty' ? 'selected' : ''; ?>>Off Duty</option>
                            </select>
                            <select class="filter-select" name="shift">
                                <option value="all" <?php echo $shift_filter == 'all' ? 'selected' : ''; ?>>All Shifts</option>
                                <option value="morning" <?php echo $shift_filter == 'morning' ? 'selected' : ''; ?>>Morning Shift</option>
                                <option value="afternoon" <?php echo $shift_filter == 'afternoon' ? 'selected' : ''; ?>>Afternoon Shift</option>
                                <option value="night" <?php echo $shift_filter == 'night' ? 'selected' : ''; ?>>Night Shift</option>
                            </select>
                            <button type="submit" class="btn">Apply Filters</button>
                        </div>
                    </form>
                    
                    <div class="table-container">
                        <table class="doctor-table">
                            <thead>
                                <tr>
                                    <th>Doctor</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Shift</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($doctorsResult->num_rows > 0) {
                                    while($doctor = $doctorsResult->fetch_assoc()) {
                                        $statusClass = $doctor['on_duty'] ? 'status-available' : 'status-off-duty';
                                        $statusText = $doctor['on_duty'] ? 'Available' : 'Off Duty';
                                        $shift = strtolower($doctor['shift'] ?? '');
                                        $shiftClass = '';
                                        switch($shift) {
                                            case 'afternoon':
                                                $shiftClass = 'shift-afternoon';
                                                break;
                                            case 'night':
                                                $shiftClass = 'shift-night';
                                                break;
                                            default:
                                                $shiftClass = 'shift-morning';
                                        }
                                        
                                        // Get first letter for avatar
                                        $firstLetter = strtoupper(substr($doctor['doctorName'], 0, 1));
                                ?>
                                <tr>
                                    <td>
                                        <div class="doctor-info">
                                            <div class="doctor-avatar"><?php echo $firstLetter; ?></div>
                                            <div>
                                                <div class="doctor-name"> <?php echo htmlspecialchars($doctor['doctorName']); ?></div>
                                                <div class="doctor-license">License: <?php echo htmlspecialchars($doctor['license_hash']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($doctor['email']); ?></div>
                                        <div><?php echo htmlspecialchars($doctor['phone']); ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <span class="shift-badge <?php echo $shiftClass; ?>"><?php echo ucfirst($shift); ?> Shift</span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctorId']; ?>">
                                                <button type="submit" name="toggle_duty" class="action-btn" title="<?php echo $doctor['on_duty'] ? 'Set Off Duty' : 'Set Available'; ?>">
                                                    <i class="fas <?php echo $doctor['on_duty'] ? 'fa-user-times' : 'fa-user-check'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctorId']; ?>">
                                                <button type="submit" name="edit_doctor" class="action-btn" title="Edit Doctor">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </form>
                                            
                                            <button class="action-btn schedule-btn" 
                                                    data-doctor-id="<?php echo $doctor['doctorId']; ?>"
                                                    data-doctor-name="<?php echo htmlspecialchars($doctor['doctorName']); ?>"
                                                    title="Manage Schedule">
                                                <i class="fas fa-calendar-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-user-md" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                                        <p>No doctors found matching your criteria.</p>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Schedule Management Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Manage Doctor Schedule</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="doctor_id" id="modal_doctor_id">
                <div class="form-group">
                    <label for="doctor_name">Doctor Name</label>
                    <input type="text" id="modal_doctor_name" readonly>
                </div>
                
                <div class="form-group">
                    <label for="day_of_week">Day of Week</label>
                    <select name="day_of_week" id="day_of_week" required>
                        <option value="">Select a day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" name="start_time" id="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" name="end_time" id="end_time" required>
                    </div>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" name="is_available" id="is_available" checked>
                    <label for="is_available">Available on this day</label>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="cancelBtn">Cancel</button>
                    <button type="submit" name="save_schedule" class="btn">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('scheduleModal');
        const scheduleBtns = document.querySelectorAll('.schedule-btn');
        const closeBtn = document.querySelector('.close');
        const cancelBtn = document.getElementById('cancelBtn');
        const modalDoctorId = document.getElementById('modal_doctor_id');
        const modalDoctorName = document.getElementById('modal_doctor_name');
        
        // Open modal when schedule button is clicked
        scheduleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const doctorId = this.getAttribute('data-doctor-id');
                const doctorName = this.getAttribute('data-doctor-name');
                
                modalDoctorId.value = doctorId;
                modalDoctorName.value = doctorName;
                modal.style.display = 'block';
            });
        });
        
        // Close modal
        function closeModal() {
            modal.style.display = 'none';
        }
        
        closeBtn.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        
        // Calendar navigation
        document.getElementById('prevMonth').addEventListener('click', function() {
            const month = this.getAttribute('data-month');
            const year = this.getAttribute('data-year');
            updateCalendar(month, year);
        });
        
        document.getElementById('nextMonth').addEventListener('click', function() {
            const month = this.getAttribute('data-month');
            const year = this.getAttribute('data-year');
            updateCalendar(month, year);
        });
        
        document.getElementById('todayBtn').addEventListener('click', function() {
            const now = new Date();
            updateCalendar(now.getMonth() + 1, now.getFullYear());
        });
        
        function updateCalendar(month, year) {
            const url = new URL(window.location.href);
            url.searchParams.set('month', month);
            url.searchParams.set('year', year);
            window.location.href = url.toString();
        }
        
        // Form validation for schedule times
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime && startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time.');
            }
        });
    </script>
</body>
</html>