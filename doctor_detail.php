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

// Check if doctorId is provided
if (!isset($_GET['doctorId']) || empty($_GET['doctorId'])) {
    die("Invalid doctor ID");
}

$doctorId = intval($_GET['doctorId']);

// Fetch doctor details including specialty
$sql = "SELECT * FROM doctors WHERE doctorId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Doctor not found");
}

$doctor = $result->fetch_assoc();
$stmt->close();

// Fetch doctor's appointments
$appointment_stmt = $conn->prepare("
    SELECT a.appointment_date, a.appointment_time, a.status, p.patientName 
    FROM appointments a 
    LEFT JOIN patients p ON a.patientsId = p.patientsId 
    WHERE a.doctorId = ? 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 5
");
$appointment_stmt->bind_param("i", $doctorId);
$appointment_stmt->execute();
$appointments_result = $appointment_stmt->get_result();
$appointments = [];

if ($appointments_result->num_rows > 0) {
    while($row = $appointments_result->fetch_assoc()) {
        $appointments[] = $row;
    }
}
$appointment_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Details - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .main-container {
            display: flex;
            min-height: 100vh;
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
            margin-bottom: 10px;
        }
        
        .nav-item a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
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
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 28px;
            display: flex;
            align-items: center;
        }
        
        .page-title i {
            margin-right: 15px;
            color: #3498db;
        }
        
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #e9ecef;
            color: #495057;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background-color: #dee2e6;
        }
        
        .back-button i {
            margin-right: 8px;
        }
        
        .doctor-details {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .doctor-card {
            flex: 1;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .doctor-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .doctor-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
        }
        
        .doctor-avatar i {
            font-size: 50px;
            color: white;
        }
        
        .doctor-info h2 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .doctor-info p {
            color: #7f8c8d;
            margin-bottom: 3px;
        }
        
        .doctor-info .specialty {
            font-weight: 600;
            color: #3498db;
            font-size: 18px;
            margin-top: 5px;
        }
        
        .doctor-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .detail-item {
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-item h4 {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        
        .detail-item p {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .appointments-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .card-header h3 {
            font-size: 20px;
            color: #2c3e50;
        }
        
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .appointments-table th, .appointments-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .appointments-table th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
        }
        
        .appointments-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-scheduled {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }
        
        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: #f57c00;
        }
        
        .action-btns {
            display: flex;
            gap: 10px;
        }
        
        .action-btns a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .btn-view {
            background-color: #3498db;
        }
        
        .btn-edit {
            background-color: #f39c12;
        }
        
        .btn-delete {
            background-color: #e74c3c;
        }
        
        .action-btns a:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .no-appointments {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        
        .no-appointments i {
            font-size: 50px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 992px) {
            .doctor-details {
                flex-direction: column;
            }
            
            .doctor-details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
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
                    <a href="doctor_list.php" class="active">
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
            <a href="doctor_list.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back to Doctors List
            </a>
            
            <h1 class="page-title"><i class="fas fa-user-md"></i> Doctor Details</h1>
            
            <div class="doctor-details">
                <div class="doctor-card">
                    <div class="doctor-header">
                        <div class="doctor-avatar">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div class="doctor-info">
                            <h2><?php echo htmlspecialchars($doctor['doctorName']); ?></h2>
                            <p>License: <?php echo htmlspecialchars($doctor['license_hash']); ?></p>
                        </div>
                    </div>
                    
                    <div class="doctor-details-grid">
                        <div class="detail-item">
                            <h4>Email Address</h4>
                            <p><?php echo htmlspecialchars($doctor['email']); ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Phone Number</h4>
                            <p><?php echo htmlspecialchars($doctor['phone']); ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>License Number</h4>
                            <p><?php echo $doctor['doctorId']; ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Member Since</h4>
                            <p><?php echo date('M j, Y', strtotime($doctor['created_at'])); ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Status</h4>
                            <p><?php echo $doctor['on_duty'] ? 'On Duty' : 'Off Duty'; ?></p>
                        </div>
                        <div class="detail-item">
                            <h4>Shift</h4>
                            <p><?php echo htmlspecialchars($doctor['shift'] ?? 'Not specified'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="appointments-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check"></i> Recent Appointments</h3>
                    </div>
                    
                    <?php if (!empty($appointments)): ?>
                        <table class="appointments-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['patientName'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $status = strtolower($appointment['status']);
                                            $statusClass = 'status-pending';
                                            
                                            if ($status === 'scheduled') $statusClass = 'status-scheduled';
                                            elseif ($status === 'completed') $statusClass = 'status-completed';
                                            elseif ($status === 'cancelled') $statusClass = 'status-cancelled';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-appointments">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Appointments Found</h3>
                            <p>This doctor doesn't have any appointments yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>