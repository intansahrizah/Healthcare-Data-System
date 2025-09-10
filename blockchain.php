<?php
function storeOnBlockchain($patientData) {
    // For Hyperledger Fabric
    $ch = curl_init('http://localhost:3000/api/registerPatient'); // Node.js middleware
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($patientData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response);
}