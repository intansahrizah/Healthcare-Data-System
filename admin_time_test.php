<?php
session_start();

// FOR TESTING: Allow manual time override via URL
if (isset($_GET['test_hour'])) {
    $current_hour = intval($_GET['test_hour']);
    $test_mode = true;
} else {
    $current_hour = date('H');
    $test_mode = false;
}

// Define access windows
$access_windows = [
    'maintenance' => [2, 3, 4],   // 2 AM - 4 AM
    'morning' => [9, 10, 11],     // 9 AM - 11 AM  
    'afternoon' => [14, 15, 16],  // 2 PM - 4 PM
    'evening' => [20, 21, 22],    // 8 PM - 10 PM
    'always' => range(0, 23)      // 24/7 access
];

// Test different windows
$window_to_test = $_GET['window'] ?? 'maintenance';

if (in_array($current_hour, $access_windows[$window_to_test])) {
    // SUCCESS - Auto login
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'time_based_admin';
    $_SESSION['user_type'] = 'admin';
    $_SESSION['login_method'] = 'time_based';
    
    if ($test_mode) {
        echo "<h1>✅ SUCCESS! Time-Based Login Working</h1>";
        echo "<p>Current test hour: $current_hour:00</p>";
        echo "<p>Access window: $window_to_test</p>";
        echo "<a href='dashboard_admin.php'>Go to Dashboard</a>";
    } else {
        header("Location: dashboard_admin.php");
        exit();
    }
} else {
    // Show test interface
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Time-Based Login Test</title>
        <style>
            body { font-family: Arial; padding: 20px; }
            .test-box { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .success { background: #d4edda; padding: 10px; border-radius: 5px; }
            .error { background: #f8d7da; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h1>🕒 Time-Based Login Test</h1>
        
        <div class="test-box">
            <h3>Test Different Times:</h3>
            <?php
            foreach ($access_windows as $name => $hours) {
                echo "<p><strong>" . ucfirst($name) . ":</strong> ";
                echo implode(':00, ', $hours) . ":00";
                echo " - <a href='admin_time_test.php?window=$name&test_hour=" . $hours[0] . "'>Test Access</a>";
                echo "</p>";
            }
            ?>
        </div>
        
        <div class="error">
            <h3>❌ Current Access Denied</h3>
            <p>Real time: <?php echo date('H:i:s'); ?> (Hour: <?php echo date('H'); ?>)</p>
            <p>Window '<?php echo $window_to_test; ?>' allows hours: <?php echo implode(', ', $access_windows[$window_to_test]); ?></p>
        </div>
        
        <p><a href="logintest_doctor.php">Use Regular Login</a></p>
    </body>
    </html>
    <?php
}
?>