// blockchain/test/ganache-connection-test.js
const Web3 = require('web3');  // Remove the curly braces

async function testConnection() {
    try {
        // Create Web3 instance
        const web3 = new Web3('http://localhost:8545');
        
        console.log('🔄 Testing Ganache connection...');
        
        // Test connection
        const isListening = await web3.eth.net.isListening();
        console.log('✅ Connected to Ganache:', isListening);
        
        // Get accounts
        const accounts = await web3.eth.getAccounts();
        console.log('📋 Accounts found:', accounts.length);
        
        // Check balances
        for (let i = 0; i < Math.min(accounts.length, 3); i++) {
            const balance = await web3.eth.getBalance(accounts[i]);
            console.log(`   Account ${i}: ${web3.utils.fromWei(balance, 'ether')} ETH`);
        }
        
        // Check network
        const networkId = await web3.eth.net.getId();
        console.log('🌐 Network ID:', networkId);
        
        return true;
    } catch (error) {
        console.error('❌ Connection failed:', error.message);
        console.log('💡 Make sure Ganache is running on port 8545');
        return false;
    }
}

testConnection();