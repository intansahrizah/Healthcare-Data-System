<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-box button {
            padding: 12px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 20px;
        }
        
        .patient-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        
        .patient-card {
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid #3498db;
        }
        
        .patient-card:hover {
            background: #e3f2fd;
            transform: translateX(5px);
        }
        
        .patient-card.active {
            background: #d1ecf1;
            border-left-color: #2c3e50;
        }
        
        .patient-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .patient-info {
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .patient-details {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .detail-header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .detail-section {
            margin: 20px 0;
        }
        
        .section-title {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .medication-list, .condition-list, .allergy-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
        }
        
        .list-item {
            background: #e8f4f8;
            padding: 12px;
            border-radius: 6px;
            border-left: 4px solid #3498db;
        }
        
        .emergency {
            background: #ffe6e6;
            border-left-color: #dc3545;
        }
        
        .no-selection {
            text-align: center;
            color: #6c757d;
            padding: 40px;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🏥 Medical Records System</h1>
            <div class="search-box">
                <input type="text" placeholder="Search patients..." id="searchInput">
                <button onclick="searchPatients()">Search</button>
            </div>
        </header>
        
        <div class="dashboard">
            <div class="patient-list" id="patientList">
                <!-- Patient list will be populated by JavaScript -->
            </div>
            
            <div class="patient-details" id="patientDetails">
                <div class="no-selection">
                    <h2>Select a patient to view details</h2>
                    <p>Click on any patient from the list to see their complete medical information</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample patient data
        const patients = [
            {
                id: "P001",
                name: "Sarah Chen",
                age: 28,
                gender: "Female",
                bloodType: "AB+",
                phone: "(555) 123-4567",
                email: "sarah.chen@email.com",
                conditions: ["Migraine", "Seasonal Allergies"],
                medications: [
                    { name: "Sumatriptan", dosage: "50mg", frequency: "As needed" },
                    { name: "Loratadine", dosage: "10mg", frequency: "Daily" }
                ],
                allergies: ["Peanuts", "Dust Mites"],
                lastVisit: "2024-01-20",
                nextAppointment: "2024-03-15",
                emergencyContact: {
                    name: "David Chen",
                    relationship: "Husband",
                    phone: "(555) 987-6543"
                },
                notes: "Patient reports improved migraine frequency with current medication."
            },
            {
                id: "P002",
                name: "Marcus Johnson",
                age: 52,
                gender: "Male",
                bloodType: "O-",
                phone: "(555) 234-5678",
                email: "marcus.j@email.com",
                conditions: ["Hypertension", "High Cholesterol", "Type 2 Diabetes"],
                medications: [
                    { name: "Lisinopril", dosage: "20mg", frequency: "Daily" },
                    { name: "Atorvastatin", dosage: "40mg", frequency: "Daily" },
                    { name: "Metformin", dosage: "500mg", frequency: "Twice daily" }
                ],
                allergies: ["Sulfa drugs", "Shellfish"],
                lastVisit: "2024-01-18",
                nextAppointment: "2024-02-25",
                emergencyContact: {
                    name: "Lisa Johnson",
                    relationship: "Wife",
                    phone: "(555) 876-5432"
                },
                notes: "Blood pressure well controlled. Continue current regimen."
            },
            {
                id: "P003",
                name: "Elena Rodriguez",
                age: 35,
                gender: "Female",
                bloodType: "B+",
                phone: "(555) 345-6789",
                email: "elena.r@email.com",
                conditions: ["Asthma", "Anxiety"],
                medications: [
                    { name: "Albuterol", dosage: "90mcg", frequency: "As needed" },
                    { name: "Sertraline", dosage: "50mg", frequency: "Daily" }
                ],
                allergies: ["Aspirin", "Latex"],
                lastVisit: "2024-01-22",
                nextAppointment: "2024-04-10",
                emergencyContact: {
                    name: "Carlos Rodriguez",
                    relationship: "Brother",
                    phone: "(555) 765-4321"
                },
                notes: "Asthma symptoms well managed. Anxiety improving with current treatment."
            }
        ];

        let selectedPatient = null;

        // Populate patient list
        function renderPatientList(patientsToRender = patients) {
            const patientList = document.getElementById('patientList');
            patientList.innerHTML = '';
            
            patientsToRender.forEach(patient => {
                const card = document.createElement('div');
                card.className = `patient-card ${selectedPatient?.id === patient.id ? 'active' : ''}`;
                card.innerHTML = `
                    <div class="patient-name">${patient.name}</div>
                    <div class="patient-info">ID: ${patient.id} | Age: ${patient.age} | ${patient.gender}</div>
                    <div class="patient-info">Last visit: ${patient.lastVisit}</div>
                `;
                
                card.addEventListener('click', () => {
                    selectedPatient = patient;
                    renderPatientList(); // Re-render to update active state
                    renderPatientDetails();
                });
                
                patientList.appendChild(card);
            });
        }

        // Render patient details
        function renderPatientDetails() {
            const detailsDiv = document.getElementById('patientDetails');
            
            if (!selectedPatient) {
                detailsDiv.innerHTML = `
                    <div class="no-selection">
                        <h2>Select a patient to view details</h2>
                        <p>Click on any patient from the list to see their complete medical information</p>
                    </div>
                `;
                return;
            }

            const p = selectedPatient;
            
            detailsDiv.innerHTML = `
                <div class="detail-header">
                    <h2>${p.name}</h2>
                    <p>Patient ID: ${p.id} | ${p.age} years | ${p.gender} | Blood Type: ${p.bloodType}</p>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title">Contact Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div>${p.phone}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div>${p.email}</div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title">Medical Conditions</h3>
                    <div class="condition-list">
                        ${p.conditions.map(condition => 
                            `<div class="list-item">${condition}</div>`
                        ).join('')}
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title">Current Medications</h3>
                    <div class="medication-list">
                        ${p.medications.map(med => 
                            `<div class="list-item">
                                <strong>${med.name}</strong><br>
                                ${med.dosage} | ${med.frequency}
                            </div>`
                        ).join('')}
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title">Allergies</h3>
                    <div class="allergy-list">
                        ${p.allergies.map(allergy => 
                            `<div class="list-item emergency">${allergy}</div>`
                        ).join('')}
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title">Appointments</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Last Visit</div>
                            <div>${p.lastVisit}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Next Appointment</div>
                            <div>${p.nextAppointment}</div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title">Emergency Contact</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Name</div>
                            <div>${p.emergencyContact.name}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Relationship</div>
                            <div>${p.emergencyContact.relationship}</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div>${p.emergencyContact.phone}</div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title">Clinical Notes</h3>
                    <div class="info-item">
                        <p>${p.notes}</p>
                    </div>
                </div>
            `;
        }

        // Search functionality
        function searchPatients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            if (!searchTerm) {
                renderPatientList();
                return;
            }
            
            const filteredPatients = patients.filter(patient =>
                patient.name.toLowerCase().includes(searchTerm) ||
                patient.id.toLowerCase().includes(searchTerm) ||
                patient.conditions.some(condition => condition.toLowerCase().includes(searchTerm))
            );
            
            renderPatientList(filteredPatients);
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', () => {
            renderPatientList();
            renderPatientDetails();
            
            // Add event listener for search input
            document.getElementById('searchInput').addEventListener('input', searchPatients);
        });
    </script>
</body>
</html>