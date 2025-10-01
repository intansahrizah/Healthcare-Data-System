const express = require('express');
const app = express();

app.use(express.json());

// Store data in memory (simulates blockchain)
let blockchainData = {
    patients: {},
    appointments: {},
    medicalRecords: {}
};

// PATIENT MANAGEMENT
app.post('/api/registerPatient', (req, res) => {
    try {
        const { patientsId, patientName, ic_number, gender, email, phone, address } = req.body;
        
        blockchainData.patients[patientsId] = {
            patientsId,
            patientName,
            ic_number,
            gender,
            email,
            phone,
            address,
            appointments: [],
            medicalHistory: [],
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            blockHash: 'block_' + Math.random().toString(36).substr(2, 9)
        };
        
        console.log('✅ Patient registered to blockchain:', patientsId);
        res.json({ 
            success: true, 
            message: 'Patient added to blockchain',
            transactionId: 'tx_' + Date.now(),
            patient: blockchainData.patients[patientsId]
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET PATIENT
app.get('/api/patient/:patientsId', (req, res) => {
    const patient = blockchainData.patients[req.params.patientsId];
    if (patient) {
        res.json({ success: true, patient });
    } else {
        res.status(404).json({ success: false, error: 'Patient not found' });
    }
});

// APPOINTMENT MANAGEMENT
app.post('/api/scheduleAppointment', (req, res) => {
    try {
        const { appointmentId, patientsId, appointment_date, appointment_time, doctorId, reason, status } = req.body;
        
        blockchainData.appointments[appointmentId] = {
            appointmentId,
            patientsId,
            appointment_date,
            appointment_time,
            doctorId,
            reason,
            status: status || 'scheduled',
            notes: '',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            blockHash: 'block_' + Math.random().toString(36).substr(2, 9)
        };
        
        // Add to patient's appointments
        if (blockchainData.patients[patientsId]) {
            blockchainData.patients[patientsId].appointments.push(appointmentId);
        }
        
        console.log('✅ Appointment scheduled on blockchain:', appointmentId);
        res.json({ 
            success: true, 
            message: 'Appointment scheduled on blockchain',
            transactionId: 'tx_' + Date.now(),
            appointment: blockchainData.appointments[appointmentId]
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// MEDICAL RECORDS
app.post('/api/addMedicalRecord', (req, res) => {
    try {
        const { history_id, patientsId, visit_date, diagnosis, treatment, doctor_notes } = req.body;
        
        blockchainData.medicalRecords[history_id] = {
            history_id,
            patientsId,
            visit_date,
            diagnosis,
            treatment,
            doctor_notes,
            created_at: new Date().toISOString(),
            blockHash: 'block_' + Math.random().toString(36).substr(2, 9)
        };
        
        // Add to patient's medical history
        if (blockchainData.patients[patientsId]) {
            blockchainData.patients[patientsId].medicalHistory.push(history_id);
        }
        
        console.log('✅ Medical record added to blockchain:', history_id);
        res.json({ 
            success: true, 
            message: 'Medical record added to blockchain',
            transactionId: 'tx_' + Date.now(),
            record: blockchainData.medicalRecords[history_id]
        });
    } catch (error) {
        res.status(500).json({ success: false, error: error.message });
    }
});

// GET ALL DATA (for testing)
app.get('/api/blockchain-data', (req, res) => {
    res.json({
        success: true,
        data: blockchainData
    });
});

app.listen(3000, () => {
    console.log('🏥 Healthcare Blockchain Middleware running on port 3000');
    console.log('📝 Endpoints:');
    console.log('   POST /api/registerPatient');
    console.log('   GET  /api/patient/:id');
    console.log('   POST /api/scheduleAppointment');
    console.log('   POST /api/addMedicalRecord');
    console.log('   GET  /api/blockchain-data');
});

// ADD THIS ROOT ENDPOINT (insert before app.listen)
app.get('/', (req, res) => {
    res.json({ 
        message: '🏥 Healthcare Blockchain API is running!',
        endpoints: [
            'POST /api/registerPatient',
            'GET  /api/patient/:id',
            'POST /api/scheduleAppointment',
            'POST /api/addMedicalRecord',
            'GET  /api/blockchain-data'
        ]
    });
});

