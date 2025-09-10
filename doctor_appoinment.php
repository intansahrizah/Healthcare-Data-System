<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Data Sharing - Doctor Appointments</title>
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --success: #2ecc71;
            --danger: #e74c3c;
            --light: #f5f7fa;
            --dark: #333;
            --gray: #95a5a6;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            background: url('https://gov-web-sing.s3.ap-southeast-1.amazonaws.com/uploads/2023/1/Wordpress-featured-images-48-1672795987342.jpg') no-repeat center center fixed;
            color: #333;
            background-size: cover; 
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

         /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--secondary) 0%, #1a252f 100%);
            color: var(--white);
            padding: 2rem 0;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 10;
        }

        .logo {
            text-align: center;
            padding: 0 2rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--white), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo p {
            font-size: 0.9rem;
            opacity: 0.8;
            letter-spacing: 1px;
        }

        .nav-menu {
            padding: 0 1.5rem;
        }

        .nav-item {
            margin-bottom: 0.8rem;
            list-style: none;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 0.8rem 1.2rem;
            border-radius: 6px;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .nav-item a:hover, 
        .nav-item a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            transform: translateX(5px);
        }

        .nav-item i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h2 {
            font-size: 28px;
            color: #2c3e50;
        }

        .search-bar {
            display: flex;
            margin-bottom: 20px;
        }

        .search-bar input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            font-size: 14px;
        }

        .search-bar button {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }

        .appointment-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .btn-success {
            background-color: #2ecc71;
        }

        .btn-success:hover {
            background-color: #27ae60;
        }

        .btn-danger {
            background-color: #e74c3c;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn-secondary {
            background-color: #95a5a6;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .appointment-table-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .appointment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .appointment-table th, 
        .appointment-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .appointment-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-scheduled {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .action-btn {
            padding: 5px 10px;
            margin-right: 5px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }

        .action-btn:hover {
            background-color: #e9ecef;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            width: 500px;
            max-width: 90%;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        /* Message Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                padding: 20px;
            }
            .nav-menu {
                display: flex;
                overflow-x: auto;
                padding-bottom: 10px;
            }
            .nav-item {
                margin-right: 15px;
                margin-bottom: 0;
                white-space: nowrap;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }

        /* New styles for demo */
        .demo-controls {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .demo-controls h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .demo-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Doctor</h1>
                <p>Healthcare Data Sharing</p>
            </div>

            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="HDS_PatientPageDoctor.html">
                        <i class="fas fa-user-injured"></i>
                        Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="active">
                        <i class="fas fa-calendar-check"></i>
                        Appointments
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2>Doctor Appointment Management</h2>
                <div class="user-profile">
                    <!-- User profile/notification can be added here -->
                </div>
            </div>

            <!-- Demo controls for testing -->
            <div class="demo-controls">
                <h3>Demo Controls</h3>
                <div class="demo-buttons">
                    <button class="btn" onclick="addDemoAppointment()">
                        <i class="fas fa-plus"></i> Add Demo Appointment
                    </button>
                    <button class="btn btn-secondary" onclick="clearAppointments()">
                        <i class="fas fa-trash"></i> Clear Appointments
                    </button>
                    <button class="btn btn-success" onclick="showAllAppointments()">
                        <i class="fas fa-eye"></i> Show All Appointments
                    </button>
                </div>
            </div>

            <!-- Display success/error messages -->
            <div id="messageArea"></div>

            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Search appointments..." onkeyup="filterAppointments()">
                <button onclick="filterAppointments()"><i class="fas fa-search"></i></button>
            </div>

            <div class="appointment-table-container">
                <h3>My Appointments</h3>
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Patient</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="appointmentsTableBody">
                        <!-- Appointments will be dynamically inserted here -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Sample appointment data
        let appointments = [
            {
                id: 1,
                date: '2023-10-15',
                time: '10:00:00',
                patient: 'John Doe',
                purpose: 'Regular Checkup',
                status: 'Scheduled'
            },
            {
                id: 2,
                date: '2023-10-16',
                time: '14:30:00',
                patient: 'Jane Smith',
                purpose: 'Follow-up Visit',
                status: 'Confirmed'
            },
            {
                id: 3,
                date: '2023-10-17',
                time: '09:15:00',
                patient: 'Robert Johnson',
                purpose: 'Vaccination',
                status: 'Scheduled'
            }
        ];

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            renderAppointments();
        });

        // Render appointments to the table
        function renderAppointments() {
            const tbody = document.getElementById('appointmentsTableBody');
            tbody.innerHTML = '';
            
            if (appointments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No appointments found.</td></tr>';
                return;
            }
            
            appointments.forEach(appt => {
                const row = document.createElement('tr');
                
                // Format date and time
                const formattedDate = formatDate(appt.date);
                const formattedTime = formatTime(appt.time);
                
                row.innerHTML = `
                    <td>${formattedDate} - ${formattedTime}</td>
                    <td>${appt.patient}</td>
                    <td>${appt.purpose}</td>
                    <td><span class="status-badge status-${appt.status.toLowerCase()}">${appt.status}</span></td>
                    <td>
                        ${appt.status === 'Scheduled' ? 
                            `<button class="btn btn-success" onclick="confirmAppointment(${appt.id})">Confirm</button>
                             <button class="btn btn-danger" onclick="cancelAppointment(${appt.id})">Cancel</button>` : 
                            '<span>No actions available</span>'
                        }
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Format date as "Oct 15, 2023"
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        // Format time as "10:00 AM"
        function formatTime(timeString) {
            const time = timeString.split(':');
            let hours = parseInt(time[0]);
            const minutes = time[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            return hours + ':' + minutes + ' ' + ampm;
        }

        // Confirm appointment
        function confirmAppointment(id) {
            const appointment = appointments.find(a => a.id === id);
            if (appointment) {
                appointment.status = 'Confirmed';
                renderAppointments();
                showMessage('Appointment confirmed successfully!', 'success');
            }
        }

        // Cancel appointment
        function cancelAppointment(id) {
            const appointment = appointments.find(a => a.id === id);
            if (appointment) {
                appointment.status = 'Cancelled';
                renderAppointments();
                showMessage('Appointment cancelled successfully!', 'success');
            }
        }

        // Show message
        function showMessage(message, type) {
            const messageArea = document.getElementById('messageArea');
            messageArea.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            
            // Auto hide message after 3 seconds
            setTimeout(() => {
                messageArea.innerHTML = '';
            }, 3000);
        }

        // Filter appointments based on search input
        function filterAppointments() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            
            if (!searchText) {
                renderAppointments();
                return;
            }
            
            const filteredAppointments = appointments.filter(appt => 
                appt.patient.toLowerCase().includes(searchText) || 
                appt.purpose.toLowerCase().includes(searchText) ||
                appt.status.toLowerCase().includes(searchText)
            );
            
            const tbody = document.getElementById('appointmentsTableBody');
            tbody.innerHTML = '';
            
            if (filteredAppointments.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No matching appointments found.</td></tr>';
                return;
            }
            
            filteredAppointments.forEach(appt => {
                const row = document.createElement('tr');
                
                // Format date and time
                const formattedDate = formatDate(appt.date);
                const formattedTime = formatTime(appt.time);
                
                row.innerHTML = `
                    <td>${formattedDate} - ${formattedTime}</td>
                    <td>${appt.patient}</td>
                    <td>${appt.purpose}</td>
                    <td><span class="status-badge status-${appt.status.toLowerCase()}">${appt.status}</span></td>
                    <td>
                        ${appt.status === 'Scheduled' ? 
                            `<button class="btn btn-success" onclick="confirmAppointment(${appt.id})">Confirm</button>
                             <button class="btn btn-danger" onclick="cancelAppointment(${appt.id})">Cancel</button>` : 
                            '<span>No actions available</span>'
                        }
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }

        // Demo function to add a new appointment
        function addDemoAppointment() {
            const newId = appointments.length > 0 ? Math.max(...appointments.map(a => a.id)) + 1 : 1;
            
            // Create a new appointment with a date in the future
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            
            const newAppointment = {
                id: newId,
                date: nextWeek.toISOString().split('T')[0],
                time: '10:00:00',
                patient: 'New Patient ' + newId,
                purpose: 'Consultation',
                status: 'Scheduled'
            };
            
            appointments.push(newAppointment);
            renderAppointments();
            showMessage('Demo appointment added successfully!', 'success');
        }

        // Show all appointments (clear search)
        function showAllAppointments() {
            document.getElementById('searchInput').value = '';
            renderAppointments();
        }

        // Clear all appointments
        function clearAppointments() {
            appointments = [];
            renderAppointments();
            showMessage('All appointments cleared.', 'success');
        }
    </script>
</body>
</html>