package main

import (
    "encoding/json"
    "fmt"
    "github.com/hyperledger/fabric-contract-api-go/contractapi"
)

type Patient struct {
    ID       string `json:"id"`
    FullName string `json:"fullName"`
    ICNumber string `json:"icNumber"`
    Gender   string `json:"gender"`
}

type SmartContract struct {
    contractapi.Contract
}

func (s *SmartContract) RegisterPatient(ctx contractapi.TransactionContextInterface, 
    id string, fullName string, icNumber string, gender string) error {
    
    patient := Patient{
        ID:       id,
        FullName: fullName,
        ICNumber: icNumber,
        Gender:   gender,
    }
    
    patientJSON, err := json.Marshal(patient)
    if err != nil {
        return fmt.Errorf("failed to marshal patient: %v", err)
    }
    
    return ctx.GetStub().PutState(id, patientJSON)
}

func (s *SmartContract) GetPatient(ctx contractapi.TransactionContextInterface, id string) (*Patient, error) {
    patientJSON, err := ctx.GetStub().GetState(id)
    if err != nil {
        return nil, fmt.Errorf("failed to read from world state: %v", err)
    }
    if patientJSON == nil {
        return nil, fmt.Errorf("patient %s does not exist", id)
    }
    
    var patient Patient
    err = json.Unmarshal(patientJSON, &patient)
    if err != nil {
        return nil, fmt.Errorf("failed to unmarshal patient: %v", err)
    }
    
    return &patient, nil
}

func main() {
    chaincode, err := contractapi.NewChaincode(&SmartContract{})
    if err != nil {
        fmt.Printf("Error creating patient chaincode: %s", err.Error())
        return
    }
    
    if err := chaincode.Start(); err != nil {
        fmt.Printf("Error starting patient chaincode: %s", err.Error())
    }
}