'use strict';

const { Contract } = require('fabric-contract-api');

class HealthcareContract extends Contract {
    
    // Initialize with sample data
    async InitLedger(ctx) {
        console.log('Healthcare Chaincode Initialized');
        
        const patients = [
            {
                patientsId: '1',
                patientName: 'John Doe',
                ic_number: '900101-01-1234',
                gender: 'Male',
                email: 'john@email.com',
                phone: '123-456-7890',
                address: '123 Main Street',
                created_at: new Date().toISOString()
            }
        ];
        
        for (const patient of patients) {
            await ctx.stub.putState(`PATIENT_${patient.patientsId}`, Buffer.from(JSON.stringify(patient)));
            console.log(`Added patient: ${patient.patientsId}`);
        }
    }

    // PATIENT MANAGEMENT - MATCHES YOUR patients TABLE
    async RegisterPatient(ctx, patientsId, patientName, ic_number, gender, email, phone, address) {
        const exists = await this.PatientExists(ctx, patientsId);
        if (exists) {
            throw new Error(`Patient ${patientsId} already exists`);
        }

        const patient = {
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
            updated_at: new Date().toISOString()
        };

        await ctx.stub.putState(`PATIENT_${patientsId}`, Buffer.from(JSON.stringify(patient)));
        return JSON.stringify(patient);
    }

    async GetPatient(ctx, patientsId) {
        const patientJSON = await ctx.stub.getState(`PATIENT_${patientsId}`);
        if (!patientJSON || patientJSON.length === 0) {
            throw new Error(`Patient ${patientsId} does not exist`);
        }
        return patientJSON.toString();
    }

    async PatientExists(ctx, patientsId) {
        const patientJSON = await ctx.stub.getState(`PATIENT_${patientsId}`);
        return patientJSON && patientJSON.length > 0;
    }

    async GetAllPatients(ctx) {
        const allResults = [];
        const iterator = await ctx.stub.getStateByRange('', '');
        let result = await iterator.next();

        while (!result.done) {
            const strValue = Buffer.from(result.value.value.toString()).toString('utf8');
            let record;
            try {
                record = JSON.parse(strValue);
                // Only return patient records
                if (record.patientsId) {
                    allResults.push(record);
                }
            } catch (err) {
                console.log(err);
            }
            result = await iterator.next();
        }
        return JSON.stringify(allResults);
    }

    // DOCTOR MANAGEMENT - MATCHES YOUR doctors TABLE
    async RegisterDoctor(ctx, doctorId, doctorName, shift, on_duty, email, license_hash, phone) {
        const doctor = {
            doctorId,
            doctorName,
            shift,
            on_duty: on_duty === 'true',
            email,
            license_hash,
            phone,
            appointments: [],
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        await ctx.stub.putState(`DOCTOR_${doctorId}`, Buffer.from(JSON.stringify(doctor)));
        return JSON.stringify(doctor);
    }

    async GetDoctor(ctx, doctorId) {
        const doctorJSON = await ctx.stub.getState(`DOCTOR_${doctorId}`);
        if (!doctorJSON || doctorJSON.length === 0) {
            throw new Error(`Doctor ${doctorId} does not exist`);
        }
        return doctorJSON.toString();
    }

    // APPOINTMENT MANAGEMENT - MATCHES YOUR appointments TABLE
    async ScheduleAppointment(ctx, appointmentId, patientsId, appointment_date, appointment_time, doctorId, reason, status) {
        const appointment = {
            appointmentId,
            patientsId,
            appointment_date,
            appointment_time,
            doctorId,
            reason,
            status: status || 'scheduled',
            notes: '',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        // Store appointment
        await ctx.stub.putState(`APPOINTMENT_${appointmentId}`, Buffer.from(JSON.stringify(appointment)));

        // Update patient's appointments
        const patientJSON = await ctx.stub.getState(`PATIENT_${patientsId}`);
        if (patientJSON && patientJSON.length > 0) {
            const patient = JSON.parse(patientJSON.toString());
            patient.appointments.push(appointmentId);
            patient.updated_at = new Date().toISOString();
            await ctx.stub.putState(`PATIENT_${patientsId}`, Buffer.from(JSON.stringify(patient)));
        }

        return JSON.stringify(appointment);
    }

    async GetAppointment(ctx, appointmentId) {
        const appointmentJSON = await ctx.stub.getState(`APPOINTMENT_${appointmentId}`);
        if (!appointmentJSON || appointmentJSON.length === 0) {
            throw new Error(`Appointment ${appointmentId} does not exist`);
        }
        return appointmentJSON.toString();
    }

    // MEDICAL RECORDS - MATCHES YOUR medical_history TABLE
    async AddMedicalRecord(ctx, history_id, patientsId, visit_date, diagnosis, treatment, doctor_notes) {
        const medicalRecord = {
            history_id,
            patientsId,
            visit_date,
            diagnosis,
            treatment,
            doctor_notes,
            created_at: new Date().toISOString()
        };

        await ctx.stub.putState(`MEDICAL_${history_id}`, Buffer.from(JSON.stringify(medicalRecord)));

        // Add to patient's medical history
        const patientJSON = await ctx.stub.getState(`PATIENT_${patientsId}`);
        if (patientJSON && patientJSON.length > 0) {
            const patient = JSON.parse(patientJSON.toString());
            patient.medicalHistory.push(history_id);
            patient.updated_at = new Date().toISOString();
            await ctx.stub.putState(`PATIENT_${patientsId}`, Buffer.from(JSON.stringify(patient)));
        }

        return JSON.stringify(medicalRecord);
    }

    async GetMedicalHistory(ctx, patientsId) {
        const patientJSON = await ctx.stub.getState(`PATIENT_${patientsId}`);
        if (!patientJSON || patientJSON.length === 0) {
            throw new Error(`Patient ${patientsId} does not exist`);
        }

        const patient = JSON.parse(patientJSON.toString());
        const medicalRecords = [];

        for (const history_id of patient.medicalHistory) {
            const recordJSON = await ctx.stub.getState(`MEDICAL_${history_id}`);
            if (recordJSON && recordJSON.length > 0) {
                medicalRecords.push(JSON.parse(recordJSON.toString()));
            }
        }

        return JSON.stringify(medicalRecords);
    }

    // DOCTOR SCHEDULES - MATCHES YOUR doctor_schedules TABLE
    async AddDoctorSchedule(ctx, id, doctor_id, day_of_week, start_time, end_time, is_available) {
        const schedule = {
            id,
            doctor_id,
            day_of_week,
            start_time,
            end_time,
            is_available: is_available === 'true',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString()
        };

        await ctx.stub.putState(`SCHEDULE_${id}`, Buffer.from(JSON.stringify(schedule)));
        return JSON.stringify(schedule);
    }

    async GetDoctorSchedule(ctx, doctor_id) {
        const allResults = [];
        const iterator = await ctx.stub.getStateByRange('', '');
        let result = await iterator.next();

        while (!result.done) {
            const strValue = Buffer.from(result.value.value.toString()).toString('utf8');
            let record;
            try {
                record = JSON.parse(strValue);
                // Only return schedule records for this doctor
                if (record.doctor_id === doctor_id) {
                    allResults.push(record);
                }
            } catch (err) {
                console.log(err);
            }
            result = await iterator.next();
        }
        return JSON.stringify(allResults);
    }
}

module.exports = HealthcareContract;