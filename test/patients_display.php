<?php
// Database connection (using your Laragon credentials)
$host = "localhost";
$user = "root";
$password = ""; // Empty password as per your setup
$database = "healthcare_system";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch all patients from database
$sql = "SELECT * FROM patients ORDER BY patientsId DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Database Viewer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        h1 {
            color: #2c3e50;
        }
        .btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
        }
        .btn i {
            margin-right: 8px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .status-pending {
            color: #e67e22;
        }
        .status-completed {
            color: #2ecc71;
        }
        .action-btns a {
            color: #3498db;
            margin-right: 10px;
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-injured"></i> Patient Records</h1>
            <a href="HDS_NewPatient.html" class="btn">
                <i class="fas fa-plus"></i> Add New Patient
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>IC Number</th>
                    <th>Gender</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['patientsId']); ?></td>
                            <td><?php echo htmlspecialchars($row['patientName']); ?></td>
                            <td><?php echo htmlspecialchars($row['ic_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['gender']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td class="action-btns">
                                <a href="view_patient.php?patientsId=<?php echo $row['patientsId']; ?>" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_patient.php?patientsId=<?php echo $row['patientsId']; ?>" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_patient.php?patientsId=<?php echo $row['patientsId']; ?>" title="Delete" 
                                   onclick="return confirm('Are you sure you want to delete this record?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-data">
                            <i class="fas fa-database" style="font-size: 48px; margin-bottom: 15px;"></i><br>
                            No patient records found in the database.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php mysqli_close($conn); ?>
</body>
</html>