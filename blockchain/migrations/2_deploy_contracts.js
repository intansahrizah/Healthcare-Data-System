const PatientRecordSystem = artifacts.require("PatientRecordSystem");

module.exports = function (deployer) {
  deployer.deploy(PatientRecordSystem);
};