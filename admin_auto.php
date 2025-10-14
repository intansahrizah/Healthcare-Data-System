<?php
// admin_auto.php
session_start();

function autoLoginAdmin() {
    // Method 1: IP Whitelist
    $allowed_ips = ['127.0.0.1', '192.168.1.0/24'];
    $user_ip = $_SERVER['REMOTE_ADDR'];
    
    foreach ($allowed_ips as $ip) {
        if (strpos($ip, '/') !== false) {
            // CIDR notation
            if (ipInRange($user_ip, $ip)) return true;
        } else {
            // Exact IP match
            if ($user_ip === $ip) return true;
        }
    }
    
    // Method 2: Secret Parameter
    if (isset($_GET['admin_key']) && $_GET['admin_key'] === 'your_secret_key_2024') {
        return true;
    }
    
    // Method 3: Specific User Agent
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'InternalAdminBot') !== false) {
        return true;
    }
    
    return false;
}

function ipInRange($ip, $cidr) {
    list($subnet, $mask) = explode('/', $cidr);
    return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
}

// Auto-login if conditions are met
if (autoLoginAdmin()) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['auto_login'] = true;
    $_SESSION['login_time'] = time();
    header("Location: dashboard_admin.php");
    exit();
} else {
    // Fallback to regular login
    header("Location: admin_login.php");
    exit();
}
?>