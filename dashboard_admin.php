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

// Fetch total patients count
$total_patients_sql = "SELECT COUNT(*) as total FROM patients";
$total_patients_result = $conn->query($total_patients_sql);
$total_patients = $total_patients_result->fetch_assoc()['total'];

// Fetch today's patients count
$today = date('Y-m-d');
$today_patients_sql = "SELECT COUNT(*) as today_count FROM patients WHERE DATE(created_at) = '$today'";
$today_patients_result = $conn->query($today_patients_sql);
$today_patients = $today_patients_result->fetch_assoc()['today_count'];

// Fetch all doctors with their schedules
$doctors_sql = "SELECT d.doctorId, d.doctorName, d.shift, d.on_duty, 
                ds.day_of_week, ds.start_time, ds.end_time, ds.is_available
                FROM doctors d 
                LEFT JOIN doctor_schedules ds ON d.doctorId = ds.doctor_id 
                WHERE ds.is_available = 1 OR ds.is_available IS NULL
                ORDER BY d.on_duty DESC, d.doctorName";
$doctors_result = $conn->query($doctors_sql);
$doctors = [];
$doctor_schedules = [];

if ($doctors_result->num_rows > 0) {
    while($row = $doctors_result->fetch_assoc()) {
        $doctorId = $row['doctorId'];
        
        // Group schedules by doctor
        if (!isset($doctor_schedules[$doctorId])) {
            $doctor_schedules[$doctorId] = [
                'doctorId' => $row['doctorId'],
                'doctorName' => $row['doctorName'],
                'shift' => $row['shift'],
                'on_duty' => $row['on_duty'],
                'schedules' => []
            ];
        }
        
        // Add schedule if available
        if ($row['day_of_week'] && $row['is_available']) {
            $doctor_schedules[$doctorId]['schedules'][] = [
                'day_of_week' => $row['day_of_week'],
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
        }
    }
    
    // Convert to simple array for display
    $doctors = array_values($doctor_schedules);
}

// Get today's day name
$todayDayName = strtolower(date('l')); // e.g., "monday"

// Function to check if doctor is scheduled for today
function isDoctorScheduledToday($schedules, $todayDayName) {
    foreach ($schedules as $schedule) {
        if (strtolower($schedule['day_of_week']) === $todayDayName) {
            // Simple approach - if scheduled today, show as ON DUTY for entire day
            return true;
        }
    }
    return false;
}

// Fetch patients from database
$sql = "SELECT patientName, ic_number, gender, email, phone FROM patients ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
$patients = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}
$conn->close();
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

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--success);
        }

        input:checked + .slider:before {
            transform: translateX(30px);
        }

        /* Dashboard Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
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

        .stat-icon.patients {
            background-color: var(--primary);
        }

        .stat-icon.today {
            background-color: var(--success);
        }

        .stat-icon.doctors {
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

        /* Doctors List */
        .doctors-list {
            list-style: none;
        }

        .doctor-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .doctor-item:last-child {
            border-bottom: none;
        }

        .doctor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 20px;
        }

        .doctor-info {
            flex: 1;
        }

        .doctor-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--secondary);
        }

        .doctor-specialty {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .doctor-shift {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .shift-morning {
            background-color: #eaf7ee;
            color: var(--success);
        }

        .shift-afternoon {
            background-color: #fef5e6;
            color: var(--warning);
        }

        .shift-night {
            background-color: #fde8e6;
            color: var(--danger);
        }

        .duty-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }

        .status-on {
            background-color: #eaf7ee;
            color: var(--success);
        }

        .status-off {
            background-color: #fde8e6;
            color: var(--danger);
        }

        .duty-toggle {
            display: flex;
            gap: 10px;
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

        .patient-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patient-table th {
            background-color: var(--secondary);
            color: white;
            text-align: left;
            padding: 15px;
            font-weight: 500;
        }

        .patient-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .patient-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .action-btns a {
            color: var(--primary);
            transition: color 0.3s;
        }

        .action-btns a:hover {
            color: var(--primary-dark);
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .pagination button:hover {
            background-color: var(--primary-dark);
        }

        .pagination button.active {
            background-color: var(--secondary);
        }

        /* Login Notice */
        .login-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-notice i {
            color: #f39c12;
            font-size: 20px;
        }

        /* Schedule Info Styles */
        .doctor-schedule {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
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
            
            .patient-table {
                min-width: 600px;
            }
            
            .duty-toggle {
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
                <h1>Welcome Admin</h1>
                <p>Healthcare Management System</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard_admin.php" class="active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_list.php">
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
                <li class="nav-item">
                    <a href="logout_page.php">
                        <i class="fas fa-sign-in-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
                <a href="patients_register.php" class="btn">
                    <i class="fas fa-plus"></i> Add New Patient
                </a>
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

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon patients">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_patients; ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon today">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $today_patients; ?></h3>
                        <p>Patients Added Today</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon doctors">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php 
                            $scheduled_today_count = 0;
                            foreach ($doctors as $doctor) {
                                if (isDoctorScheduledToday($doctor['schedules'], $todayDayName)) {
                                    $scheduled_today_count++;
                                }
                            }
                            echo $scheduled_today_count; 
                        ?></h3>
                        <p>Doctors Scheduled Today</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Doctors on Duty Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-user-md"></i> Manage Doctors on Duty (Based on Today's Schedule)</h3>
                    </div>
                    <div class="panel-body">
                        <?php if (count($doctors) > 0): ?>
                            <ul class="doctors-list">
                                <?php foreach ($doctors as $doctor): 
                                    $shift_class = 'shift-morning'; // Default class
                                    if (isset($doctor['shift'])) {
                                        if (strpos(strtolower($doctor['shift']), 'afternoon') !== false) {
                                            $shift_class = 'shift-afternoon';
                                        } elseif (strpos(strtolower($doctor['shift']), 'night') !== false) {
                                            $shift_class = 'shift-night';
                                        }
                                    }
                                    
                                    // Check if doctor is scheduled for today
                                    $isScheduledToday = isDoctorScheduledToday($doctor['schedules'], $todayDayName);
                                    $dutyStatus = $isScheduledToday ? 'ON DUTY' : 'OFF DUTY';
                                    $dutyStatusClass = $isScheduledToday ? 'status-on' : 'status-off';
                                ?>
                                <li class="doctor-item">
                                    <div class="doctor-avatar">
                                        <i class="fas fa-user-md"></i>
                                    </div>
                                    <div class="doctor-info">
                                        <div class="doctor-name">
                                            <?php echo htmlspecialchars($doctor['doctorName']); ?>
                                            <span class="duty-status <?php echo $dutyStatusClass; ?>">
                                                <?php echo $dutyStatus; ?>
                                            </span>
                                        </div>
                                        <?php if (isset($doctor['shift'])): ?>
                                            <span class="doctor-shift <?php echo $shift_class; ?>">
                                                <?php echo htmlspecialchars($doctor['shift']); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Display today's schedule if available -->
                                        <?php 
                                        $todaySchedule = null;
                                        foreach ($doctor['schedules'] as $schedule) {
                                            if (strtolower($schedule['day_of_week']) === $todayDayName) {
                                                $todaySchedule = $schedule;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($todaySchedule): ?>
                                            <div class="doctor-schedule">
                                                <i class="fas fa-clock"></i> 
                                                Today: <?php echo date('g:i A', strtotime($todaySchedule['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($todaySchedule['end_time'])); ?>
                                            </div>
                                        <?php elseif ($doctor['schedules']): ?>
                                            <div class="doctor-schedule" style="color: #999;">
                                                <i class="fas fa-calendar-times"></i> Not scheduled today
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-user-md"></i>
                                <h3>No Doctors Found</h3>
                                <p>No doctor records found in the database.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Patients Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h3><i class="fas fa-user-injured"></i> Recent Patients</h3>
                        <a href="patient_list.php" class="btn">
                            <i class="fas fa-list"></i> View All
                        </a>
                    </div>
                    <div class="panel-body">
                        <?php if (count($patients) > 0): ?>
                            <table class="patient-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>IC Number</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($patient['patientName']); ?></td>
                                            <td><?php echo htmlspecialchars($patient['ic_number']); ?></td>
                                            <td class="action-btns">
                                                <a href="patient_detail.php?patientName=<?php echo $patient['patientName']; ?>" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-user-slash"></i>
                                <h3>No Patients Found</h3>
                                <p>No patient records found in the database.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Full Patient List -->
            <div class="header">
                <h2><i class="fas fa-user-injured"></i> All Patients</h2>
            </div>

            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search patients...">
                </div>
                <select class="filter-select" id="rowsPerPage">
                    <option value="5">5 rows per page</option>
                    <option value="10" selected>10 rows per page</option>
                    <option value="20">20 rows per page</option>
                    <option value="50">50 rows per page</option>
                </select>
            </div>

            <div class="table-container">
                <table class="patient-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>IC Number</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="patientTableBody">
                        <?php if (count($patients) > 0): ?>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['patientName']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['ic_number']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td class="action-btns">
                                        <a href="patients_details.php?patientName=<?php echo $patient['patientName']; ?>" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="delete_patient.php?patientName=<?php echo $patient['patientName']; ?>" title="Delete" 
                                           onclick="return confirm('Are you sure you want to delete this record?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="no-data">
                                        <i class="fas fa-user-slash"></i>
                                        <h3>No Patients Found</h3>
                                        <p>No patient records found in the database.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination">
                <button id="prevBtn">&laquo; Previous</button>
                <button class="active">1</button>
                <button>2</button>
                <button>3</button>
                <button id="nextBtn">Next &raquo;</button>
            </div>
        </main>
    </div>

    <script>
        // Simple search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#patientTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Rows per page functionality
        document.getElementById('rowsPerPage').addEventListener('change', function() {
            alert('In a real application, this would refresh the page with the selected number of rows per page.');
        });

        // Pagination functionality
        document.getElementById('prevBtn').addEventListener('click', function() {
            alert('In a real application, this would go to the previous page.');
        });

        document.getElementById('nextBtn').addEventListener('click', function() {
            alert('In a real application, this would go to the next page.');
        });

        // Simple pagination buttons
        const paginationButtons = document.querySelectorAll('.pagination button');
        paginationButtons.forEach(button => {
            button.addEventListener('click', function() {
                paginationButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>