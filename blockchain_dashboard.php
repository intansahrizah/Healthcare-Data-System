<!DOCTYPE html>
<html>
<head>
    <title>Healthcare Blockchain Live Dashboard</title>
    <style>
        .blockchain-badge { background: #4CAF50; color: white; padding: 10px; margin: 10px; border-radius: 5px; }
        .transaction { border-left: 4px solid #2196F3; padding: 10px; margin: 5px; background: #f9f9f9; }
    </style>
</head>
<body>
    <h1>🏥 Healthcare Blockchain Live Dashboard</h1>
    
    <div class="blockchain-badge">
        <strong>🔗 REAL ETHEREUM BLOCKCHAIN</strong>
        <div id="blockchain-status">Loading...</div>
    </div>

    <h3>📊 Live Blockchain Data</h3>
    <div id="blockchain-data">Loading...</div>

    <h3>⛓️ Recent Transactions</h3>
    <div id="transactions"></div>

    <script>
        async function updateDashboard() {
            // Get blockchain status
            const status = await fetch('http://localhost:3001/api/blockchain-status').then(r => r.json());
            document.getElementById('blockchain-status').innerHTML = `
                Network: ${status.blockchain.network} | 
                Block: ${status.blockchain.blockNumber} | 
                Status: <span style="color: green">✅ ${status.blockchain.status}</span>
            `;

            // Get blockchain data
            const data = await fetch('http://localhost:3001/api/blockchain-data').then(r => r.json());
            document.getElementById('blockchain-data').innerHTML = `
                <div>Patients on Blockchain: ${data.dataCounts.patients}</div>
                <div>Appointments on Blockchain: ${data.dataCounts.appointments}</div>
                <div>Medical Records on Blockchain: ${data.dataCounts.medicalRecords}</div>
            `;
        }

        setInterval(updateDashboard, 3000);
        updateDashboard();
    </script>
</body>
</html>