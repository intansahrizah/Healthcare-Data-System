<?php
// Database connection
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

// Get appointment ID from request
$appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if ($appointmentId > 0) {
    // Fetch appointment details
    $stmt = $conn->prepare("
        SELECT a.*, p.patientName as patientName, p.email as patient_email, 
               p.phone as patient_phone, d.doctorName
        FROM appointments a 
        LEFT JOIN patients p ON a.patientsId = p.patientsId 
        LEFT JOIN doctors d ON a.doctorId = d.doctorId
        WHERE a.appointmentId = ?
    ");
    
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $appt = $result->fetch_assoc();
        
        echo '<div class="appointment-detail-item">';
        echo '<div class="appointment-detail-label">Date & Time</div>';
        echo '<div>' . date('F j, Y', strtotime($appt['appointment_date'])) . ' at ' . 
             date('g:i A', strtotime($appt['appointment_time'])) . '</div>';
        echo '</div>';
        
        echo '<div class="appointment-detail-item">';
        echo '<div class="appointment-detail-label">Patient</div>';
        echo '<div>' . htmlspecialchars($appt['patientName']) . '</div>';
        echo '</div>';
        
        echo '<div class="appointment-detail-item">';
        echo '<div class="appointment-detail-label">Doctor</div>';
        echo '<div> ' . htmlspecialchars($appt['doctorName']) . '</div>';
        echo '</div>';
        
        echo '<div class="appointment-detail-item">';
        echo '<div class="appointment-detail-label">Reason</div>';
        echo '<div>' . htmlspecialchars($appt['reason']) . '</div>';
        echo '</div>';
        
        echo '<div class="appointment-detail-item">';
        echo '<div class="appointment-detail-label">Status</div>';
        echo '<span class="status-badge status-' . strtolower($appt['status']) . '">' . 
             $appt['status'] . '</span>';
        echo '</div>';
        
        if (!empty($appt['notes'])) {
            echo '<div class="appointment-detail-item">';
            echo '<div class="appointment-detail-label">Notes</div>';
            echo '<div>' . htmlspecialchars($appt['notes']) . '</div>';
            echo '</div>';
        }
    } else {
        echo '<div class="error">Appointment not found.</div>';
    }
    
    $stmt->close();
} else {
    echo '<div class="error">Invalid appointment ID.</div>';
}

$conn->close();
?>