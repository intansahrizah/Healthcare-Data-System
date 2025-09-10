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
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";

    if (empty($errors)) {
        // Check if user exists
        $stmt = $conn->prepare("SELECT doctorId, doctorName, password_hash FROM doctors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $doctorName, $password_hash);
            $stmt->fetch();
            
            // Verify password
            if (password_verify($password, $password_hash)) {
                // Start session and redirect to dashboard
                session_start();
                $_SESSION['doctor_id'] = $doctorId;
                $_SESSION['doctor_name'] = $doctorName;
                $_SESSION['logged_in'] = true;
                
                header("Location: dashboard_doctor.php"); // Pegi page mana
                exit();
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
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
    <title>Doctor Login - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles remain unchanged */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a75bc 0%, #0d47a1 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .left-panel h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        
        .left-panel p {
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .features {
            list-style: none;
            margin-top: 30px;
        }
        
        .features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .features i {
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.2);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .right-panel {
            flex: 1.5;
            padding: 40px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        .logo i {
            font-size: 32px;
            margin-right: 10px;
            color: #1a75bc;
        }
        
        .logo h1 {
            font-size: 24px;
            font-weight: 700;
        }
        
        h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #425466;
        }
        
        input, select {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid #e0e7ff;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            border-color: #4a90e2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
        }
        
        .error {
            color: #e53e3e;
            font-size: 14px;
            margin-top: 5px;
            display: none;
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
        
        button {
            width: 100%;
            padding: 16px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #3a7bc8;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
        }
        
        .register-link a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        #responseMessage {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-message {
            background-color: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .left-panel {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h2>Welcome Back Doctor</h2> 
            <p>Access your personalized dashboard to manage your patients, appointments, and medical practice.</p>
            
            <ul class="features">
                <li><i class="fas fa-user-injured"></i> Access patient medical records</li>
                <li><i class="fas fa-calendar-alt"></i> Manage your appointment schedule</li>
                <li><i class="fas fa-prescription-bottle"></i> Write and review prescriptions</li>
                <li><i class="fas fa-chart-bar"></i> View practice analytics</li>
                <li><i class="fas fa-comment-medical"></i> Secure messaging with patients</li>
            </ul>
        </div>
        
        <div class="right-panel">
            <div class="logo">
                <i class="fas fa-heartbeat"></i>
                <h1>MedConnect</h1>
            </div>
            
            <h2>Doctor Login</h2>
            
            <div id="responseMessage" class="<?php echo !empty($errors) ? 'error-message' : ''; ?>" 
                 style="<?php echo !empty($errors) ? 'display: block;' : 'display: none;'; ?>">
                <?php
                if (!empty($errors)) {
                    echo implode('<br>', $errors);
                }
                ?>
            </div>
            
            <form id="loginForm" method="post" action="">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="doctor@example.com" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <div id="emailError" class="error">Please enter a valid email address</div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                        <span class="toggle-password" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                    <div id="passwordError" class="error">Please enter your password</div>
                </div>
                
                <div class="form-group" style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <input type="checkbox" id="remember" name="remember" style="width: auto; margin-right: 8px;">
                        <label for="remember" style="display: inline-block; margin-bottom: 0;">Remember me</label>
                    </div>
                    <a href="forgot_password.php" style="color: #4a90e2; text-decoration: none; font-size: 14px;">Forgot password?</a>
                </div>
                
                <button type="submit" id="loginBtn">Sign In</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="dashboard_doctor.php">Register here</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const responseMessage = document.getElementById('responseMessage');
            
            // If there are PHP errors, show the message
            <?php if (!empty($errors)): ?>
                responseMessage.style.display = 'block';
            <?php endif; ?>
            
            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                // Basic validation
                let isValid = true;
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;
                
                // Reset errors
                document.querySelectorAll('.error').forEach(el => el.style.display = 'none');
                
                if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                    document.getElementById('emailError').style.display = 'block';
                    isValid = false;
                }
                
                if (!password) {
                    document.getElementById('passwordError').style.display = 'block';
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    responseMessage.style.display = 'none'; // Hide PHP messages if client-side validation fails
                } else {
                    // Show loading state
                    const loginBtn = document.getElementById('loginBtn');
                    loginBtn.disabled = true;
                    loginBtn.textContent = 'Signing in...';
                }
            });
        });
    </script>
</body>
</html>