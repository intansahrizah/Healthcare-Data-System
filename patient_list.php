<?php
// Database configuration
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

// Fetch patients from database with blockchain info
$sql = "SELECT patientsId, patientName, ic_number, gender, email, phone, blockchain_address 
        FROM patients 
        ORDER BY patientName";
$result = $conn->query($sql);
$patients = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}
$conn->close();

// Blockchain contract addresses
$blockchainConfig = [
    'patientRecordSystem' => '0x1F572dfb0120c0aa7484EFb84B7B0680DFA51966',
    'medicalRecord' => '0xDb0287AA8061e52D5578C8eDF57729106ad81630',
    'network' => 'Ganache Local (5777)',
    'rpcUrl' => 'http://127.0.0.1:7545'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Database Viewer - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --light: #f5f7fa;
            --dark: #333;
            --white: #ffffff;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --blockchain: #8e44ad;
            --blockchain-light: #9b59b6;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            background: url('https://gov-web-sing.s3.ap-southeast-1.amazonaws.com/uploads/2023/1/Wordpress-featured-images-48-1672795987342.jpg') no-repeat center center fixed;
            color: #333;
            background-size: cover;
        }

        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: var(--secondary);
            color: white;
            padding: 30px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .logo p {
            font-size: 14px;
            opacity: 0.8;
        }

        .nav-menu {
            padding: 0 20px;
        }

        .nav-item {
            margin-bottom: 15px;
            list-style: none;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-item a:hover, .nav-item a.active {
            background-color: var(--primary);
        }

        .nav-item i {
            margin-right: 10px;
            font-size: 18px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .header h2 {
            font-size: 1.8rem;
            color: var(--secondary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h2 i {
            color: var(--primary);
        }

        .btn {
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .btn-blockchain {
            background-color: var(--blockchain);
        }

        .btn-blockchain:hover {
            background-color: var(--blockchain-light);
        }

        /* Blockchain Info Panel */
        .blockchain-panel {
            background: linear-gradient(135deg, var(--blockchain), var(--blockchain-light));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .blockchain-panel h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .blockchain-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .blockchain-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }

        .blockchain-item label {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 0.5rem;
            display: block;
        }

        .blockchain-address {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
            background: rgba(0, 0, 0, 0.2);
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
        }

        .copy-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }

        .copy-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        /* Search and Filter Styles */
        .search-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: var(--white);
            padding: 10px 15px;
            border-radius: 5px;
            box-shadow: var(--shadow);
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            border: none;
            outline: none;
            margin-left: 10px;
            width: 100%;
            background: transparent;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background: var(--white);
            box-shadow: var(--shadow);
        }

        /* Table Styles */
        .table-container {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .patient-table {
            width: 100%;
            border-collapse: collapse;
        }

        .patient-table th {
            background-color: var(--secondary);
            color: white;
            text-align: left;
            padding: 15px;
            font-weight: 500;
        }

        .patient-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .patient-table tr:hover {
            background-color: #f5f5f5;
        }

        .blockchain-badge {
            background-color: var(--blockchain);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-family: 'Courier New', monospace;
            display: inline-block;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .no-blockchain {
            color: #7f8c8d;
            font-style: italic;
            font-size: 0.9rem;
        }

        .action-btns {
            display: flex;
            gap: 10px;
        }

        .action-btns a {
            color: var(--primary);
            transition: color 0.3s;
        }

        .action-btns a:hover {
            color: var(--primary-dark);
        }

        .blockchain-action {
            color: var(--blockchain) !important;
        }

        .blockchain-action:hover {
            color: var(--blockchain-light) !important;
        }

        .no-patients {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .no-patients i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .pagination button:hover {
            background-color: var(--primary-dark);
        }

        .pagination button.active {
            background-color: var(--secondary);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1.5rem;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
                padding-bottom: 0.5rem;
            }
            
            .nav-item {
                margin-right: 1rem;
                margin-bottom: 0;
                white-space: nowrap;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .search-box, .filter-select {
                width: 100%;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .patient-table {
                min-width: 800px;
            }
            
            .blockchain-info {
                grid-template-columns: 1fr;
            }
        }
        
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo">
                <h1>Welcome Admin</h1>
                <p>Healthcare Management System</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="dashboard_admin.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="patient_list.php" class="active">
                        <i class="fas fa-user-injured"></i> Patients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="doctor_list.php">
                        <i class="fas fa-user-md"></i> Doctors
                    </a>
                </li>
                <li class="nav-item">
                    <a href="test_appoiment.php">
                        <i class="fas fa-calendar-check"></i> Appointments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="header">
                <h2><i class="fas fa-user-injured"></i> Patient Database</h2>
                <div>
                    <a href="patients_register.php" class="btn">
                        <i class="fas fa-plus"></i> Add New Patient
                    </a>
                    <a href="#" class="btn btn-blockchain" onclick="showBlockchainInfo()">
                        <i class="fas fa-link"></i> Blockchain Info
                    </a>
                </div>
                
            </div>

            <!-- Blockchain Information Panel -->
            <div class="blockchain-panel">
                <h3><i class="fas fa-cube"></i> Blockchain Network Information</h3>
                <div class="blockchain-info">
                    <div class="blockchain-item">
                        <label><i class="fas fa-network-wired"></i> Network</label>
                        <div><?php echo htmlspecialchars($blockchainConfig['network']); ?></div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-hospital-user"></i> Patient Record System</label>
                        <div class="blockchain-address">
                            <?php echo htmlspecialchars($blockchainConfig['patientRecordSystem']); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $blockchainConfig['patientRecordSystem']; ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-file-medical"></i> Medical Record System</label>
                        <div class="blockchain-address">
                            <?php echo htmlspecialchars($blockchainConfig['medicalRecord']); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $blockchainConfig['medicalRecord']; ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                    <div class="blockchain-item">
                        <label><i class="fas fa-server"></i> RPC URL</label>
                        <div class="blockchain-address">
                            <?php echo htmlspecialchars($blockchainConfig['rpcUrl']); ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo $blockchainConfig['rpcUrl']; ?>')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search patients...">
                </div>
                <select class="filter-select" id="rowsPerPage">
                    <option value="5">5 rows per page</option>
                    <option value="10" selected>10 rows per page</option>
                    <option value="20">20 rows per page</option>
                    <option value="50">50 rows per page</option>
                </select>
            </div>

            <div class="table-container">
                <table class="patient-table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>IC Number</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Blockchain Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="patientTableBody">
                        <?php if (count($patients) > 0): ?>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($patient['patientName']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['ic_number']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td>
                                        <?php if (!empty($patient['blockchain_address'])): ?>
                                            <span class="blockchain-badge" title="<?php echo htmlspecialchars($patient['blockchain_address']); ?>">
                                                <?php echo substr($patient['blockchain_address'], 0, 8) . '...' . substr($patient['blockchain_address'], -6); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="no-blockchain">Not on blockchain</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-btns">
                                        <a href="patient_detail.php?patientName=<?php echo urlencode($patient['patientName']); ?>" title="View"><i class="fas fa-eye"></i></a>
                                        <a href="edit_patient.php?patientsId=<?php echo htmlspecialchars($patient['patientsId']); ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="#" class="blockchain-action" title="View on Blockchain" onclick="viewOnBlockchain('<?php echo $patient['blockchain_address']; ?>')">
                                            <i class="fas fa-link"></i>
                                        </a>
                                        <a href="delete_patient.php?patientsId=<?php echo htmlspecialchars($patient['patientsId']); ?>" title="Delete" 
                                        onclick="return confirm('Are you sure you want to delete <?php echo addslashes($patient['patientName']); ?>? This action cannot be undone.')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="no-patients">
                                        <i class="fas fa-user-slash"></i>
                                        <h3>No Patients Found</h3>
                                        <p>No patient records found in the database.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="pagination">
                <button id="prevBtn">&laquo; Previous</button>
                <button class="active">1</button>
                <button>2</button>
                <button>3</button>
                <button id="nextBtn">Next &raquo;</button>
            </div>
        </main>
    </div>

    <script>
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Address copied to clipboard: ' + text);
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }

        // View on blockchain function
        function viewOnBlockchain(address) {
            if (!address) {
                alert('This patient does not have a blockchain address yet.');
                return;
            }
            // In a real application, this would open a block explorer
            alert('Viewing patient on blockchain: ' + address + '\n\nIn a real application, this would open a block explorer like Etherscan.');
        }

        // Show blockchain info
        function showBlockchainInfo() {
            alert('Blockchain Network: <?php echo $blockchainConfig['network']; ?>\n' +
                  'Patient Record System: <?php echo $blockchainConfig['patientRecordSystem']; ?>\n' +
                  'Medical Record System: <?php echo $blockchainConfig['medicalRecord']; ?>\n' +
                  'RPC URL: <?php echo $blockchainConfig['rpcUrl']; ?>');
        }

        // Simple search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#patientTableBody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Rows per page functionality
        document.getElementById('rowsPerPage').addEventListener('change', function() {
            alert('In a real application, this would refresh the page with the selected number of rows per page.');
        });

        // Pagination functionality
        document.getElementById('prevBtn').addEventListener('click', function() {
            alert('In a real application, this would go to the previous page.');
        });

        document.getElementById('nextBtn').addEventListener('click', function() {
            alert('In a real application, this would go to the next page.');
        });

        // Simple pagination buttons
        const paginationButtons = document.querySelectorAll('.pagination button');
        paginationButtons.forEach(button => {
            button.addEventListener('click', function() {
                paginationButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>