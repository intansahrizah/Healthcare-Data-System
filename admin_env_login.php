<?php
// admin_env_login.php
session_start();

// Set this in your server environment variables
$admin_secret = getenv('ADMIN_SECRET_KEY');

if (isset($_GET['key']) && $_GET['key'] === $admin_secret) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['login_method'] = 'environment_key';
    header("Location: dashboard_admin.php");
    exit();
} else {
    die("Invalid access key.");
}
?>