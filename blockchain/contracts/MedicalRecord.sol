// SPDX-License-Identifier: MIT
pragma solidity ^0.8.0;

contract MedicalRecord {
    address public owner;
    uint256 public patientCount;
    uint256 public medicalHistoryCount;
    
    event PatientAdded(uint256 indexed patientId, string patientName, string icNumber);
    event MedicalHistoryAdded(uint256 indexed historyId, uint256 indexed patientId, string diagnosis, string treatment);
    event AccessGranted(uint256 indexed patientId, address grantedTo);
    event AccessRevoked(uint256 indexed patientId, address revokedFrom);
    
    struct Patient {
        uint256 patientId;
        string patientName;
        string icNumber;
        string gender;
        string phone;
        address createdBy;
        uint256 createdAt;
    }
    
    struct MedicalHistory {
        uint256 historyId;
        uint256 patientId;
        string diagnosis;
        string treatment;
        string doctorNotes;
        address createdBy;
        uint256 visitDate;
    }
    
    mapping(uint256 => mapping(address => bool)) public hasAccess;
    mapping(uint256 => Patient) public patients;
    mapping(uint256 => MedicalHistory) public medicalHistories;
    mapping(uint256 => uint256[]) public patientMedicalHistories;
    
    modifier onlyOwner() {
        require(msg.sender == owner, "Only owner can call this function");
        _;
    }
    
    modifier hasPatientAccess(uint256 _patientId) {
        require(
            msg.sender == owner || 
            hasAccess[_patientId][msg.sender] || 
            patients[_patientId].createdBy == msg.sender,
            "No access to this patient's records"
        );
        _;
    }
    
    constructor() {
        owner = msg.sender;
    }
    
    function addPatient(
        string memory _patientName,
        string memory _icNumber,
        string memory _gender,
        string memory _phone
    ) public returns (uint256) {
        require(bytes(_patientName).length > 0, "Patient name cannot be empty");
        require(bytes(_icNumber).length > 0, "IC number cannot be empty");
        
        patientCount++;
        uint256 newPatientId = patientCount;
        
        patients[newPatientId] = Patient({
            patientId: newPatientId,
            patientName: _patientName,
            icNumber: _icNumber,
            gender: _gender,
            phone: _phone,
            createdBy: msg.sender,
            createdAt: block.timestamp
        });
        
        hasAccess[newPatientId][msg.sender] = true;
        emit PatientAdded(newPatientId, _patientName, _icNumber);
        
        return newPatientId;
    }
    
    function addMedicalHistory(
        uint256 _patientId,
        string memory _diagnosis,
        string memory _treatment,
        string memory _doctorNotes
    ) public hasPatientAccess(_patientId) returns (uint256) {
        require(_patientId > 0 && _patientId <= patientCount, "Invalid patient ID");
        
        medicalHistoryCount++;
        uint256 newHistoryId = medicalHistoryCount;
        
        medicalHistories[newHistoryId] = MedicalHistory({
            historyId: newHistoryId,
            patientId: _patientId,
            diagnosis: _diagnosis,
            treatment: _treatment,
            doctorNotes: _doctorNotes,
            createdBy: msg.sender,
            visitDate: block.timestamp
        });
        
        patientMedicalHistories[_patientId].push(newHistoryId);
        emit MedicalHistoryAdded(newHistoryId, _patientId, _diagnosis, _treatment);
        
        return newHistoryId;
    }
    
    // Simplified getters to avoid stack issues
    function getPatientBasicInfo(uint256 _patientId) public view hasPatientAccess(_patientId) returns (uint256, string memory, string memory) {
        require(_patientId > 0 && _patientId <= patientCount, "Invalid patient ID");
        Patient storage patient = patients[_patientId];
        return (patient.patientId, patient.patientName, patient.icNumber);
    }
    
    function getPatientDetails(uint256 _patientId) public view hasPatientAccess(_patientId) returns (string memory, string memory, string memory, address, uint256) {
        require(_patientId > 0 && _patientId <= patientCount, "Invalid patient ID");
        Patient storage patient = patients[_patientId];
        return (patient.gender, patient.phone, patient.icNumber, patient.createdBy, patient.createdAt);
    }
    
    function getMedicalHistoryBasic(uint256 _historyId) public view returns (uint256, uint256, string memory, string memory) {
        require(_historyId > 0 && _historyId <= medicalHistoryCount, "Invalid history ID");
        MedicalHistory storage history = medicalHistories[_historyId];
        require(hasAccess[history.patientId][msg.sender] || patients[history.patientId].createdBy == msg.sender, "No access");
        
        return (history.historyId, history.patientId, history.diagnosis, history.treatment);
    }
    
    function getMedicalHistoryDetails(uint256 _historyId) public view returns (string memory, address, uint256) {
        require(_historyId > 0 && _historyId <= medicalHistoryCount, "Invalid history ID");
        MedicalHistory storage history = medicalHistories[_historyId];
        require(hasAccess[history.patientId][msg.sender] || patients[history.patientId].createdBy == msg.sender, "No access");
        
        return (history.doctorNotes, history.createdBy, history.visitDate);
    }
    
    function getPatientMedicalHistories(uint256 _patientId) public view hasPatientAccess(_patientId) returns (uint256[] memory) {
        return patientMedicalHistories[_patientId];
    }
    
    function grantAccess(uint256 _patientId, address _grantedTo) public hasPatientAccess(_patientId) {
        require(_grantedTo != address(0), "Invalid address");
        hasAccess[_patientId][_grantedTo] = true;
        emit AccessGranted(_patientId, _grantedTo);
    }
    
    function revokeAccess(uint256 _patientId, address _revokedFrom) public hasPatientAccess(_patientId) {
        require(_revokedFrom != address(0), "Invalid address");
        require(_revokedFrom != patients[_patientId].createdBy, "Cannot revoke access from record creator");
        hasAccess[_patientId][_revokedFrom] = false;
        emit AccessRevoked(_patientId, _revokedFrom);
    }
    
    function checkAccess(uint256 _patientId, address _checkAddress) public view returns (bool) {
        return hasAccess[_patientId][_checkAddress] || patients[_patientId].createdBy == _checkAddress;
    }
}