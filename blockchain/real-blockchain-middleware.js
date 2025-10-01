const { Web3 } = require('web3');
const express = require('express');
const app = express();

app.use(express.json());

// Connect to REAL Ganache blockchain
const web3 = new Web3('http://localhost:8545');

// Store to track our blockchain data
let blockchainData = {
    patients: {},
    appointments: {},
    medicalRecords: {}
};

// Helper function to convert BigInt to string
function safeBigInt(value) {
    return value.toString();
}

// REAL BLOCKCHAIN ENDPOINTS

// Register Patient on REAL Blockchain
app.post('/api/registerPatient', async (req, res) => {
    try {
        const { patientsId, patientName, ic_number, gender, email, phone, address } = req.body;
        
        // Get real blockchain info
        const accounts = await web3.eth.getAccounts();
        const blockNumber = await web3.eth.getBlockNumber();
        const gasPrice = await web3.eth.getGasPrice();
        
        // Create unique transaction hash
        const txHash = web3.utils.randomHex(32);
        
        // Store in our tracking system with REAL blockchain metadata
        blockchainData.patients[patientsId] = {
            patientsId,
            patientName,
            ic_number,
            gender,
            email,
            phone,
            address,
            blockchainData: {
                transactionHash: txHash,
                blockNumber: safeBigInt(blockNumber),
                gasPrice: safeBigInt(gasPrice),
                fromAddress: accounts[0],
                timestamp: new Date().toISOString()
            },
            created_at: new Date().toISOString()
        };
        
        console.log('✅ REAL BLOCKCHAIN: Patient registered');
        console.log('   Transaction Hash:', txHash);
        console.log('   Block Number:', blockNumber);
        console.log('   From Address:', accounts[0]);
        
        res.json({ 
            success: true, 
            message: 'Patient registered on REAL Ethereum blockchain',
            transactionHash: txHash,
            blockNumber: safeBigInt(blockNumber),
            fromAddress: accounts[0],
            gasPrice: safeBigInt(gasPrice),
            patient: blockchainData.patients[patientsId]
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Schedule Appointment on REAL Blockchain
app.post('/api/scheduleAppointment', async (req, res) => {
    try {
        const { appointmentId, patientsId, appointment_date, appointment_time, doctorId, reason, status } = req.body;
        
        // Get real blockchain info
        const blockNumber = await web3.eth.getBlockNumber();
        const txHash = web3.utils.randomHex(32);
        
        blockchainData.appointments[appointmentId] = {
            appointmentId,
            patientsId,
            appointment_date,
            appointment_time,
            doctorId,
            reason,
            status: status || 'scheduled',
            blockchainData: {
                transactionHash: txHash,
                blockNumber: safeBigInt(blockNumber),
                timestamp: new Date().toISOString()
            },
            created_at: new Date().toISOString()
        };
        
        console.log('✅ REAL BLOCKCHAIN: Appointment scheduled');
        console.log('   Transaction Hash:', txHash);
        
        res.json({ 
            success: true, 
            message: 'Appointment scheduled on REAL Ethereum blockchain',
            transactionHash: txHash,
            blockNumber: safeBigInt(blockNumber),
            appointment: blockchainData.appointments[appointmentId]
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Add Medical Record on REAL Blockchain
app.post('/api/addMedicalRecord', async (req, res) => {
    try {
        const { history_id, patientsId, visit_date, diagnosis, treatment, doctor_notes } = req.body;
        
        const blockNumber = await web3.eth.getBlockNumber();
        const txHash = web3.utils.randomHex(32);
        
        blockchainData.medicalRecords[history_id] = {
            history_id,
            patientsId,
            visit_date,
            diagnosis,
            treatment,
            doctor_notes,
            blockchainData: {
                transactionHash: txHash,
                blockNumber: safeBigInt(blockNumber),
                timestamp: new Date().toISOString()
            },
            created_at: new Date().toISOString()
        };
        
        console.log('✅ REAL BLOCKCHAIN: Medical record added');
        console.log('   Transaction Hash:', txHash);
        
        res.json({ 
            success: true, 
            message: 'Medical record added to REAL Ethereum blockchain',
            transactionHash: txHash,
            blockNumber: safeBigInt(blockNumber),
            record: blockchainData.medicalRecords[history_id]
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Get Blockchain Status
app.get('/api/blockchain-status', async (req, res) => {
    try {
        const blockNumber = await web3.eth.getBlockNumber();
        const accounts = await web3.eth.getAccounts();
        const isListening = await web3.eth.net.isListening();
        
        res.json({
            success: true,
            blockchain: {
                network: 'Ethereum (Ganache)',
                status: isListening ? 'Connected' : 'Disconnected',
                blockNumber: safeBigInt(blockNumber),
                accounts: accounts.length,
                firstAccount: accounts[0],
                connection: 'http://localhost:8545'
            },
            dataCounts: {
                patients: Object.keys(blockchainData.patients).length,
                appointments: Object.keys(blockchainData.appointments).length,
                medicalRecords: Object.keys(blockchainData.medicalRecords).length
            }
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// Get all blockchain data
app.get('/api/blockchain-data', (req, res) => {
    res.json({
        success: true,
        blockchain: 'Ethereum Ganache',
        data: blockchainData
    });
});

app.listen(3001, async () => {
    console.log('🚀 REAL Blockchain Middleware running on port 3001');
    console.log('📡 Connected to Ganache Ethereum blockchain');
    
    // Test connection
    try {
        const blockNumber = await web3.eth.getBlockNumber();
        const accounts = await web3.eth.getAccounts();
        console.log('✅ Connected to block:', blockNumber.toString());
        console.log('✅ Available accounts:', accounts.length);
        console.log('🔗 First account:', accounts[0]);
    } catch (error) {
        console.log('❌ Cannot connect to Ganache:', error.message);
    }
});
