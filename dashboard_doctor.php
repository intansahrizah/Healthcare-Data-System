<?php
// Start output buffering to prevent header errors
ob_start();

// Start session and check if doctor is logged in
session_start();

// Debug session data
error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['doctorId']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    error_log("Redirecting to login - Session missing or invalid");
    header("Location: login_page.php");
    exit();
}

$doctor_id = $_SESSION['doctorId'];

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

// Initialize variables with default values
$total_patients = 0;
$today_patients = 0;
$today_appointments = 0;
$pending_records = 0;
$recent_patients = [];
$upcoming_appointments = [];

try {
    // Fetch total patients count FOR THIS DOCTOR ONLY
    $sql_total = "SELECT COUNT(*) as total FROM patients WHERE doctorId = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $doctor_id);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    if ($result_total) {
        $total_patients = $result_total->fetch_assoc()['total'];
    }
    $stmt_total->close();

    // Fetch today's patients count FOR THIS DOCTOR ONLY
    $sql_today = "SELECT COUNT(*) as today FROM patients WHERE doctorId = ? AND DATE(created_at) = CURDATE()";
    $stmt_today = $conn->prepare($sql_today);
    $stmt_today->bind_param("i", $doctor_id);
    $stmt_today->execute();
    $result_today = $stmt_today->get_result();
    if ($result_today) {
        $today_patients = $result_today->fetch_assoc()['today'];
    }
    $stmt_today->close();

    // Fetch appointments for today FOR THIS DOCTOR ONLY
    $sql_appointments = "SELECT COUNT(*) as appointments FROM appointments WHERE doctorId = ? AND DATE(appointment_date) = CURDATE()";
    $stmt_appointments = $conn->prepare($sql_appointments);
    $stmt_appointments->bind_param("i", $doctor_id);
    $stmt_appointments->execute();
    $result_appointments = $stmt_appointments->get_result();
    if ($result_appointments) {
        $today_appointments = $result_appointments->fetch_assoc()['appointments'];
    }
    $stmt_appointments->close();

    // Fetch pending medical records
    $sql_pending = "SELECT COUNT(*) as pending FROM medical_records mr 
                   JOIN patients p ON mr.patient_id = p.patientsId 
                   WHERE p.doctorId = ? AND mr.status = 'pending'";
    $stmt_pending = $conn->prepare($sql_pending);
    $stmt_pending->bind_param("i", $doctor_id);
    $stmt_pending->execute();
    $result_pending = $stmt_pending->get_result();
    if ($result_pending) {
        $pending_records = $result_pending->fetch_assoc()['pending'];
    }
    $stmt_pending->close();

    // Fetch recent patients (last 5) FOR THIS DOCTOR ONLY
    $sql_recent = "SELECT patientName, ic_number, created_at, patientsId FROM patients WHERE doctorId = ? ORDER BY created_at DESC LIMIT 5";
    $stmt_recent = $conn->prepare($sql_recent);
    $stmt_recent->bind_param("i", $doctor_id);
    $stmt_recent->execute();
    $result_recent = $stmt_recent->get_result();
    if ($result_recent && $result_recent->num_rows > 0) {
        while($row = $result_recent->fetch_assoc()) {
            $recent_patients[] = $row;
        }
    }
    $stmt_recent->close();

    // Fetch upcoming appointments (next 3)
    $sql_upcoming = "SELECT a.*, p.patientName, p.ic_number 
                    FROM appointments a 
                    JOIN patients p ON a.patientsId = p.patientsId 
                    WHERE a.doctorId = ? AND a.appointment_date >= CURDATE() AND a.status IN ('Scheduled', 'Confirmed')
                    ORDER BY a.appointment_date, a.appointment_time 
                    LIMIT 3";
    $stmt_upcoming = $conn->prepare($sql_upcoming);
    $stmt_upcoming->bind_param("i", $doctor_id);
    $stmt_upcoming->execute();
    $result_upcoming = $stmt_upcoming->get_result();
    if ($result_upcoming && $result_upcoming->num_rows > 0) {
        while($row = $result_upcoming->fetch_assoc()) {
            $upcoming_appointments[] = $row;
        }
    }
    $stmt_upcoming->close();

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --primary-light: #ebf5fb;
            --secondary: #2c3e50;
            --success: #27ae60;
            --success-light: #d5f4e6;
            --warning: #f39c12;
            --warning-light: #fef5e7;
            --danger: #e74c3c;
            --danger-light: #fdedec;
            --info: #1abc9c;
            --info-light: #e8f6f3;
            --light: #f5f7fa;
            --dark: #333;
            --white: #ffffff;
            --gray: #95a5a6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
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

        /* Sidebar Styles - UNCHANGED */
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

        /* Main Content Styles - ENHANCED */
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
            padding: 1.5rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
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

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn {
            background: var(--primary);
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            box-shadow: var(--shadow);
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--success);
        }

        .btn-success:hover {
            background: #219653;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }

        .welcome-banner h1 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            position: relative;
        }

        .welcome-banner p {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
        }

        .banner-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1.5rem;
            position: relative;
        }

        .banner-stat {
            text-align: center;
        }

        .banner-stat .number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }

        .banner-stat .label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Enhanced Statistics Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.patients { border-left-color: var(--primary); }
        .stat-card.today { border-left-color: var(--success); }
        .stat-card.appointments { border-left-color: var(--warning); }
        .stat-card.records { border-left-color: var(--info); }

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
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }

        .stat-icon.today {
            background: linear-gradient(135deg, var(--success), #219653);
        }

        .stat-icon.appointments {
            background: linear-gradient(135deg, var(--warning), #e67e22);
        }

        .stat-icon.records {
            background: linear-gradient(135deg, var(--info), #16a085);
        }

        .stat-info h3 {
            font-size: 28px;
            margin-bottom: 5px;
            color: var(--dark);
            font-weight: 700;
        }

        .stat-info p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .stat-trend {
            font-size: 12px;
            font-weight: 500;
        }

        .trend-up { color: var(--success); }
        .trend-down { color: var(--danger); }

        /* Dashboard Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 2rem;
        }

        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Enhanced Dashboard Sections */
        .dashboard-section {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .dashboard-section:hover {
            box-shadow: var(--shadow-lg);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light);
        }

        .section-header h3 {
            font-size: 1.3rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        .section-header h3 i {
            color: var(--primary);
        }

        /* Enhanced Recent Patients Table */
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
        }

        .patient-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patient-table th {
            background-color: var(--primary-light);
            text-align: left;
            padding: 15px;
            font-weight: 600;
            color: var(--secondary);
            border-bottom: 2px solid var(--primary);
        }

        .patient-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .patient-table tr:hover {
            background-color: var(--light);
        }

        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }

        .patient-info {
            display: flex;
            align-items: center;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn.view {
            background: var(--info);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        /* Upcoming Appointments */
        .appointment-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .appointment-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: var(--light);
            border-radius: 10px;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }

        .appointment-item:hover {
            background: var(--primary-light);
            transform: translateX(5px);
        }

        .appointment-time {
            background: var(--primary);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            min-width: 80px;
            margin-right: 15px;
        }

        .appointment-time .time {
            font-size: 1.1rem;
            font-weight: bold;
            display: block;
        }

        .appointment-time .date {
            font-size: 0.8rem;
            opacity: 0.9;
        }

        .appointment-details h4 {
            margin-bottom: 5px;
            color: var(--secondary);
        }

        .appointment-details p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 1.5rem;
        }

        .quick-action-btn {
            background: var(--white);
            border: 2px solid var(--light);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: var(--secondary);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .quick-action-btn:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow);
        }

        .quick-action-btn i {
            font-size: 2rem;
            color: var(--primary);
        }

        .quick-action-btn span {
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Chart Container */
        .chart-container {
            background: var(--white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            height: 300px;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        .no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-data h3 {
            margin-bottom: 10px;
            color: var(--secondary);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease-out;
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
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .welcome-banner h1 {
                font-size: 1.5rem;
            }
            
            .banner-stats {
                flex-direction: column;
                gap: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation - UNCHANGED -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Dr. <?php echo htmlspecialchars($_SESSION['doctor_name']); ?></h1>
                <p>Healthcare Management System</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard_doctor.php" class="active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_appoinment.php">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_calender.php">
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

        <!-- Main Content Area - ENHANCED -->
        <main class="main-content">
            <!-- Header -->
            <div class="header fade-in">
                <h2><i class="fas fa-tachometer-alt"></i> Doctor Dashboard</h2>
                <div class="header-actions">
                    <a href="patients_register.php" class="btn">
                        <i class="fas fa-plus"></i> Add New Patient
                    </a>
                </div>
            </div>

            <!-- Welcome Banner -->
            <div class="welcome-banner fade-in">
                <h1>Welcome back, Dr. <?php echo htmlspecialchars($_SESSION['doctor_name']); ?>! 👋</h1>
                <p>Here's what's happening with your practice today.</p>
                <div class="banner-stats">
                    <div class="banner-stat">
                        <span class="number"><?php echo $today_appointments; ?></span>
                        <span class="label">Today's Appointments</span>
                    </div>
                    <div class="banner-stat">
                        <span class="number"><?php echo $today_patients; ?></span>
                        <span class="label">New Patients Today</span>
                    </div>
                    <div class="banner-stat">
                        <span class="number"><?php echo $pending_records; ?></span>
                        <span class="label">Pending Records</span>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-container fade-in">
                <div class="stat-card patients">
                    <div class="stat-icon patients">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_patients; ?></h3>
                        <p>Total Patients</p>
                        <span class="stat-trend trend-up">+<?php echo $today_patients; ?> today</span>
                    </div>
                </div>

                <div class="stat-card today">
                    <div class="stat-icon today">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $today_patients; ?></h3>
                        <p>New Patients Today</p>
                        <span class="stat-trend trend-up">Active</span>
                    </div>
                </div>

                <div class="stat-card appointments">
                    <div class="stat-icon appointments">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $today_appointments; ?></h3>
                        <p>Today's Appointments</p>
                        <span class="stat-trend trend-up">Scheduled</span>
                    </div>
                </div>

                <div class="stat-card records">
                    <div class="stat-icon records">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $pending_records; ?></h3>
                        <p>Pending Records</p>
                        <span class="stat-trend trend-down">Needs attention</span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Left Column -->
                <div class="left-column">
                    <!-- Recent Patients Section -->
                    <div class="dashboard-section fade-in">
                        <div class="section-header">
                            <h3><i class="fas fa-clock"></i> Recently Added Patients</h3>
                            <a href="doctor_appoinment.php" class="btn">
                                <i class="fas fa-list"></i> View All
                            </a>
                        </div>

                        <div class="table-container">
                            <?php if (count($recent_patients) > 0): ?>
                                <table class="patient-table">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>IC Number</th>
                                            <th>Date Added</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_patients as $patient): ?>
                                            <tr>
                                                <td>
                                                    <div class="patient-info">
                                                        <div class="patient-avatar">
                                                            <?php echo strtoupper(substr($patient['patientName'], 0, 1)); ?>
                                                        </div>
                                                        <?php echo htmlspecialchars($patient['patientName']); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($patient['ic_number']); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                                                <td>
                                                    <a href="add_medical.php?patientId=<?php echo $patient['patientsId']; ?>" class="action-btn view" title="View Medical Records">
                                                        <i class="fas fa-file-medical"></i> Records
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
                                    <p>No patient records found for your account.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>


                <!-- Right Column -->
                <div class="right-column">
                    <!-- Upcoming Appointments -->
                    <div class="dashboard-section fade-in">
                        <div class="section-header">
                            <h3><i class="fas fa-calendar-alt"></i> Upcoming Appointments</h3>
                            <a href="doctor_calender.php" class="btn btn-success">
                                <i class="fas fa-eye"></i> View Calendar
                            </a>
                        </div>

                        <div class="appointment-list">
                            <?php if (count($upcoming_appointments) > 0): ?>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-time">
                                            <span class="time"><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></span>
                                            <span class="date"><?php echo date('M j', strtotime($appointment['appointment_date'])); ?></span>
                                        </div>
                                        <div class="appointment-details">
                                            <h4><?php echo htmlspecialchars($appointment['patientName']); ?></h4>
                                            <p><?php echo htmlspecialchars($appointment['reason']); ?></p>
                                            <small>IC: <?php echo htmlspecialchars($appointment['ic_number']); ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-calendar-times"></i>
                                    <h3>No Upcoming Appointments</h3>
                                    <p>Schedule new appointments to see them here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-section fade-in">
                        <div class="section-header">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="quick-actions">
                            <a href="patients_register.php" class="quick-action-btn">
                                <i class="fas fa-user-plus"></i>
                                <span>Add Patient</span>
                            </a>
                            <a href="doctor_calender.php" class="quick-action-btn">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Schedule</span>
                            </a>
                            <a href="doctor_appoinment.php" class="quick-action-btn">
                                <i class="fas fa-list"></i>
                                <span>All Patients</span>
                            </a>
                            <a href="add_medical.php" class="quick-action-btn">
                                <i class="fas fa-file-medical"></i>
                                <span>Medical Records</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Patient Growth Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('patientGrowthChart').getContext('2d');
            const patientGrowthChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'New Patients',
                        data: [12, 19, 15, 25, 22, 30, 28, 35, 32, 40, 38, 45],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: {
                                size: 14
                            },
                            bodyFont: {
                                size: 13
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });

            // Add fade-in animation to all dashboard sections
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });

            // Auto-refresh dashboard every 5 minutes
            setTimeout(() => {
                window.location.reload();
            }, 300000); // 5 minutes
        });

        // Simple notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 1000;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }

        // Check if there are pending records and show notification
        <?php if ($pending_records > 0): ?>
        setTimeout(() => {
            showNotification('You have <?php echo $pending_records; ?> pending medical records that need attention.', 'warning');
        }, 2000);
        <?php endif; ?>

        <?php if ($today_appointments > 0): ?>
        setTimeout(() => {
            showNotification('You have <?php echo $today_appointments; ?> appointments scheduled for today.', 'info');
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php
// Flush the output buffer
ob_end_flush();
?>