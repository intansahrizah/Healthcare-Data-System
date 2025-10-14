<?php
// Start the session
session_start();

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Set security headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// JavaScript to clear history and prevent back button
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <script>
        // Clear session storage and local storage
        sessionStorage.clear();
        localStorage.clear();
        
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function() {
            history.go(1);
        };
        
        // Redirect to login page
        window.location.href = "login_page.php";
    </script>
</head>
<body>
    <div style="text-align: center; margin-top: 50px;">
        <h2>Logging out...</h2>
        <p>Please wait while we securely log you out.</p>
    </div>
</body>
</html>';
exit();
?>