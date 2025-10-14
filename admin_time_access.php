<?php
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

// Time-based auto login configuration
$current_time = date('H:i');
$current_datetime = date('Y-m-d H:i:s');

// Define access windows
$morning_window_start = '08:00';
$morning_window_end = '12:00';
$afternoon_window_start = '13:00';
$afternoon_window_end = '17:00';

// Check if current time is within access windows
$is_morning_access = ($current_time >= $morning_window_start && $current_time <= $morning_window_end);
$is_afternoon_access = ($current_time >= $afternoon_window_start && $current_time <= $afternoon_window_end);

if ($is_morning_access || $is_afternoon_access) {
    
    // ACCESS GRANTED - Within allowed hours
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'time_based_admin';
    $_SESSION['user_type'] = 'admin';
    $_SESSION['login_method'] = 'time_based';
    $_SESSION['access_window'] = $is_morning_access ? 'morning' : 'afternoon';
    $_SESSION['login_time'] = date('Y-m-d H:i:s');
    
    // Redirect to dashboard
    header("Location: dashboard_admin.php");
    exit();
    
} else {
    // ACCESS DENIED - Outside allowed hours
    $next_access_time = getNextAccessTime($current_time);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Restricted - Healthcare System</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }
            
            body {
                background: linear-gradient(135deg, #ff6b6b 0%, #c44569 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            
            .container {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                text-align: center;
                max-width: 600px;
                width: 100%;
                border-left: 8px solid #e74c3c;
            }
            
            .icon {
                font-size: 80px;
                color: #e74c3c;
                margin-bottom: 20px;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            h1 {
                color: #2c3e50;
                margin-bottom: 15px;
                font-size: 32px;
            }
            
            .subtitle {
                color: #7f8c8d;
                font-size: 18px;
                margin-bottom: 30px;
            }
            
            .time-info {
                background: #fff5f5;
                padding: 25px;
                border-radius: 12px;
                margin: 25px 0;
                text-align: left;
                border: 2px solid #fed7d7;
            }
            
            .time-item {
                display: flex;
                justify-content: space-between;
                margin-bottom: 12px;
                padding: 10px 0;
                border-bottom: 1px solid #fed7d7;
            }
            
            .time-label {
                font-weight: 700;
                color: #2c3e50;
                min-width: 180px;
            }
            
            .time-value {
                color: #e53e3e;
                font-weight: 600;
            }
            
            .access-windows {
                background: #f0fff4;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                border-left: 5px solid #38a169;
            }
            
            .window-item {
                margin: 10px 0;
                padding: 8px 0;
            }
            
            .btn {
                display: inline-block;
                padding: 15px 30px;
                background: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                margin: 15px 10px;
                font-weight: 600;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
                font-size: 16px;
            }
            
            .btn:hover {
                background: #2980b9;
                transform: translateY(-2px);
            }
            
            .countdown {
                font-size: 24px;
                font-weight: 700;
                color: #e74c3c;
                margin: 20px 0;
                padding: 15px;
                background: #fff5f5;
                border-radius: 10px;
                border: 2px dashed #e74c3c;
            }
            
            .status-badge {
                display: inline-block;
                padding: 10px 20px;
                border-radius: 20px;
                font-weight: 600;
                margin: 10px 0;
                background: #fed7d7;
                color: #c53030;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
            
            <h1>🚫 Access Restricted</h1>
            <p class="subtitle">Admin access is only available during business hours</p>
            
            <div class="status-badge">
                <i class="fas fa-clock"></i> OUTSIDE ACCESS HOURS
            </div>
            
            <div class="time-info">
                <div class="time-item">
                    <span class="time-label">📅 Current Date:</span>
                    <span class="time-value"><?php echo date('l, F j, Y'); ?></span>
                </div>
                <div class="time-item">
                    <span class="time-label">🕐 Current Time:</span>
                    <span class="time-value"><?php echo $current_datetime; ?></span>
                </div>
                <div class="time-item">
                    <span class="time-label">🔒 Access Status:</span>
                    <span class="time-value">DENIED</span>
                </div>
                <div class="time-item">
                    <span class="time-label">⏰ Next Access:</span>
                    <span class="time-value"><?php echo $next_access_time; ?></span>
                </div>
            </div>
            
            <div class="access-windows">
                <h3><i class="fas fa-calendar-alt"></i> Available Access Windows:</h3>
                <div class="window-item">
                    <strong>🌅 Morning Session:</strong> 8:00 AM - 12:00 PM
                </div>
                <div class="window-item">
                    <strong>🌞 Afternoon Session:</strong> 1:00 PM - 5:00 PM
                </div>
            </div>
            
            <div class="countdown">
                ⏳ Next access in: <span id="timer">--:--:--</span>
            </div>

            <a href="logintest_doctor.php" class="btn">
                <i class="fas fa-sign-in-alt"></i> Use Regular Login
            </a>
            
            <div style="margin-top: 20px; color: #7f8c8d; font-size: 14px;">
                <i class="fas fa-info-circle"></i> 
                System time: <?php echo date('H:i:s'); ?> (Server Time)
            </div>
        </div>

        <script>
            // Countdown to next access window
            function updateCountdown() {
                const now = new Date();
                const currentHour = now.getHours();
                const currentMinute = now.getMinutes();
                
                let targetTime = new Date();
                
                // Determine next access window
                if (currentHour < 8) {
                    // Before morning session - target 8:00 AM today
                    targetTime.setHours(8, 0, 0, 0);
                } else if (currentHour >= 12 && currentHour < 13) {
                    // Between sessions - target 1:00 PM today
                    targetTime.setHours(13, 0, 0, 0);
                } else if (currentHour >= 17) {
                    // After afternoon session - target 8:00 AM tomorrow
                    targetTime.setDate(targetTime.getDate() + 1);
                    targetTime.setHours(8, 0, 0, 0);
                } else {
                    // Shouldn't happen, but just in case
                    targetTime.setDate(targetTime.getDate() + 1);
                    targetTime.setHours(8, 0, 0, 0);
                }
                
                const diff = targetTime - now;
                
                if (diff <= 0) {
                    document.getElementById('timer').textContent = '00:00:00';
                    location.reload();
                    return;
                }
                
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                
                document.getElementById('timer').textContent = 
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            updateCountdown();
            setInterval(updateCountdown, 1000);
        </script>
    </body>
    </html>
    <?php
    exit();
}

function getNextAccessTime($current_time) {
    $current_hour = (int) date('H');
    
    if ($current_hour < 8) {
        return "Today at 8:00 AM";
    } elseif ($current_hour >= 12 && $current_hour < 13) {
        return "Today at 1:00 PM";
    } elseif ($current_hour >= 17) {
        return "Tomorrow at 8:00 AM";
    } else {
        return "Check schedule";
    }
}
?>