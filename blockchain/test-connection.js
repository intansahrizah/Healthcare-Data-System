const { Web3 } = require('web3');

async function testConnection() {
    console.log('🔗 Testing Ganache connection...');
    
    // Connect to Ganache on port 8545
    const web3 = new Web3('http://127.0.0.1:7545');
    
    try {
        // Test connection - get accounts
        const accounts = await web3.eth.getAccounts();
        console.log('✅ Connected to Ganache successfully!');
        console.log('📊 Available accounts:', accounts.length);
        console.log('👤 First account:', accounts[0]);
        
        // Check balance
        const balance = await web3.eth.getBalance(accounts[0]);
        console.log('💰 Balance:', web3.utils.fromWei(balance, 'ether'), 'ETH');
        
        // Check network
        const networkId = await web3.eth.net.getId();
        console.log('🌐 Network ID:', networkId);
        
        // Check latest block
        const blockNumber = await web3.eth.getBlockNumber();
        console.log('📦 Current block:', blockNumber);
        
        return true;
        
    } catch (error) {
        console.error('❌ Connection failed:', error.message);
        console.log('💡 Make sure Ganache is running on port 8545');
        return false;
    }
}

// Run the test
testConnection();