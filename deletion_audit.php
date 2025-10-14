<?php
// blockchain_audit.php
require_once 'vendor/autoload.php';

use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;

session_start();

// Blockchain configuration
$ganacheUrl = "http://localhost:8545";
$contractAddress = "YOUR_CONTRACT_ADDRESS";
$contractABI = '[YOUR_CONTRACT_ABI_JSON]';

try {
    // Connect to blockchain
    $web3 = new Web3(new HttpProvider(new HttpRequestManager($ganacheUrl, 10)));
    $contract = new Contract($web3->provider, $contractABI);
    $contract->at($contractAddress);
    
    // Get deletion count from blockchain
    $deletionCount = 0;
    $contract->call('getDeletionCount', function ($err, $result) use (&$deletionCount) {
        if ($err === null) {
            $deletionCount = $result[0]->toString();
        }
    });
    
    // Get deletion records from blockchain
    $blockchainDeletions = [];
    for ($i = 1; $i <= $deletionCount; $i++) {
        $contract->call('getDeletionRecord', $i, function ($err, $result) use (&$blockchainDeletions, $i) {
            if ($err === null) {
                $blockchainDeletions[] = [
                    'deletionId' => $i,
                    'patientId' => $result[0]->toString(),
                    'patientName' => $result[1],
                    'icNumber' => $result[2],
                    'gender' => $result[3],
                    'email' => $result[4],
                    'phone' => $result[5],
                    'homeAddress' => $result[6],
                    'deletedBy' => $result[7],
                    'deletedAt' => $result[8]->toString(),
                    'deletionHash' => $result[9]
                ];
            }
        });
    }
    
} catch (Exception $e) {
    $blockchainError = $e->getMessage();
    $blockchainDeletions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain Deletion Audit - Healthcare System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Add your existing CSS styles here */
        .blockchain-badge {
            background: linear-gradient(45deg, #f7931a, #ff6b6b);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .hash-cell {
            font-family: monospace;
            font-size: 10px;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Your existing sidebar -->
        
        <main class="main-content">
            <div class="header">
                <h2>
                    <i class="fas fa-link"></i> 
                    Blockchain Deletion Audit
                    <span class="blockchain-badge">REAL BLOCKCHAIN</span>
                </h2>
            </div>

            <?php if (isset($blockchainError)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    Blockchain Connection Error: <?php echo htmlspecialchars($blockchainError); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Deletion ID</th>
                            <th>Patient Details</th>
                            <th>Deleted By</th>
                            <th>Deleted At</th>
                            <th>Blockchain Hash</th>
                            <th>Network</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($blockchainDeletions) > 0): ?>
                            <?php foreach ($blockchainDeletions as $record): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($record['deletionId']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($record['patientName']); ?></strong><br>
                                        <small>ID: <?php echo htmlspecialchars($record['patientId']); ?></small><br>
                                        <small>IC: <?php echo htmlspecialchars($record['icNumber']); ?></small>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($record['deletedBy']); ?></code>
                                    </td>
                                    <td>
                                        <?php echo date('Y-m-d H:i:s', $record['deletedAt']); ?>
                                    </td>
                                    <td class="hash-cell" title="<?php echo htmlspecialchars($record['deletionHash']); ?>">
                                        <?php echo substr(htmlspecialchars($record['deletionHash']), 0, 16) . '...'; ?>
                                    </td>
                                    <td>
                                        <span style="color: #27ae60;">
                                            <i class="fas fa-check-circle"></i> Immutable
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 3rem;">
                                    <i class="fas fa-cube" style="font-size: 3rem; margin-bottom: 1rem; color: #7f8c8d;"></i>
                                    <h3>No Blockchain Deletions</h3>
                                    <p>No deletion records found on the blockchain.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>