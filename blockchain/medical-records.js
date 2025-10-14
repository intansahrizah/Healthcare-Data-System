const { Web3 } = require('web3');

class MedicalRecordManager {
    constructor() {
        this.web3 = new Web3('http://127.0.0.1:7545');
        this.accounts = [];
    }
    
    async initialize() {
        this.accounts = await this.web3.eth.getAccounts();
        console.log('🏥 Medical Record Manager Initialized');
        console.log('Available roles:');
        console.log('  - Hospital:', this.accounts[3]);
        console.log('  - Doctors:', this.accounts[1] + ', ' + this.accounts[2]);
        console.log('  - Patients:', this.accounts[4] + ', ' + this.accounts[5]);
    }
    
    async logMedicalAccess(doctorAddr, patientAddr, recordId) {
        try {
            const receipt = await this.web3.eth.sendTransaction({
                from: doctorAddr,
                to: patientAddr,
                value: '0',
                data: this.web3.utils.asciiToHex(`ACCESS:${recordId}:${Date.now()}`)
            });
            
            console.log(`✅ Medical record ${recordId} access logged`);
            console.log(`   Doctor: ${doctorAddr}`);
            console.log(`   Patient: ${patientAddr}`);
            console.log(`   TX Hash: ${receipt.transactionHash}`);
            
            return receipt;
        } catch (error) {
            console.log('❌ Failed to log access:', error.message);
        }
    }
    
    async getBlockchainInfo() {
        const blockNumber = await this.web3.eth.getBlockNumber();
        const networkId = await this.web3.eth.net.getId();
        
        return {
            blockNumber: blockNumber.toString(),
            networkId: networkId.toString(),
            accounts: this.accounts.length
        };
    }
}

// Demo usage
async function demo() {
    const medicalManager = new MedicalRecordManager();
    await medicalManager.initialize();
    
    console.log('\n--- Testing Medical Record Access ---');
    await medicalManager.logMedicalAccess(
        medicalManager.accounts[1], // Doctor
        medicalManager.accounts[4], // Patient  
        'PATIENT_001'
    );
    
    const info = await medicalManager.getBlockchainInfo();
    console.log('\n--- Blockchain Info ---');
    console.log('Current Block:', info.blockNumber);
    console.log('Total Accounts:', info.accounts);
}

demo();