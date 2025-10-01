<?php
// blockchain_transactions.php
$blockchain_data = json_decode(file_get_contents('http://localhost:3000/api/blockchain-data'), true);
?>
<h2>📋 Blockchain Transaction History</h2>

<div class="transactions">
    <!-- Patient Registrations -->
    <?php foreach($blockchain_data['data']['patients'] as $patient): ?>
    <div class="transaction-item">
        <span class="tx-type">👤 PATIENT REGISTRATION</span>
        <span class="tx-id">ID: <?php echo $patient['patientsId']; ?></span>
        <span class="tx-hash">Hash: <?php echo $patient['blockHash']; ?></span>
        <span class="tx-time"><?php echo $patient['created_at']; ?></span>
    </div>
    <?php endforeach; ?>

    <!-- Appointments -->
    <?php foreach($blockchain_data['data']['appointments'] as $appointment): ?>
    <div class="transaction-item">
        <span class="tx-type">📅 APPOINTMENT</span>
        <span class="tx-id">ID: <?php echo $appointment['appointmentId']; ?></span>
        <span class="tx-hash">Hash: <?php echo $appointment['blockHash']; ?></span>
        <span class="tx-time"><?php echo $appointment['created_at']; ?></span>
    </div>
    <?php endforeach; ?>

    <!-- Medical Records -->
    <?php foreach($blockchain_data['data']['medicalRecords'] as $record): ?>
    <div class="transaction-item">
        <span class="tx-type">🏥 MEDICAL RECORD</span>
        <span class="tx-id">ID: <?php echo $record['history_id']; ?></span>
        <span class="tx-hash">Hash: <?php echo $record['blockHash']; ?></span>
        <span class="tx-time"><?php echo $record['created_at']; ?></span>
    </div>
    <?php endforeach; ?>
</div>