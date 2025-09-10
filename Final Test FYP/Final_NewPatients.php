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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fullName = $_POST['fullName'] ?? '';
    $icNumber = $_POST['ic_number'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Validate required fields
    $errors = [];
    if (empty($fullName)) $errors[] = "Full name is required";
    if (empty($icNumber)) $errors[] = "IC number is required";
    if (empty($gender)) $errors[] = "Gender is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($phone)) $errors[] = "Phone is required";
    if (empty($address)) $errors[] = "Address is required";
    
    if (empty($errors)) {

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO patients (fullName, ic_number, gender, email, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fullName, $icNumber, $gender, $email, $phone, $address);
        
        if ($stmt->execute()) {
            $success = "Patient registered successfully!";
            // Clear form after successful submission
            $_POST = array();
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Data Sharing - New Patient</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
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
            background-color: #3498db;
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

        /* Form Styles */
        .registration-form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .form-group input:focus, 
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .btn-submit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: background-color 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #2980b9;
        }

        /* Validation Styles */
        .error-message {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .form-group.error input,
        .form-group.error select {
            border-color: #e74c3c;
        }

        .form-group.error .error-message {
            display: block;
        }

        /* Success and Error Messages */
        .success-message {
            background-color: #2ecc71;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-messages {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }

        .error-messages p {
            margin: 5px 0;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
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
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>HealthCare</h1>
                <p>Data Sharing</p>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="HDS_HomePage.html">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="active">
                        <i class="fas fa-user-injured"></i>
                        Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2>Register New Patient</h2>
            </div>

            <div class="registration-form">
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="patientForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="fullName" 
                                   placeholder="Enter full name" required
                                   value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>">
                            <div class="error-message" id="nameError">Please enter a valid name</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="ic_number">IC Number</label>
                        <input type="text" id="ic_number" name="ic_number" 
                               placeholder="Enter IC number" required
                               value="<?php echo isset($_POST['ic_number']) ? htmlspecialchars($_POST['ic_number']) : ''; ?>">
                        <div class="error-message" id="icError">Please enter a valid IC number</div>
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                        <div class="error-message" id="genderError">Please select a gender</div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" placeholder="Enter email address" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        <div class="error-message" id="emailError">Please enter a valid email address</div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" placeholder="Enter phone number" name="phone" required
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        <div class="error-message" id="phoneError">Please enter a valid phone number</div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" placeholder="Enter full address" name="address" required
                               value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                        <div class="error-message" id="addressError">Please enter an address</div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">Register Patient</button>
                </form>
            </div>
        </main>
    </div>

    <script>
    document.getElementById("patientForm").addEventListener("submit", function(e) {
        if (!validateForm()) {
            e.preventDefault();
        }
    });

    function validateForm() {
        let isValid = true;
        
        // Reset error states
        document.querySelectorAll('.form-group').forEach(group => {
            group.classList.remove('error');
        });

        // Validate Full Name
        const fullName = document.getElementById("fullName").value.trim();
        if (fullName.length < 2) {
            document.getElementById("nameError").textContent = "Please enter a valid name (at least 2 characters)";
            document.getElementById("fullName").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate IC Number
        const icNumber = document.getElementById("ic_number").value.trim();
        if (!/^[0-9\-]{10,14}$/.test(icNumber)) {
            document.getElementById("icError").textContent = "Please enter a valid IC number (e.g. 000000-00-0000)";
            document.getElementById("ic_number").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Gender
        const gender = document.getElementById("gender").value;
        if (!gender) {
            document.getElementById("genderError").textContent = "Please select a gender";
            document.getElementById("gender").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Email
        const email = document.getElementById("email").value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById("emailError").textContent = "Please enter a valid email address";
            document.getElementById("email").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Phone
        const phone = document.getElementById("phone").value.trim();
        if (!/^[0-9]{10,15}$/.test(phone)) {
            document.getElementById("phoneError").textContent = "Please enter a valid phone number (10-15 digits)";
            document.getElementById("phone").parentElement.classList.add('error');
            isValid = false;
        }

        // Validate Address
        const address = document.getElementById("address").value.trim();
        if (address.length < 10) {
            document.getElementById("addressError").textContent = "Please enter a complete address (at least 10 characters)";
            document.getElementById("address").parentElement.classList.add('error');
            isValid = false;
        }

        return isValid;
    }
    </script>
</body>
</html>