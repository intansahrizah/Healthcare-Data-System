<?php
// Set response type to JSON
header('Content-Type: application/json');

// Database connection settings (Laragon defaults)
$servername = "localhost";
$username   = "root"; // default Laragon username
$password   = "1234";     // default Laragon password is empty
$dbname     = "healthcare_system"; // change if your DB name is different

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Get JSON data from fetch() request
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
$required = ['fullName', 'ic_number', 'gender', 'email', 'phone', 'address'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        echo json_encode(["success" => false, "message" => "Missing required field: $field"]);
        exit;
    }
}

// Prepare SQL insert statement
$stmt = $conn->prepare("INSERT INTO patients (full_name, ic_number, gender, email, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param(
    "ssssss",
    $data['fullName'],
    $data['ic_number'],
    $data['gender'],
    $data['email'],
    $data['phone'],
    $data['address']
);

// Execute and send response
if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Patient registered successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
