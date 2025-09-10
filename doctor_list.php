<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            background: url('https://gov-web-sing.s3.ap-southeast-1.amazonaws.com/uploads/2023/1/Wordpress-featured-images-48-1672795987342.jpg') no-repeat center center fixed;
            color: #333;
            background-size: cover;
        }

        .header {
            background-color: #1a75bc;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
        }

        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            padding: 20px 0;
        }

        .logo-sidebar {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .logo-sidebar h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .logo-sidebar p {
            font-size: 14px;
            opacity: 0.8;
        }

        .nav-menu {
            list-style: none;
            padding: 0 20px;
        }

        .nav-item {
            margin-bottom: 15px;
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
            background-color: #3498db;
        }

        .nav-item i {
            margin-right: 10px;
            font-size: 18px;
        }

        .content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .page-title {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
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
            background-color: var(--danger);
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

        .panel {
            background: white;
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

        .doctors-table {
            width: 100%;
            border-collapse: collapse;
        }

        .doctors-table th {
            background-color: var(--secondary);
            color: white;
            text-align: left;
            padding: 15px;
            font-weight: 500;
        }

        .doctors-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .doctors-table tr:hover {
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

        .action-btns button {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 16px;
            transition: color 0.3s;
        }

        .action-btns button:hover {
            color: var(--primary-dark);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-available {
            background-color: #eaf7ee;
            color: var(--success);
        }

        .status-off-duty {
            background-color: #fde8e6;
            color: var(--danger);
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .day-column {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        .day-header {
            text-align: center;
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
        }

        .time-slot {
            background-color: white;
            padding: 8px;
            border-radius: 5px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            font-size: 14px;
        }

        .time-slot.available {
            border-left: 4px solid var(--success);
        }

        .time-slot.booked {
            border-left: 4px solid var(--danger);
        }

        .doctor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            color: white;
            font-size: 16px;
        }

        .doctor-info-cell {
            display: flex;
            align-items: center;
        }

        .shift-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
            margin-top: 5px;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            width: 80%;
            max-width: 600px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .schedule-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 1024px) {
            .schedule-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
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
            
            .doctors-table {
                display: block;
                overflow-x: auto;
            }
            
            .schedule-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .schedule-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
    
    // Handle toggle duty status
    if (isset($_POST['toggle_duty']) && isset($_POST['doctor_id'])) {
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
        header("Location: doctor_list.php");
        exit();
    }
    
    // Handle schedule management
    if (isset($_POST['save_schedule'])) {
        $doctor_id = $_POST['doctor_id'];
        $day_of_week = $_POST['day_of_week'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Check if schedule already exists
        $checkStmt = $conn->prepare("SELECT id FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?");
        $checkStmt->bind_param("is", $doctor_id, $day_of_week);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
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
        header("Location: doctor_list.php");
        exit();
    }
    
    // Get search and filter parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $shift_filter = isset($_GET['shift']) ? $_GET['shift'] : 'all';
    
    // Build SQL query with filters using prepared statements
    $sql = "SELECT * FROM doctors WHERE 1=1";
    $params = array();
    $types = '';

    if (!empty($search)) {
        $sql .= " AND (doctorName LIKE ? OR specialty LIKE ? OR email LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= 'sss';
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

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    
    // Get statistics
    $total_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors")->fetch_assoc()['count'];
    $available_doctors = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE on_duty = 1")->fetch_assoc()['count'];
    $off_duty_doctors = $total_doctors - $available_doctors;
    
    // Get schedule data based on doctors' availability
    $scheduleData = [];
    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    // Handle week offset from URL
    $weekOffset = isset($_GET['week_offset']) ? (int)$_GET['week_offset'] : 0;
    $weekStart = date('Y-m-d', strtotime('monday this week' . ($weekOffset >= 0 ? " + $weekOffset weeks" : " $weekOffset weeks")));
    
    for ($i = 0; $i < 7; $i++) {
        $currentDate = date('Y-m-d', strtotime($weekStart . " + $i days"));
        $dayName = $daysOfWeek[$i];
        $formattedDate = date('M j', strtotime($currentDate));
        
        $scheduleData[$i] = [
            'day' => $dayName,
            'date' => $formattedDate,
            'slots' => []
        ];
    }
    
    // Get doctors and their typical working hours based on shift
    $doctorSchedules = [];
    $doctorsResult = $conn->query("SELECT * FROM doctors WHERE on_duty = 1");
    
    if ($doctorsResult->num_rows > 0) {
        while($doctor = $doctorsResult->fetch_assoc()) {
            // Assign time slots based on shift
            $timeSlots = [];
            $shift = isset($doctor['shift']) ? strtolower($doctor['shift']) : '';
            switch($shift) {
                case 'morning':
                    $timeSlots = ['9:00 AM', '10:00 AM', '11:00 AM'];
                    break;
                case 'afternoon':
                    $timeSlots = ['1:00 PM', '2:00 PM', '3:00 PM', '4:00 PM'];
                    break;
                case 'night':
                    $timeSlots = ['6:00 PM', '7:00 PM', '8:00 PM'];
                    break;
                default:
                    $timeSlots = ['10:00 AM', '2:00 PM']; // Default slots
            }
            
            // Randomly assign some availability throughout the week
            foreach ($daysOfWeek as $dayIndex => $day) {
                // Doctors are more likely to be available on weekdays
                $available = ($dayIndex < 5) ? rand(0, 1) : rand(0, 2); // Less available on weekends
                
                if ($available === 1) {
                    foreach ($timeSlots as $timeSlot) {
                        // Randomly mark some slots as booked
                        $status = rand(0, 3) === 0 ? 'booked' : 'available';
                        
                        $scheduleData[$dayIndex]['slots'][] = [
                            'time' => $timeSlot,
                            'doctor' => $doctor['doctorName'],
                            'specialty' => $doctor['specialty'],
                            'status' => $status
                        ];
                    }
                }
            }
        }
    }
    
    // Sort slots by time for each day
    foreach ($scheduleData as &$day) {
        if (!empty($day['slots'])) {
            usort($day['slots'], function($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });
        }
    }
    ?>
    
    <div class="main-container">
        <aside class="sidebar">
            <div class="logo-sidebar">
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
                    <a href="#" class="active">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="test_appoiment.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
            </ul>
        </aside>
        
        <div class="content">
            <h1 class="page-title"><i class="fas fa-user-md"></i> Doctor Management</h1>
            
            <!-- Statistics Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon doctors">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="total-doctors"><?php echo $total_doctors; ?></h3>
                        <p>Total Doctors</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon available">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="available-doctors"><?php echo $available_doctors; ?></h3>
                        <p>Available Today</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon off-duty">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="off-duty-doctors"><?php echo $off_duty_doctors; ?></h3>
                        <p>Off Duty</p>
                    </div>
                </div>
            </div>
            
            <!-- Doctors List Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-list"></i> All Registered Doctors</h3>
                    <a href="registest_doctors.php" class="btn">
                        <i class="fas fa-plus"></i> Add New Doctor
                    </a>
                </div>
                <div class="panel-body">
                    <form method="GET" action="">
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
                    
                    <table class="doctors-table">
                        <thead>
                            <tr>
                                <th>Doctor</th>
                                <th>Specialty</th>
                                <th>Shift</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="doctors-table-body">
                            <?php
                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    // Determine shift class
                                    $shiftClass = 'shift-morning';
                                    if (!empty($row['shift'])) {
                                        $shift = strtolower($row['shift'] ?? '');
                                        if ($shift == 'afternoon') $shiftClass = 'shift-afternoon';
                                        if ($shift == 'night') $shiftClass = 'shift-night';
                                    }
                                    
                                    echo "<tr>
                                        <td>
                                            <div class='doctor-info-cell'>
                                                <div class='doctor-avatar'>
                                                    <i class='fas fa-user-md'></i>
                                                </div>
                                                <div>
                                                    <div>{$row['doctorName']}</div>
                                                    <div style='font-size: 12px; color: #777;'>License: {$row['license_hash']}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{$row['specialty']}</td>
                                        <td>
                                            <span class='shift-badge {$shiftClass}'>" . (!empty($row['shift']) ? $row['shift'] : 'Not Set') . "</span>
                                        </td>
                                        <td>{$row['email']}<br>{$row['phone']}</td>
                                        <td>
                                            <span class='status-badge " . ($row['on_duty'] ? 'status-available' : 'status-off-duty') . "'>
                                                " . ($row['on_duty'] ? 'Available' : 'Off Duty') . "
                                            </span>
                                        </td>
                                        <td class='action-btns'>
                                            <a href='#' title='View Profile'><i class='fas fa-eye'></i></a>
                                            <a href='#' title='Edit'><i class='fas fa-edit'></i></a>
                                            <a href='#' title='Schedule' onclick='openScheduleModal({$row['doctorId']}, \"{$row['doctorName']}\"); return false;'><i class='fas fa-calendar-alt'></i></a>
                                            <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to change this doctor's availability?\");'>
                                                <input type='hidden' name='doctor_id' value='{$row['doctorId']}'>
                                                <input type='hidden' name='toggle_duty' value='1'>
                                                <button type='submit' title='" . ($row['on_duty'] ? 'Set Off Duty' : 'Set On Duty') . "'>
                                                    <i class='fas " . ($row['on_duty'] ? 'fa-user-times' : 'fa-user-check') . "'></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align: center;'>No doctors found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Weekly Schedule Panel -->
            <div class="panel">
                <div class="panel-header">
                    <h3><i class="fas fa-calendar-alt"></i> Weekly Schedule</h3>
                    <div>
                        <button class="btn" id="prev-week"><i class="fas fa-chevron-left"></i> Previous Week</button>
                        <button class="btn" id="next-week">Next Week <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="schedule-grid" id="schedule-grid">
                        <?php
                        foreach ($scheduleData as $day) {
                            echo "<div class='day-column'>
                                <div class='day-header'>{$day['day']}<br>{$day['date']}</div>";
                            
                            if (empty($day['slots'])) {
                                echo "<div class='time-slot' style='text-align: center; padding: 20px 10px; color: #777;'>
                                    <i class='fas fa-calendar-times'></i><br>
                                    No schedules
                                </div>";
                            } else {
                                foreach ($day['slots'] as $slot) {
                                    $statusClass = $slot['status'];
                                    $statusIcon = $slot['status'] === 'available' ? 'fa-check-circle' : 'fa-calendar-times';
                                    
                                    echo "<div class='time-slot {$statusClass}'>
                                        <strong>{$slot['time']}</strong><br>
                                        <div style='font-weight: 500;'>{$slot['doctor']}</div>
                                        <div style='font-size: 12px; color: #666;'>{$slot['specialty']}</div>
                                        <div style='font-size: 11px; margin-top: 5px;'>
                                            <i class='fas {$statusIcon}'></i> 
                                            " . ucfirst($slot['status']) . "
                                        </div>
                                    </div>";
                                }
                            }
                            
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Management Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Manage Doctor Schedule</h2>
            <form method="POST" action="">
                <input type="hidden" name="doctor_id" id="modalDoctorId">
                <input type="hidden" name="save_schedule" value="1">
                
                <div class="form-group">
                    <label for="day_of_week">Day of Week</label>
                    <select name="day_of_week" id="day_of_week" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                
                <div class="schedule-form">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" name="start_time" id="start_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" name="end_time" id="end_time" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_available" id="is_available" value="1" checked>
                        Available on this day
                    </label>
                </div>
                
                <button type="submit" class="btn">Save Schedule</button>
            </form>
        </div>
    </div>

    <script>
        // Week navigation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('prev-week').addEventListener('click', function() {
                alert('Previous week clicked. In a real application, this would load the previous week\'s schedule.');
            });
            
            document.getElementById('next-week').addEventListener('click', function() {
                alert('Next week clicked. In a real application, this would load the next week\'s schedule.');
            });
            
            // Modal functionality
            var modal = document.getElementById("scheduleModal");
            var span = document.getElementsByClassName("close")[0];
            
            span.onclick = function() {
                modal.style.display = "none";
            }
            
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        });
        
        function openScheduleModal(doctorId, doctorName) {
            document.getElementById("modalDoctorId").value = doctorId;
            document.getElementById("modalTitle").innerText = "Manage Schedule for Dr. " + doctorName;
            document.getElementById("scheduleModal").style.display = "block";
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>