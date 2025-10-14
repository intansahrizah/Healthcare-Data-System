<?php
session_start();

// ULTRA STRICT - Always deny with military-style precision
header("HTTP/1.1 403 Forbidden");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACCESS DENIED - Time Violation</title>
    <style>
        body { 
            background: #2c3e50; 
            color: white; 
            font-family: 'Courier New', monospace;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .terminal {
            background: #1a252f;
            padding: 40px;
            border-radius: 10px;
            border: 2px solid #e74c3c;
            max-width: 800px;
            width: 100%;
        }
        .blink { animation: blink 1s infinite; }
        @keyframes blink { 50% { opacity: 0; } }
        .red { color: #e74c3c; }
        .yellow { color: #f39c12; }
        .green { color: #2ecc71; }
    </style>
</head>
<body>
    <div class="terminal">
        <div class="red">╔══════════════════════════════════════╗</div>
        <div class="red">║ ████████████████████████████████████ ║</div>
        <div class="red">║ ██ <span class="blink">⚠️  ACCESS VIOLATION DETECTED</span> ██ ║</div>
        <div class="red">║ ████████████████████████████████████ ║</div>
        <div class="red">║                                      ║</div>
        <div class="yellow">║  TIMESTAMP: <?php echo date('Y-m-d H:i:s T'); ?> ║</div>
        <div class="yellow">║  VIOLATION: TIME_BASED_RESTRICTION ║</div>
        <div class="yellow">║  STATUS: PERMANENTLY_DENIED        ║</div>
        <div class="red">║                                      ║</div>
        <div class="red">║  THIS SYSTEM OPERATES ON STRICT      ║</div>
        <div class="red">║  TEMPORAL ACCESS PROTOCOLS.          ║</div>
        <div class="red">║                                      ║</div>
        <div class="green">║  NEXT WINDOW: [CLASSIFIED]          ║</div>
        <div class="green">║  AUTH METHOD: [RESTRICTED]          ║</div>
        <div class="red">║                                      ║</div>
        <div class="red">║  ████████████████████████████████████ ║</div>
        <div class="red">║  ██ SECURITY PROTOCOL ACTIVE ██ ║</div>
        <div class="red">║  ████████████████████████████████████ ║</div>
        <div class="red">╚══════════════════════════════════════╝</div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="logintest_doctor.php" style="color: #3498db; text-decoration: none;">
                [ RETURN TO STANDARD ACCESS PORTAL ]
            </a>
        </div>
    </div>
</body>
</html>