const { Gateway, Wallets } = require('fabric-network');
const express = require('express');
const app = express();

app.use(express.json());

// Hyperledger Fabric connection
async function connectToNetwork() {
    const wallet = await Wallets.newFileSystemWallet('./wallet');
    const gateway = new Gateway();
    await gateway.connect(connectionProfile, {
        wallet,
        identity: 'admin',
        discovery: { enabled: true, asLocalhost: true }
    });
    return gateway;
}

// API endpoint
app.post('/api/registerPatient', async (req, res) => {
    try {
        const gateway = await connectToNetwork();
        const network = await gateway.getNetwork('mychannel');
        const contract = network.getContract('patient-contract');
        
        await contract.submitTransaction(
            'RegisterPatient',
            req.body.patient_id,
            req.body.fullName,
            req.body.icNumber,
            req.body.gender
        );
        
        res.json({ success: true });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

app.listen(3000, () => console.log('Blockchain middleware running'));