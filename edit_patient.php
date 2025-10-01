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

$patient = null;
$message = '';

// Handle POST request for updating patient details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['patientsId'])) {
    $patientsId = intval($_POST['patientsId']);
    $patientName = $_POST['patientName'];
    $ic_number = $_POST['ic_number'];
    $gender = $_POST['gender'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $conn->prepare("UPDATE patients SET patientName=?, ic_number=?, gender=?, email=?, phone=? WHERE patientsId=?");
    $stmt->bind_param("sssssi", $patientName, $ic_number, $gender, $email, $phone, $patientsId);

    if ($stmt->execute()) {
        $message = "Patient details updated successfully!";
        // Fetch the updated details to display on the page
        $sql = "SELECT patientsId, patientName, ic_number, gender, email, phone FROM patients WHERE patientsId = ?";
        $fetch_stmt = $conn->prepare($sql);
        $fetch_stmt->bind_param("i", $patientsId);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        $patient = $result->fetch_assoc();
        $fetch_stmt->close();
    } else {
        $message = "Error updating record: " . $conn->error;
    }
    $stmt->close();
}

// Fetch patient details for display
if (isset($_GET['patientsId'])) {
    $patientsId = intval($_GET['patientsId']);
    $sql = "SELECT patientsId, patientName, ic_number, gender, email, phone FROM patients WHERE patientsId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patientsId);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $stmt->close();
    
    if (!$patient) {
        $message = "Patient not found.";
    }
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $message = "No patient ID provided.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --light: #f5f7fa;
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
            background-color: var(--light);
            color: #333;
            min-height: 100vh;
            display: flex;
            background: url('https://gov-web-sing.s3.ap-southeast-1.amazonaws.com/uploads/2023/1/Wordpress-featured-images-48-1672795987342.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .sidebar {
            width: 250px;
            background-color: var(--secondary);
            color: var(--white);
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
            color: var(--white);
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-item a:hover, .nav-item a.active {
            background-color: var(--primary);
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
            color: var(--secondary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .form-container {
            background-color: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-container h2 {
            font-size: 24px;
            color: var(--secondary);
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
        }

        .message {
            text-align: center;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }

        .message.success {
            background-color: #d4edda;
            color: var(--success);
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background-color: #f8d7da;
            color: var(--danger);
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
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
                <a href="patient_list.php" class="active">
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
        </ul>
    </aside>

    <div class="content">
        <a href="patient_list.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Patients
        </a>
        
        <h1 class="page-title"><i class="fas fa-user-edit"></i> Edit Patient Details</h1>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($patient): ?>
                <form action="edit_patient.php?patientsId=<?php echo htmlspecialchars($patient['patientsId']); ?>" method="POST">
                    <input type="hidden" name="patientsId" value="<?php echo htmlspecialchars($patient['patientsId']); ?>">
                    
                    <div class="form-group">
                        <label for="patientName">Patient Name</label>
                        <input type="text" id="patientName" name="patientName" value="<?php echo htmlspecialchars($patient['patientName']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="ic_number">IC Number</label>
                        <input type="text" id="ic_number" name="ic_number" value="<?php echo htmlspecialchars($patient['ic_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($patient['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($patient['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($patient['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            <?php else: ?>
                <div class="form-container">
                    <p>No patient details available to edit.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>