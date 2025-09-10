<?php
// Start output buffering to prevent header errors
ob_start();

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

// Fetch total patients count
$sql_total = "SELECT COUNT(*) as total FROM patients";
$result_total = $conn->query($sql_total);
$total_patients = $result_total->fetch_assoc()['total'];

// Fetch today's patients count
$sql_today = "SELECT COUNT(*) as today FROM patients WHERE DATE(created_at) = CURDATE()";
$result_today = $conn->query($sql_today);
$today_patients = $result_today->fetch_assoc()['today'];

// Fetch appointments for today
$sql_appointments = "SELECT COUNT(*) as appointments FROM appointments WHERE DATE(appointment_date) = CURDATE()";
$result_appointments = $conn->query($sql_appointments);
$today_appointments = $result_appointments->fetch_assoc()['appointments'];

// Fetch recent patients (last 5)
$sql_recent = "SELECT patientName, ic_number, created_at FROM patients ORDER BY created_at DESC LIMIT 5";
$result_recent = $conn->query($sql_recent);
$recent_patients = [];
if ($result_recent->num_rows > 0) {
    while($row = $result_recent->fetch_assoc()) {
        $recent_patients[] = $row;
    }
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
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #f5f7fa;
            --dark: #333;
            --white: #ffffff;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --info: #1abc9c;
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

        .appointment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

       .appointment-table th{
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
       } 
        .appointment-table td {
            padding: 10px;
            text-align: center;
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

        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-icon.appointments {
            background-color: var(--warning);
        }

        .stat-icon.records {
            background-color: var(--info);
        }

        .stat-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
            color: var(--dark);
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 14px;
        }

        /* Dashboard Sections */
        .dashboard-section {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .section-header h3 {
            font-size: 1.2rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            color: var(--primary);
        }

        /* Recent Patients Table */
        .table-container {
            overflow-x: auto;
        }

        .patient-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patient-table th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px 15px;
            font-weight: 500;
            color: var(--secondary);
            border-bottom: 2px solid #eee;
        }

        .patient-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .patient-table tr:hover {
            background-color: #f5f5f5;
        }

        .no-data {
            text-align: center;
            padding: 2rem;
            color: #7f8c8d;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
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
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Welcome Doctor</h1>
                <p>Patient Management System </p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doc_listpatient.php">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_calender.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-tachometer-alt"></i> Doctor Dashboard</h2>
                <a href="patients_register.php" class="btn">
                    <i class="fas fa-plus"></i> Add New Patient
                </a>
            </div>

            <!-- Statistics Cards -->
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
                        <p>New Patients Today</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon appointments">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $today_appointments; ?></h3>
                        <p>Today's Appointments</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon records">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div class="stat-info">
                        <h3>0</h3>
                        <p>Pending Records</p>
                    </div>
                </div>
            </div>

            <!-- Recent Patients Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-clock"></i> Recently Added Patients</h3>
                    <a href="patient_list.php" class="btn">
                        <i class="fas fa-list"></i> View All
                    </a>
                </div>

                <div class="table-container">
                    <table class="patient-table">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>IC Number</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_patients) > 0): ?>
                                <?php foreach ($recent_patients as $patient): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($patient['patientName']); ?></td>
                                        <td><?php echo htmlspecialchars($patient['ic_number']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($patient['created_at'])); ?></td>
                                        <td>
                                            <a href="add_medical.php?patientName=<?php echo urlencode($patient['patientName']); ?>" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
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
            </div>

            <!-- Upcoming Appointments Section -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h3><i class="fas fa-calendar-alt"></i> Upcoming Appointments</h3>
                    <a href="test_appoiment.php" class="btn">
                        <i class="fas fa-calendar-plus"></i> Schedule New
                    </a>
                </div>
                
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
                </table>
                
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Upcoming Appointments</h3>
                    <p>There are no appointments scheduled for today.</p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php
// Flush the output buffer
ob_end_flush();
?>