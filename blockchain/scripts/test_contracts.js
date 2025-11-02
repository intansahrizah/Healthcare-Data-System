// scripts/test_contracts.js
const MedicalRecord = artifacts.require("MedicalRecord");
const PatientRecordSystem = artifacts.require("PatientRecordSystem");

module.exports = async function(callback) {
  try {
    console.log("🧪 Testing contract functionality...\n");
    
    const medicalRecord = await MedicalRecord.deployed();
    const patientSystem = await PatientRecordSystem.deployed();
    
    const accounts = await web3.eth.getAccounts();
    const admin = accounts[0];
    
    console.log("✅ Contracts loaded successfully");
    console.log("MedicalRecord:", medicalRecord.address);
    console.log("PatientRecordSystem:", patientSystem.address);
    console.log("Admin account:", admin);
    
    // Test PatientRecordSystem
    console.log("\n📋 Testing PatientRecordSystem...");
    
    // Create a patient
    console.log("Creating patient...");
    await patientSystem.createPatient(
      "John Doe",
      "901231-08-1234",
      "Male",
      "john.doe@email.com",
      "+60123456789",
      "123 Medical Street, Kuala Lumpur",
      { from: admin }
    );
    console.log("✅ Patient created");
    
    // Get patient info
    const patientBasic = await patientSystem.getPatientBasic(1);
    console.log("Patient basic info:", patientBasic);
    
    const activeCount = await patientSystem.getActivePatientCount();
    console.log("Active patients:", activeCount.toString());
    
    // Test MedicalRecord
    console.log("\n📋 Testing MedicalRecord...");
    
    // Add a patient to MedicalRecord
    console.log("Adding patient to MedicalRecord...");
    await medicalRecord.addPatient(
      "John Doe",
      "901231-08-1234", 
      "Male",
      "+60123456789",
      { from: admin }
    );
    console.log("✅ Patient added to MedicalRecord");
    
    // Add medical history
    console.log("Adding medical history...");
    await medicalRecord.addMedicalHistory(
      1,
      "Hypertension Stage 1",
      "Lifestyle modification and medication",
      "Patient needs to monitor BP daily",
      { from: admin }
    );
    console.log("✅ Medical history added");
    
    // Get patient count
    const patientCount = await medicalRecord.patientCount();
    console.log("MedicalRecord patient count:", patientCount.toString());
    
    console.log("\n🎉 ALL TESTS PASSED! Contracts are working correctly!");
    
    callback();
  } catch (error) {
    console.error("❌ Test failed:", error);
    callback(error);
  }
};