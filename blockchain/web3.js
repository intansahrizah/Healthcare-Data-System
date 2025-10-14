// Import the web3 library
const Web3 = require('web3');

// --- 1. SETUP: Connect to Ganache ---

// Ganache usually runs on this address and port by default.
const ganacheUrl = 'http://127.0.0.1:7545';
const web3 = new Web3(ganacheUrl);

// --- Contract Details (Replace with your own) ---
// The ABI (Application Binary Interface) defines your contract's functions.
const CONTRACT_ABI = [
    {
        "constant": true,
        "inputs": [],
        "name": "getMessage",
        "outputs": [{"name": "", "type": "string"}],
        "payable": false,
        "stateMutability": "view",
        "type": "function"
    },
    {
        "constant": false,
        "inputs": [{"name": "_newMessage", "type": "string"}],
        "name": "setMessage",
        "outputs": [],
        "payable": false,
        "stateMutability": "nonpayable",
        "type": "function"
    }
];

// The address where your contract is deployed on Ganache.
const CONTRACT_ADDRESS = '0xe834A3e5BE9F8e7676542CcBa074e90114b683C4'; // Replace this

// Create a JavaScript object (instance) of your deployed contract
const simpleContract = new web3.eth.Contract(CONTRACT_ABI, CONTRACT_ADDRESS);


// --- 2. MAIN FUNCTION: Interact with Ganache ---

async function runWeb3Example() {
    try {
        console.log("--- 1. Connection Check ---");
        // Get the current block number to confirm connection
        const blockNumber = await web3.eth.getBlockNumber();
        console.log(`Connected to Ganache. Latest Block: ${blockNumber}`);

        // Get the list of test accounts provided by Ganache
        const accounts = await web3.eth.getAccounts();
        const defaultAccount = accounts[0];
        console.log(`Using Account: ${defaultAccount}`);
        
        // Check the balance of the first account (should be 100 Ether on Ganache)
        const balanceWei = await web3.eth.getBalance(defaultAccount);
        const balanceEth = web3.utils.fromWei(balanceWei, 'ether');
        console.log(`Balance: ${balanceEth} ETH`);

        console.log("\n--- 2. Reading Contract State (View Function) ---");
        
        // CALL: Read data from the contract (does not cost gas)
        let message = await simpleContract.methods.getMessage().call();
        console.log(`Current Message: ${message}`);
        
        console.log("\n--- 3. Writing to Contract (Transaction Function) ---");
        
        const newMessage = "Hello Laragon and Ganache!";

        // SEND: Write data to the contract (requires gas and an account)
        console.log(`Attempting to set message to: "${newMessage}"`);
        
        const receipt = await simpleContract.methods.setMessage(newMessage).send({
            from: defaultAccount, // The account paying the gas fee
            gas: 3000000 // A safe gas limit for local development
        });

        console.log("Transaction successful!");
        console.log(`Transaction Hash: ${receipt.transactionHash}`);

        console.log("\n--- 4. Verify New State ---");

        // CALL again to verify the message was updated
        message = await simpleContract.methods.getMessage().call();
        console.log(`New Message: ${message}`);

    } catch (error) {
        console.error("An error occurred during Web3 interaction:", error.message);
        console.log("\n*** TROUBLESHOOTING TIP ***");
        console.log("Make sure Ganache is running and your CONTRACT_ADDRESS is correct.");
    }
}

// Execute the main function
runWeb3Example();