// migrations/2_deploy_contracts.js
const PatientRecordSystem = artifacts.require("PatientRecordSystem");
const MedicalRecord = artifacts.require("MedicalRecord");

module.exports = async function(deployer, network, accounts) {
  console.log("🚀 Starting deployment from account:", accounts[0]);
  console.log("💰 Account balance:", web3.utils.fromWei(await web3.eth.getBalance(accounts[0]), 'ether'), "ETH");
  
  try {
    // Deploy PatientRecordSystem
    console.log("\n📋 Deploying PatientRecordSystem...");
    await deployer.deploy(PatientRecordSystem);
    const patientSystem = await PatientRecordSystem.deployed();
    console.log("✅ PatientRecordSystem deployed at:", patientSystem.address);
    
    // Deploy MedicalRecord
    console.log("📋 Deploying MedicalRecord...");
    await deployer.deploy(MedicalRecord);
    const medicalRecord = await MedicalRecord.deployed();
    console.log("✅ MedicalRecord deployed at:", medicalRecord.address);
    
    console.log("\n🎉 All contracts deployed successfully!");
    console.log("📝 PatientRecordSystem:", patientSystem.address);
    console.log("📝 MedicalRecord:", medicalRecord.address);
    
  } catch (error) {
    console.error("❌ Deployment failed:", error);
    throw error;
  }
};