<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$success = "";
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $doctorName = $_POST['doctorName'] ?? '';
    $email = $_POST['email'] ?? '';
    $license_hash = $_POST['license'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($doctorName)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($license_hash)) $errors[] = "License is required";
    if (empty($phone)) $errors[] = "Phone is required";
    if (empty($password)) $errors[] = "Password is required";

    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Validate password strength
    if (!empty($password) && (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password))) {
        $errors[] = "Password must be at least 8 characters with letters and numbers";
    }

    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT doctorId FROM doctors WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $errors[] = "Email already registered";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO doctors (doctorName, email, license_hash, phone, password_hash) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $doctorName, $email, $license_hash, $phone, $password_hash);
        
            if ($stmt->execute()) {
                $success = "Doctor registered successfully!";
                // Clear form after successful submission
                $_POST = array();
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration - Healthcare System</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0;
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
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-title {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #425466;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e7ff;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            border-color: #4a90e2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }
        
        .full-width {
            grid-column: span 2;
        }
        
        .btn-primary {
            background-color: #1a75bc;
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #0d47a1;
        }
        
        #responseMessage {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background-color: #c6f6d5;
            color: #2a7a3e;
            border: 1px solid #a3e9b6;
        }
        
        .error-message {
            background-color: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }
        
        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 14px;
            cursor: pointer;
            color: #64748b;
        }
        
        @media (max-width: 992px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .full-width {
                grid-column: span 1;
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                order: 2;
            }
            
            .content {
                order: 1;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    
    <div class="main-container">
        <!-- Updated Sidebar to match dashboard_admin.php -->
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
                    <a href="#">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
            </ul>
        </aside>
        
        <div class="content">
            <div class="card">
                <h2 class="card-title">Register New Doctor</h2>
                
                <div id="responseMessage" class="<?php echo !empty($success) ? 'success' : (!empty($errors) ? 'error-message' : ''); ?>" 
                     style="<?php echo (!empty($success) || !empty($errors)) ? 'display: block;' : 'display: none;'; ?>">
                    <?php
                    if (!empty($success)) {
                        echo $success;
                    } elseif (!empty($errors)) {
                        echo implode('<br>', $errors);
                    }
                    ?>
                </div>
                
                <form id="registerForm" method="post" action="">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="doctorName">Full Name</label>
                            <input type="text" id="doctorName" name="doctorName" required placeholder="Enter doctor's full name" 
                                   value="<?php echo isset($_POST['doctorName']) ? htmlspecialchars($_POST['doctorName']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required placeholder="doctor@example.com" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="license">Medical License Number</label>
                            <input type="text" id="license" name="license" required placeholder="MD123456" 
                                   value="<?php echo isset($_POST['license']) ? htmlspecialchars($_POST['license']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="eg. 0123456789" required
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="password-container">
                                <input type="password" id="password" name="password" required placeholder="••••••••" minlength="8">
                                <span class="toggle-password" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirmPassword">Confirm Password</label>
                            <div class="password-container">
                                <input type="password" id="confirmPassword" name="confirmPassword" required placeholder="••••••••">
                                <span class="toggle-password" id="toggleConfirmPassword">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        
                        <div class="form-group full-width">
                            <button type="submit" class="btn-primary">Register Doctor</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const responseMessage = document.getElementById('responseMessage');
            
            // If there are PHP errors or success, show the message
            <?php if (!empty($success) || !empty($errors)): ?>
                responseMessage.style.display = 'block';
                
                // Auto-hide success message after 5 seconds
                <?php if (!empty($success)): ?>
                    setTimeout(() => {
                        responseMessage.style.display = 'none';
                    }, 5000);
                <?php endif; ?>
            <?php endif; ?>
            
            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Toggle confirm password visibility
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                // Basic validation
                let isValid = true;
                const doctorName = document.getElementById('doctorName').value.trim();
                const email = document.getElementById('email').value.trim();
                const license = document.getElementById('license').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                
                if (!doctorName) {
                    isValid = false;
                    alert("Please enter doctor's full name");
                }
                
                if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                    isValid = false;
                    alert("Please enter a valid email address");
                }
                
                if (!license) {
                    isValid = false;
                    alert("Please enter medical license number");
                }
                
                if (!phone) {
                    isValid = false;
                    alert("Please enter phone number");
                }
                
                if (password.length < 8 || !/(?=.*[a-zA-Z])(?=.*[0-9])/.test(password)) {
                    isValid = false;
                    alert("Password must be at least 8 characters with letters and numbers");
                }
                
                if (password !== confirmPassword) {
                    isValid = false;
                    alert("Passwords do not match");
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>