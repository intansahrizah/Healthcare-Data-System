<?php
// delete_patient.php

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

// Check if patientName is set
if (isset($_GET['patientName'])) {
    $patientName = $conn->real_escape_string($_GET['patientName']);
    
    // Prepare a delete statement
    $stmt = $conn->prepare("DELETE FROM patients WHERE patientName = ?");
    $stmt->bind_param("s", $patientName);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Check if any row was affected
        if ($stmt->affected_rows > 0) {
            // Redirect back to the patient list with success message
            header("Location: patient_list.php?message=deleted");
            exit();
        } else {
            // No patient found with that name
            header("Location: patient_list.php?error=notfound");
            exit();
        }
    } else {
        // Error executing the query
        header("Location: patient_list.php?error=dberror");
        exit();
    }
    
    // Close statement
    $stmt->close();
} else {
    // Invalid request
    header("Location: patient_list.php?error=invalid");
    exit();
}

// Close connection
$conn->close();
?>