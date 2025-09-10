<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Data Sharing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: url('https://gov-web-sing.s3.ap-southeast-1.amazonaws.com/uploads/2023/1/Wordpress-featured-images-48-1672795987342.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
        }

        .login-container {
            background-color: rgba(33, 33, 33, 0.85);
            padding: 40px;
            border-radius: 15px;
            width: 450px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(79, 195, 247, 0.3);
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo i {
            font-size: 32px;
            margin-right: 10px;
            color: #4fc3f7;
        }

        h1 {
            color: #4fc3f7;
            text-align: center;
            margin-bottom: 5px;
            font-size: 28px;
            font-weight: 700;
        }

        .tagline {
            text-align: center;
            margin-bottom: 30px;
            color: #e0f7fa;
            font-size: 16px;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #4fc3f7;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px 15px;
            border: 2px solid #4fc3f7;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            background-color: rgba(255, 255, 255, 0.95);
            color: #333;
        }

        input:focus {
            border-color: #29b6f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(41, 182, 246, 0.3);
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

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        a {
            color: #4fc3f7;
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: #29b6f6;
            text-decoration: underline;
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background: linear-gradient(135deg, #29b6f6 0%, #039be5 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        button:active {
            transform: translateY(0);
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #b3e5fc;
        }
        
        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background-color: rgba(255, 107, 107, 0.1);
            border-radius: 8px;
            border: 1px solid #ff6b6b;
            display: block;
        }
        
        .success-message {
            color: #4caf50;
            text-align: center;
            margin-bottom: 20px;
            padding: 12px;
            background-color: rgba(76, 175, 80, 0.1);
            border-radius: 8px;
            border: 1px solid #4caf50;
        }
        
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #b3e5fc;
        }
        
        .input-error {
            border-color: #ff6b6b !important;
        }
        
        .error-text {
            color: #ff6b6b;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-heartbeat"></i>
            <h1>Healthcare Data Sharing</h1>
        </div>
        <p class="tagline">Secure access to your medical records</p>
        
        <?php
        // Database configuration
        $servername = "localhost";
        $username = "root";
        $password = "1234";
        $dbname = "healthcare_system";
        
        // Initialize variables
        $error = "";
        $success = "";
        
        // Check if form is submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get input values
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Simple validation
            if (empty($email) || empty($password)) {
                $error = "Please enter both email and password";
            } else {
                try {
                    // Create connection
                    $conn = new mysqli($servername, $username, $password, $dbname);
                    
                    // Check connection
                    if ($conn->connect_error) {
                        throw new Exception("Connection failed: " . $conn->connect_error);
                    }
                    
                    // Prepare and execute query
                    $stmt = $conn->prepare("SELECT id, full_name, email, password_hash FROM doctors WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        // Verify password
                        if (password_verify($password, $user['password_hash'])) {
                            // Start session and store user data
                            session_start();
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_type'] = 'doctor';
                            
                            // Set success message
                            $success = "Login successful! Redirecting to dashboard...";
                            
                            // Redirect to dashboard after a short delay
                            echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'HDS_Dashboard.php';
                                }, 1500);
                            </script>";
                        } else {
                            $error = "Invalid email or password";
                        }
                    } else {
                        $error = "No account found with this email address";
                    }
                    
                    $stmt->close();
                    $conn->close();
                    
                } catch (Exception $e) {
                    $error = "Database error: Please try again later";
                    // For debugging only - remove in production
                    // $error = "Database error: " . $e->getMessage();
                }
            }
        }
        ?>
        
        <!-- Display error message if any -->
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Display success message if any -->
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> 
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="">
            <div class="input-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div id="emailError" class="error-text">Please enter a valid email address</div>
            </div>
            
            <div class="input-group">
                <label for="password">Password:</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <span class="toggle-password" id="togglePassword">
                        <i class="far fa-eye"></i>
                    </span>
                </div>
                <div id="passwordError" class="error-text">Please enter your password</div>
            </div>
            
            <div class="forgot-password">
                <a href="#"><i class="fas fa-key"></i> Forgot Password?</a>
            </div>
            
            <button type="submit" id="loginBtn">
                <span id="btnText">Login</span>
                <span id="btnLoader" class="loader" style="display:none;"></span>
            </button>
        </form>
        
        <div class="register-link">
            <p>Don't have an account? <a href="test_doctors.php">Register here</a></p>
        </div>
        
        <div class="footer">
            <p><i class="fas fa-lock"></i> Secure & Encrypted | <i class="fas fa-shield-alt"></i> HIPAA Compliant</p>
            <p>© 2023 Healthcare Data Sharing. All rights reserved.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const loginBtn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const btnLoader = document.getElementById('btnLoader');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            
            // Toggle password visibility
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Form submission
            loginForm.addEventListener('submit', function(e) {
                // Reset errors
                emailInput.classList.remove('input-error');
                passwordInput.classList.remove('input-error');
                emailError.style.display = 'none';
                passwordError.style.display = 'none';
                
                let isValid = true;
                
                // Validate email
                if (!emailInput.value.trim() || !/\S+@\S+\.\S+/.test(emailInput.value)) {
                    emailInput.classList.add('input-error');
                    emailError.style.display = 'block';
                    isValid = false;
                }
                
                // Validate password
                if (!passwordInput.value) {
                    passwordInput.classList.add('input-error');
                    passwordError.style.display = 'block';
                    isValid = false;
                }
                
                if (isValid) {
                    // Show loading state
                    btnText.textContent = 'Logging in...';
                    btnLoader.style.display = 'inline-block';
                    loginBtn.disabled = true;
                } else {
                    e.preventDefault();
                }
            });
            
            // Clear error when typing
            emailInput.addEventListener('input', function() {
                if (this.classList.contains('input-error')) {
                    this.classList.remove('input-error');
                    emailError.style.display = 'none';
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (this.classList.contains('input-error')) {
                    this.classList.remove('input-error');
                    passwordError.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>