const { Gateway, Wallets } = require('fabric-network');
const fs = require('fs');
const path = require('path');

async function main() {
    try {
        const args = process.argv.slice(2);
        const functionName = args[0];
        const patientArgs = args.slice(1);

        // Load connection profile
        const ccpPath = path.resolve(__dirname, '..', 'connection.json');
        const ccp = JSON.parse(fs.readFileSync(ccpPath, 'utf8'));

        // Create a new file system based wallet
        const walletPath = path.join(process.cwd(), 'wallet');
        const wallet = await Wallets.newFileSystemWallet(walletPath);

        // Check if user exists in wallet
        const identity = await wallet.get('appUser');
        if (!identity) {
            console.log('Identity not found in wallet');
            process.exit(1);
        }

        // Connect to gateway
        const gateway = new Gateway();
        await gateway.connect(ccp, {
            wallet,
            identity: 'appUser',
            discovery: { enabled: true, asLocalhost: true }
        });

        // Get network and contract
        const network = await gateway.getNetwork('mychannel');
        const contract = network.getContract('patient-chaincode');

        // Submit transaction
        const result = await contract.submitTransaction(functionName, ...patientArgs);
        console.log(JSON.stringify({
            success: true,
            transactionId: contract.getTransaction().getTransactionId(),
            result: result.toString()
        }));

        await gateway.disconnect();
        
    } catch (error) {
        console.log(JSON.stringify({
            success: false,
            error: error.message
        }));
        process.exit(1);
    }
}

main();