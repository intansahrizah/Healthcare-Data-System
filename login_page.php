<?php
// Start session at the very beginning
session_start();

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
$login_type = 'doctor'; // Default login type

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $login_type = $_POST['login_type'] ?? 'doctor';

    // Validate required fields
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";

    if (empty($errors)) {
        if ($login_type === 'doctor') {
            // DOCTOR LOGIN
            $stmt = $conn->prepare("SELECT doctorId, doctorName, password_hash FROM doctors WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $doctorName, $password_hash);
                $stmt->fetch();
                
                if (password_verify($password, $password_hash)) {
                    $_SESSION['doctorId'] = $id;
                    $_SESSION['doctor_name'] = $doctorName;
                    $_SESSION['logged_in'] = true;
                    $_SESSION['user_type'] = 'doctor';
                    
                    header("Location: dashboard_doctor.php");
                    exit();
                } else {
                    $errors[] = "Invalid email or password";
                }
            } else {
                $errors[] = "Invalid email or password";
            }
            $stmt->close();
            
        } elseif ($login_type === 'admin') {
            // ADMIN LOGIN - Check if admins table exists, if not use hardcoded credentials
            $admin_table_exists = $conn->query("SHOW TABLES LIKE 'admins'")->num_rows > 0;
            
            if ($admin_table_exists) {
                // Check in admins table
                $stmt = $conn->prepare("SELECT admin_id, username, password_hash FROM admins WHERE email = ? OR username = ?");
                $stmt->bind_param("ss", $email, $email);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $stmt->bind_result($id, $username, $password_hash);
                    $stmt->fetch();
                    
                    if (password_verify($password, $password_hash)) {
                        $_SESSION['admin_id'] = $id;
                        $_SESSION['admin_username'] = $username;
                        $_SESSION['admin_logged_in'] = true;
                        $_SESSION['user_type'] = 'admin';
                        
                        header( "Location: dashboard_admin.php");
                        exit();
                    } else {
                        $errors[] = "Invalid email/username or password";
                    }
                } else {
                    $errors[] = "Invalid email/username or password";
                }
                $stmt->close();
            } else {
                // Hardcoded admin credentials (for testing)
                $admin_credentials = [
                    'admin@healthcare.com' => 'admin123',
                    'admin' => 'admin123',
                    'superadmin' => 'admin123'
                ];
                
                if (isset($admin_credentials[$email]) && $password === $admin_credentials[$email]) {
                    $_SESSION['admin_id'] = 1;
                    $_SESSION['admin_username'] = $email;
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['user_type'] = 'admin';
                    
                    header("Location: dashboard_admin.php");
                    exit();
                } else {
                    $errors[] = "Invalid email/username or password";
                }
            }
        }
    }
}

// Get login type from POST or default to doctor
$current_login_type = $_POST['login_type'] ?? 'doctor';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare System Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('https://i.pinimg.com/1200x/8b/c5/ec/8bc5ecc28b81eddc4b3f93717f5dc4d0.jpg');
            background-size: cover;     /* Makes the image cover the whole screen */
            background-position: center; /* Keeps the image centered */
            background-repeat: no-repeat; /* Prevents tiling */
            background-attachment: fixed; /* Optional: keeps background fixed when scrolling */
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 1px, transparent 1px),
                radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 100px 100px, 150px 150px, 80px 80px;
            animation: sparkle 20s linear infinite;
        }

        @keyframes sparkle {
            0% { transform: translateY(0); }
            100% { transform: translateY(-100px); }
        }

        .container {
            display: flex;
            max-width: 900px;
            width: 90%;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 50px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .doctor-icon {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .doctor-icon svg {
            width: 60px;
            height: 60px;
            fill: #1e3c72;
        }

        .left-panel h1 {
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
        }

        .divider {
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, white, transparent);
            margin-bottom: 30px;
        }

        .feature {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            width: 100%;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 28px;
            height: 28px;
            fill: white;
        }

        .feature-text {
            font-size: 15px;
            line-height: 1.4;
        }

        .right-panel {
            flex: 1;
            padding: 50px 40px;
            background: #f5f7fa;
        }

        .right-panel h2 {
            font-size: 32px;
            color: #1e3c72;
            margin-bottom: 30px;
        }

        /* Login Type Selector */
        .login-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            background: #e8ecf1;
            padding: 5px;
            border-radius: 8px;
        }

        .login-type-btn {
            flex: 1;
            padding: 12px;
            background: transparent;
            border: 2px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            text-align: center;
        }

        .login-type-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .login-type-btn:hover {
            border-color: #3498db;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .required {
            color: #e74c3c;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            background: white;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-container {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 12px;
            cursor: pointer;
            color: #64748b;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
        }

        .remember-me label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
        }

        .forgot-password {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background-color: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        .demo-credentials {
            background: #e8ecf1;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }

        .demo-credentials h4 {
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        .demo-credentials ul {
            list-style: none;
            padding-left: 0;
        }

        .demo-credentials li {
            margin-bottom: 5px;
            padding-left: 15px;
            position: relative;
        }

        .demo-credentials li:before {
            content: "•";
            color: #3498db;
            font-weight: bold;
            position: absolute;
            left: 0;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #64748b;
        }
        
        .register-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .left-panel {
                padding: 30px 20px;
            }

            .right-panel {
                padding: 30px 20px;
            }

            .feature-text {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="doctor-icon">
                <svg viewBox="0 0 100 100">
                    <!-- Head -->
                    <circle cx="50" cy="25" r="18" fill="#1e3c72"/>
                    
                    <!-- Body/Scrubs with V-neck -->
                    <path d="M32 43 C32 43 28 45 25 50 L25 85 C25 90 30 95 35 95 L65 95 C70 95 75 90 75 85 L75 50 C72 45 68 43 68 43 L68 48 C68 50 66 52 64 52 L60 52 L55 45 L50 43 L45 45 L40 52 L36 52 C34 52 32 50 32 48 Z" fill="#1e3c72"/>
                    
                    <!-- White V-neck collar -->
                    <path d="M40 45 L45 52 L50 48 L55 52 L60 45 L55 43 L50 46 L45 43 Z" fill="white"/>
                    
                    <!-- Stethoscope - left side -->
                    <path d="M35 50 Q32 50 30 52 L30 68 Q30 72 33 75" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <circle cx="35" cy="77" r="4" stroke="white" stroke-width="2.5" fill="none"/>
                    
                    <!-- Stethoscope - right side -->
                    <path d="M65 50 Q68 50 70 52 L70 68 Q70 72 67 75" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                    <circle cx="65" cy="77" r="4" stroke="white" stroke-width="2.5" fill="none"/>
                    
                    <!-- Stethoscope earpieces -->
                    <circle cx="35" cy="50" r="2.5" fill="white"/>
                    <circle cx="65" cy="50" r="2.5" fill="white"/>
                    
                    <!-- Medical cross on chest -->
                    <rect x="47" y="60" width="6" height="14" fill="white" rx="1"/>
                    <rect x="44" y="63" width="12" height="6" fill="white" rx="1"/>
                </svg>
            </div>
            <h1>Welcome to Healthcare System</h1>
            <div class="divider"></div>
            
            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M19,3H14.82C14.4,1.84 13.3,1 12,1C10.7,1 9.6,1.84 9.18,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M12,3A1,1 0 0,1 13,4A1,1 0 0,1 12,5A1,1 0 0,1 11,4A1,1 0 0,1 12,3M7,7H17V5H19V19H5V5H7V7M17,11H7V9H17V11M15,15H7V13H15V15Z"/>
                    </svg>
                </div>
                <div class="feature-text">Access patient medical records</div>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M15,11H7V13H15V11M17,15H7V17H17V15Z"/>
                    </svg>
                </div>
                <div class="feature-text">Write and review prescription</div>
            </div>

            <div class="feature">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z"/>
                    </svg>
                </div>
                <div class="feature-text">Manage your appointment schedule</div>
            </div>
        </div>

        <div class="right-panel">
            <h2 id="loginTitle"><?php echo $current_login_type === 'doctor' ? 'Doctor Login' : 'Admin Login'; ?></h2>
            
            <!-- Login Type Selector -->
            <div class="login-type-selector">
                <button type="button" class="login-type-btn <?php echo $current_login_type === 'doctor' ? 'active' : ''; ?>" 
                        onclick="setLoginType('doctor')">
                    Doctor
                </button>
                <button type="button" class="login-type-btn <?php echo $current_login_type === 'admin' ? 'active' : ''; ?>" 
                        onclick="setLoginType('admin')">
                    Admin
                </button>
            </div>
            
            <div id="responseMessage" class="<?php echo !empty($errors) ? 'error-message' : ''; ?>" 
                 style="<?php echo !empty($errors) ? 'display: block;' : 'display: none;'; ?>">
                <?php
                if (!empty($errors)) {
                    echo implode('<br>', $errors);
                }
                ?>
            </div>
            
            <form id="loginForm" method="post" action="">
                <input type="hidden" id="loginType" name="login_type" value="<?php echo $current_login_type; ?>">
                
                <div class="form-group">
                    <label for="email" id="emailLabel">
                        <?php echo $current_login_type === 'doctor' ? 'Email Address <span class="required">*</span>' : 'Email or Username <span class="required">*</span>'; ?>
                    </label>
                    <input type="text" id="email" name="email" required 
                           placeholder="<?php echo $current_login_type === 'doctor' ? 'doctor@example.com' : 'admin@example.com or admin'; ?>" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="password-container">
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                        <span class="toggle-password" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-btn" id="loginBtn">
                    Sign In as <?php echo ucfirst($current_login_type); ?>
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="demo-credentials">
                <h4>Demo Credentials:</h4>
                <div id="doctorCredentials" style="<?php echo $current_login_type === 'doctor' ? 'display: block;' : 'display: none;'; ?>">
                    <strong>Doctor Login:</strong>
                    <ul>
                        <li>Use your registered doctor email</li>
                        <li>Password: your doctor password</li>
                    </ul>
                </div>
                <div id="adminCredentials" style="<?php echo $current_login_type === 'admin' ? 'display: block;' : 'display: none;'; ?>">
                    <strong>Admin Login:</strong>
                    <ul>
                        <li>Email/Username: admin@healthcare.com or admin</li>
                        <li>Password: admin123</li>
                    </ul>
                </div>
            </div>
            
            <div class="register-link">
                <?php if ($current_login_type === 'doctor'): ?>
                    Don't have an account? <a href="doctor_register.php">Register here</a>
                <?php else: ?>
                    Need help? <a href="contact_support.php">Contact Support</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function setLoginType(type) {
            document.getElementById('loginType').value = type;
            
            // Update button styles
            document.querySelectorAll('.login-type-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update form labels and placeholders
            const emailLabel = document.getElementById('emailLabel');
            const emailInput = document.getElementById('email');
            const loginTitle = document.getElementById('loginTitle');
            const loginBtn = document.getElementById('loginBtn');
            const doctorCred = document.getElementById('doctorCredentials');
            const adminCred = document.getElementById('adminCredentials');
            
            if (type === 'admin') {
                emailLabel.innerHTML = 'Email or Username <span class="required">*</span>';
                emailInput.placeholder = 'admin@example.com or admin';
                loginTitle.textContent = 'Admin Login';
                loginBtn.innerHTML = 'Sign In as Admin';
                doctorCred.style.display = 'none';
                adminCred.style.display = 'block';
            } else {
                emailLabel.innerHTML = 'Email Address <span class="required">*</span>';
                emailInput.placeholder = 'doctor@example.com';
                loginTitle.textContent = 'Doctor Login';
                loginBtn.innerHTML = 'Sign In as Doctor';
                doctorCred.style.display = 'block';
                adminCred.style.display = 'none';
            }
        }

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
                const loginType = document.getElementById('loginType').value;
                
                if (!email) {
                    isValid = false;
                }
                
                if (!password) {
                    isValid = false;
                }
                
                if (!isValid) {
                    e.preventDefault();
                    responseMessage.textContent = 'Please fill in all required fields';
                    responseMessage.style.display = 'block';
                    responseMessage.className = 'error-message';
                } else {
                    // Show loading state
                    const loginBtn = document.getElementById('loginBtn');
                    loginBtn.disabled = true;
                    loginBtn.innerHTML = 'Signing in...';
                }
            });
        });
    </script>
</body>
</html>