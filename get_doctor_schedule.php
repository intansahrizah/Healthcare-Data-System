<?php
// get_doctor_schedule.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "healthcare_system";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$doctor_id = $_GET['doctor_id'];
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$scheduleData = [];

foreach ($days as $day) {
    $stmt = $conn->prepare("SELECT start_time, end_time, is_available FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?");
    $stmt->bind_param("is", $doctor_id, $day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $scheduleData[$day] = $result->fetch_assoc();
    } else {
        $scheduleData[$day] = [
            'is_available' => false,
            'start_time' => '09:00',
            'end_time' => '17:00'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($scheduleData);

$conn->close();
?>