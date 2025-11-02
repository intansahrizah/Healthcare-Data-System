// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract PatientRecordSystem {
    struct Patient {
        uint patientId;
        string patientName;
        string icNumber;
        string gender;
        string email;
        string phone;
        string homeAddress;
        bool isActive;
    }

    struct DeletionRecord {
        uint patientId;
        address deletedBy;
        uint deletedAt;
        bytes32 deletionHash;
    }

    mapping(uint => Patient) public patients;
    mapping(uint => DeletionRecord) public deletionRecords;
    uint public nextPatientId = 1;
    uint public nextDeletionId = 1;
    address public admin;

    event PatientCreated(uint patientId, string patientName, string icNumber);
    event PatientDeleted(uint patientId, string patientName, address deletedBy);

    modifier onlyAdmin() {
        require(msg.sender == admin, "Only admin can perform this action");
        _;
    }

    constructor() {
        admin = msg.sender;
    }

    function createPatient(
        string memory _name,
        string memory _ic,
        string memory _gender,
        string memory _email,
        string memory _phone,
        string memory _address
    ) public onlyAdmin {
        patients[nextPatientId] = Patient(
            nextPatientId,
            _name,
            _ic,
            _gender,
            _email,
            _phone,
            _address,
            true
        );

        emit PatientCreated(nextPatientId, _name, _ic);
        nextPatientId++;
    }

    // Split into multiple functions to avoid stack issues
    function getPatientBasic(uint _id) public view returns (uint, string memory, string memory, bool) {
        Patient storage patient = patients[_id];
        require(patient.isActive, "Patient record has been deleted");
        return (patient.patientId, patient.patientName, patient.icNumber, patient.isActive);
    }

    function getPatientContact(uint _id) public view returns (string memory, string memory, string memory) {
        Patient storage patient = patients[_id];
        require(patient.isActive, "Patient record has been deleted");
        return (patient.gender, patient.email, patient.phone);
    }

    function getPatientAddress(uint _id) public view returns (string memory) {
        Patient storage patient = patients[_id];
        require(patient.isActive, "Patient record has been deleted");
        return patient.homeAddress;
    }

    function deletePatient(uint _id) public onlyAdmin {
        require(patients[_id].patientId != 0, "Patient does not exist");
        require(patients[_id].isActive, "Patient already deleted");

        Patient storage patient = patients[_id];
        
        bytes32 deletionHash = keccak256(abi.encodePacked(
            patient.patientId,
            patient.patientName,
            block.timestamp
        ));

        deletionRecords[nextDeletionId] = DeletionRecord(
            patient.patientId,
            msg.sender,
            block.timestamp,
            deletionHash
        );

        patients[_id].isActive = false;
        emit PatientDeleted(_id, patient.patientName, msg.sender);
        nextDeletionId++;
    }

    function getDeletionRecord(uint _deletionId) public view returns (uint, address, uint, bytes32) {
        DeletionRecord storage record = deletionRecords[_deletionId];
        return (record.patientId, record.deletedBy, record.deletedAt, record.deletionHash);
    }

    function getActivePatientCount() public view returns (uint) {
        uint count = 0;
        for (uint i = 1; i < nextPatientId; i++) {
            if (patients[i].isActive) {
                count++;
            }
        }
        return count;
    }

    function getDeletionCount() public view returns (uint) {
        return nextDeletionId - 1;
    }
}