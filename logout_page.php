<?php
// Start the session
session_start();

// Check if logout is confirmed
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
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
} else {
    // Show confirmation dialog with background image
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirm Logout</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
                background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                            url("https://gov-web-sing.s3.ap-southeast-1.amazonaws.com/uploads/2023/1/Wordpress-featured-images-48-1672795987342.jpg");
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-attachment: fixed;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .confirmation-box {
                background: rgba(255, 255, 255, 0.12);
                padding: 40px 30px;
                border-radius: 20px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                text-align: center;
                max-width: 450px;
                width: 90%;
                backdrop-filter: blur(15px);
                border: 1px solid rgba(255, 255, 255, 0.18);
            }
            .confirmation-box h2 {
                color: white;
                margin-bottom: 20px;
                font-weight: 600;
                font-size: 28px;
                text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            }
            .confirmation-box p {
                color: rgba(255, 255, 255, 0.9);
                margin-bottom: 30px;
                font-size: 18px;
                line-height: 1.6;
                text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
            }
            .button-group {
                display: flex;
                gap: 20px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .btn {
                padding: 14px 28px;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-size: 16px;
                font-weight: 600;
                transition: all 0.3s ease;
                min-width: 140px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .btn-confirm {
                background: linear-gradient(135deg, rgba(220, 53, 69, 0.9), rgba(178, 34, 34, 0.9));
                color: white;
                border: 2px solid rgba(255, 255, 255, 0.3);
            }
            .btn-confirm:hover {
                background: linear-gradient(135deg, rgba(199, 0, 23, 0.9), rgba(153, 0, 0, 0.9));
                transform: translateY(-3px);
                box-shadow: 0 6px 20px rgba(220, 53, 69, 0.5);
                border: 2px solid rgba(255, 255, 255, 0.5);
            }
            .btn-cancel {
                background: linear-gradient(135deg, rgba(108, 117, 125, 0.9), rgba(73, 80, 87, 0.9));
                color: white;
                border: 2px solid rgba(255, 255, 255, 0.3);
            }
            .btn-cancel:hover {
                background: linear-gradient(135deg, rgba(84, 91, 98, 0.9), rgba(52, 58, 64, 0.9));
                transform: translateY(-3px);
                box-shadow: 0 6px 20px rgba(108, 117, 125, 0.5);
                border: 2px solid rgba(255, 255, 255, 0.5);
            }
            
            /* Animation for popup */
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px) scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }
            
            .confirmation-box {
                animation: fadeInUp 0.5s ease-out;
            }
            
            /* Responsive design */
            @media (max-width: 480px) {
                .confirmation-box {
                    padding: 30px 20px;
                    margin: 20px;
                }
                .button-group {
                    flex-direction: column;
                    gap: 15px;
                }
                .btn {
                    min-width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="confirmation-box">
            <h2>Confirm Logout</h2>
            <p>Are you sure you want to log out?</p>
            <form method="POST" action="logout_page.php">
                <input type="hidden" name="confirm_logout" value="yes">
                <div class="button-group">
                    <button type="submit" class="btn btn-confirm">Yes, Logout</button>
                    <button type="button" class="btn btn-cancel" onclick="window.history.back()">Cancel</button>
                </div>
            </form>
        </div>
    </body>
    </html>';
    exit();
}
?>