const { JsonRpcProvider, Contract } = require('ethers');
const mysql = require('mysql2/promise'); // Using promise-based mysql

// --- 1. GANACHE CONFIG ---
const ganacheUrl = 'http://127.0.0.1:7545';
const provider = new JsonRpcProvider(ganacheUrl);

// Replace with your Smart Contract's ABI and Address
const contractAddress = '0xYourDeployedContractAddress'; 
const contractAbi = [ /* The ABI array from your compiled contract */ ]; 
const myContract = new Contract(contractAddress, contractAbi, provider);

// --- 2. LARAGON DATABASE CONFIG ---
const dbConnection = await mysql.createConnection({
    host: 'localhost',       // Laragon's default host
    user: 'root',            // Laragon's default user
    password: '',            // Laragon's default password (usually empty)
    database: 'your_db_name' // IMPORTANT: Create this DB in Laragon first!
});

console.log('Successfully connected to Ganache and Laragon DB.');

// --- 3. EVENT LISTENING (The Bridge) ---
// Assuming your Solidity contract has an event: `event DataSaved(address indexed user, string data, uint256 blockTime);`
myContract.on('DataSaved', async (user, data, event) => {
    
    // Data received from Ganache
    const txHash = event.log.transactionHash;
    const blockNumber = event.log.blockNumber;

    console.log(`[Event Detected] User: ${user}, Data: ${data}`);

    // Insert the data into your Laragon MySQL database
    try {
        const [result] = await dbConnection.execute(
            'INSERT INTO records (user_address, off_chain_data, tx_hash, block_number) VALUES (?, ?, ?, ?)',
            [user, data, txHash, blockNumber]
        );
        console.log(`[DB Inserted] ID: ${result.insertId}`);
    } catch (error) {
        console.error('Database insertion error:', error);
    }
});

console.log('Bridge is listening for Smart Contract events...');