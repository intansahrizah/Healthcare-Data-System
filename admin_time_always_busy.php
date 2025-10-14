<?php
session_start();

// ALWAYS BUSY - Force denial by using impossible time windows
$current_hour = date('H');
$impossible_windows = [
    'emergency_maintenance' => [24, 25, 26], // Impossible hours
    'system_update' => [27, 28, 29],         // More impossible hours
];

// ALWAYS show busy message
$busy_messages = [
    "System undergoing emergency maintenance",
    "Routine security update in progress", 
    "Server optimization underway",
    "Scheduled infrastructure maintenance",
    "Security protocol verification",
    "Database backup in progress",
    "System health check running"
];

$random_message = $busy_messages[array_rand($busy_messages)];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Busy - Healthcare Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Same beautiful styles as above */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #ff7e5f 0%, #feb47b 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: white; padding: 50px; border-radius: 20px; box-shadow: 0 25px 50px rgba(0,0,0,0.2); text-align: center; max-width: 700px; border-top: 8px solid #e67e22; }
        .icon { font-size: 100px; color: #e67e22; margin-bottom: 30px; }
        h1 { color: #2c3e50; margin-bottom: 20px; font-size: 36px; }
        .status-message { background: #fffaf0; padding: 20px; border-radius: 10px; margin: 25px 0; border-left: 5px solid #e67e22; }
        .technical-info { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; text-align: left; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <i class="fas fa-tools"></i>
        </div>
        
        <h1>🔧 System Currently Busy</h1>
        
        <div class="status-message">
            <h3><i class="fas fa-info-circle"></i> <?php echo $random_message; ?></h3>
            <p style="margin-top: 10px; color: #666;">We apologize for the inconvenience. Please try again later.</p>
        </div>
        
        <div class="technical-info">
            <h4><i class="fas fa-clock"></i> Current Server Time:</h4>
            <p style="font-size: 18px; font-weight: bold; color: #e67e22;">
                <?php echo date('l, F j, Y - H:i:s'); ?>
            </p>
            <p style="margin-top: 10px; color: #666;">
                All administrative functions are temporarily unavailable during this period.
            </p>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="logintest_doctor.php" class="btn" style="display: inline-block; padding: 15px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                <i class="fas fa-arrow-left"></i> Return to Login
            </a>
        </div>
        
        <div style="margin-top: 25px; padding: 15px; background: #e8f4f8; border-radius: 8px;">
            <i class="fas fa-headset"></i> 
            <strong>Need immediate access?</strong> Contact system administrator for emergency override.
        </div>
    </div>
</body>
</html>