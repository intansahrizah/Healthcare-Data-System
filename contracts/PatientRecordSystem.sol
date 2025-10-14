// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

/**
 * @title PatientRecordSystem
 * @dev A smart contract to store patient records with blockchain deletion tracking
 */
contract PatientRecordSystem {
    // --- 1. Define the Data Structures ---
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
        string patientName;
        string icNumber;
        string gender;
        string email;
        string phone;
        string homeAddress;
        address deletedBy;
        uint deletedAt;
        bytes32 deletionHash;
    }

    // --- 2. State Variables ---
    mapping(uint => Patient) public patients;
    mapping(uint => DeletionRecord) public deletionRecords;
    uint public nextPatientId = 1;
    uint public nextDeletionId = 1;
    address public admin;

    // --- 3. Events ---
    event PatientCreated(uint patientId, string patientName, string icNumber);
    event PatientUpdated(uint patientId, string patientName);
    event PatientDeleted(uint patientId, string patientName, address deletedBy, uint deletedAt);

    // --- 4. Modifiers ---
    modifier onlyAdmin() {
        require(msg.sender == admin, "Only admin can perform this action");
        _;
    }

    constructor() {
        admin = msg.sender;
    }

    // --- 5. Function to Create Patient ---
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

    // --- 6. Function to Read Patient ---
    function getPatient(uint _id) public view returns (
        uint,
        string memory,
        string memory,
        string memory,
        string memory,
        string memory,
        string memory,
        bool
    ) {
        Patient storage patient = patients[_id];
        require(patient.isActive, "Patient record has been deleted");
        
        return (
            patient.patientId,
            patient.patientName,
            patient.icNumber,
            patient.gender,
            patient.email,
            patient.phone,
            patient.homeAddress,
            patient.isActive
        );
    }

    // --- 7. Function to Delete Patient (Blockchain Record) ---
    function deletePatient(uint _id) public onlyAdmin {
        require(patients[_id].patientId != 0, "Patient does not exist");
        require(patients[_id].isActive, "Patient already deleted");

        Patient storage patient = patients[_id];
        
        // Create deletion hash for integrity
        bytes32 deletionHash = keccak256(abi.encodePacked(
            patient.patientId,
            patient.patientName,
            patient.icNumber,
            patient.gender,
            patient.email,
            patient.phone,
            patient.homeAddress,
            msg.sender,
            block.timestamp
        ));

        // Store deletion record
        deletionRecords[nextDeletionId] = DeletionRecord(
            patient.patientId,
            patient.patientName,
            patient.icNumber,
            patient.gender,
            patient.email,
            patient.phone,
            patient.homeAddress,
            msg.sender,
            block.timestamp,
            deletionHash
        );

        // Mark patient as inactive (soft delete)
        patients[_id].isActive = false;

        emit PatientDeleted(_id, patient.patientName, msg.sender, block.timestamp);
        nextDeletionId++;
    }

    // --- 8. Get Deletion Record ---
    function getDeletionRecord(uint _deletionId) public view returns (
        uint patientId,
        string memory patientName,
        string memory icNumber,
        string memory gender,
        string memory email,
        string memory phone,
        string memory homeAddress,
        address deletedBy,
        uint deletedAt,
        bytes32 deletionHash
    ) {
        DeletionRecord storage record = deletionRecords[_deletionId];
        return (
            record.patientId,
            record.patientName,
            record.icNumber,
            record.gender,
            record.email,
            record.phone,
            record.homeAddress,
            record.deletedBy,
            record.deletedAt,
            record.deletionHash
        );
    }

    // --- 9. Get Active Patient Count ---
    function getActivePatientCount() public view returns (uint) {
        uint count = 0;
        for (uint i = 1; i < nextPatientId; i++) {
            if (patients[i].isActive) {
                count++;
            }
        }
        return count;
    }

    // --- 10. Get Total Deletion Count ---
    function getDeletionCount() public view returns (uint) {
        return nextDeletionId - 1;
    }
}