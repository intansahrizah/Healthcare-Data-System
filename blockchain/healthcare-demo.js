const { Web3 } = require('web3');

async function healthcareDemo() {
    const web3 = new Web3('http://127.0.0.1:7545');
    const accounts = await web3.eth.getAccounts();
    
    console.log('🏥 Healthcare System Blockchain Demo\n');
    
    // Simulate patient record access
    console.log('1. Patient Record Access Simulation:');
    console.log('   Doctor Address:', accounts[1]);
    console.log('   Patient Address:', accounts[2]);
    console.log('   Hospital Address:', accounts[3]);
    
    // Simulate medical data transaction
    console.log('\n2. Simulating Medical Data Transaction...');
    
    try {
        const receipt = await web3.eth.sendTransaction({
            from: accounts[1], // Doctor
            to: accounts[3],   // Hospital
            value: web3.utils.toWei('0.001', 'ether'),
            data: web3.utils.asciiToHex('PatientRecord:Access:12345')
        });
        
        console.log('   ✅ Medical record access logged on blockchain');
        console.log('   Transaction Hash:', receipt.transactionHash);
        
    } catch (error) {
        console.log('   ❌ Transaction failed:', error.message);
    }
    
    // Check final state
    console.log('\n3. Current Blockchain State:');
    const blockNumber = await web3.eth.getBlockNumber();
    console.log('   Latest Block:', blockNumber.toString());
    console.log('   Network ID:', (await web3.eth.net.getId()).toString());
}

healthcareDemo();