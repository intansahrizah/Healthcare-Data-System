// scripts/verify_deployment.js
const PatientRecordSystem = artifacts.require("PatientRecordSystem");
const MedicalRecord = artifacts.require("MedicalRecord");

module.exports = async function(callback) {
  try {
    const patientSystem = await PatientRecordSystem.deployed();
    const medicalRecord = await MedicalRecord.deployed();
    
    console.log("=== DEPLOYMENT SUCCESSFUL ===");
    console.log("PatientRecordSystem:", patientSystem.address);
    console.log("MedicalRecord:", medicalRecord.address);
    
    // Test basic functionality
    const patientAdmin = await patientSystem.admin();
    const medicalOwner = await medicalRecord.owner();
    
    console.log("\n=== CONTRACT ADMINS ===");
    console.log("PatientRecordSystem Admin:", patientAdmin);
    console.log("MedicalRecord Owner:", medicalOwner);
    
    callback();
  } catch (error) {
    console.error("Deployment verification failed:", error);
    callback(error);
  }
};