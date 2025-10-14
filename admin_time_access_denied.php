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

// ALWAYS DENY ACCESS - For testing the denial page
$current_hour = date('H');
$current_time = date('H:i:s');
$current_date = date('l, F j, Y');

// Define access windows but NEVER allow access
$access_windows = [
    'maintenance' => [25, 26, 27], // Impossible hours - always denied
];

$active_window = 'maintenance';

// ALWAYS SHOW DENIAL PAGE (comment out this section to test auto-login)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Time Restricted</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
            margin-top: 30px;
            text-align: left;
        }
        
        .window-item {
            background: #f0fff4;
            padding: 12px 18px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 5px solid #38a169;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .window-icon {
            font-size: 20px;
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
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
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
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 25px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-ban"></i>
        </div>
        
        <h1>🚫 Access Restricted</h1>
        <p class="subtitle">Administrative access is currently unavailable due to time restrictions</p>
        
        <div class="warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Security Notice:</strong> This system enforces time-based access control for enhanced security.
        </div>
        
        <div class="time-info">
            <div class="time-item">
                <span class="time-label">📅 Current Date:</span>
                <span class="time-value"><?php echo $current_date; ?></span>
            </div>
            <div class="time-item">
                <span class="time-label">🕐 Current Time:</span>
                <span class="time-value"><?php echo $current_time; ?></span>
            </div>
            <div class="time-item">
                <span class="time-label">⏰ Current Hour:</span>
                <span class="time-value"><?php echo $current_hour . ':00'; ?></span>
            </div>
            <div class="time-item">
                <span class="time-label">🔒 Access Status:</span>
                <span class="time-value" style="color: #c53030;">DENIED</span>
            </div>
        </div>
        
        <div class="countdown">
            ⏳ Next access window in: <span id="timer">--:--:--</span>
        </div>
        
        <div class="access-windows">
            <h3><i class="fas fa-calendar-alt"></i> Scheduled Access Windows:</h3>
            <div class="window-item">
                <span class="window-icon">🌙</span>
                <div>
                    <strong>Maintenance Window</strong><br>
                    <span style="color: #666;">2:00 AM - 4:00 AM (Server Time)</span>
                </div>
            </div>
            <div class="window-item">
                <span class="window-icon">☀️</span>
                <div>
                    <strong>Morning Access</strong><br>
                    <span style="color: #666;">9:00 AM - 11:00 AM (Server Time)</span>
                </div>
            </div>
            <div class="window-item">
                <span class="window-icon">🌞</span>
                <div>
                    <strong>Afternoon Access</strong><br>
                    <span style="color: #666;">2:00 PM - 4:00 PM (Server Time)</span>
                </div>
            </div>
            <div class="window-item">
                <span class="window-icon">🌜</span>
                <div>
                    <strong>Evening Access</strong><br>
                    <span style="color: #666;">8:00 PM - 10:00 PM (Server Time)</span>
                </div>
            </div>
        </div>

        <div class="button-group">
            <a href="logintest_doctor.php" class="btn">
                <i class="fas fa-sign-in-alt"></i> Use Regular Login
            </a>
            <button class="btn btn-danger" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Check Again
            </button>
        </div>
        
        <div style="margin-top: 20px; color: #7f8c8d; font-size: 14px;">
            <i class="fas fa-info-circle"></i> 
            System automatically checks access permissions every time you visit this page.
        </div>
    </div>

    <script>
        // Countdown to next access window (2 AM tomorrow)
        function updateCountdown() {
            const now = new Date();
            const targetTime = new Date();
            
            // Set target to 2 AM tomorrow
            targetTime.setDate(targetTime.getDate() + 1);
            targetTime.setHours(2, 0, 0, 0);
            
            const diff = targetTime - now;
            
            if (diff <= 0) {
                document.getElementById('timer').textContent = '00:00:00';
                return;
            }
            
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById('timer').textContent = 
                `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Update immediately and every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // Add some visual effects
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });
    </script>
</body>
</html>