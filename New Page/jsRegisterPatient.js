// Store patients data in memory
        let patients = [];

        // Register Patient Function
        function registerPatient(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            
            // Create patient object
            const patient = {
                id: Date.now(), // Simple ID generation
                fullName: formData.get('fullName'),
                icNumber: formData.get('icNumber'),
                gender: formData.get('gender'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                address: formData.get('address'),
                registrationDate: new Date().toLocaleDateString()
            };

            // Validate IC Number format
            const icPattern = /^[0-9]{6}-[0-9]{2}-[0-9]{4}$/;
            if (!icPattern.test(patient.icNumber)) {
                alert('Please enter IC Number in correct format: 000000-00-0000');
                return;
            }

            // Check if patient already exists
            const existingPatient = patients.find(p => p.icNumber === patient.icNumber);
            if (existingPatient) {
                alert('A patient with this IC Number is already registered!');
                return;
            }

            // Add patient to storage
            patients.push(patient);
            
            // Show success message
            const successMessage = document.getElementById('successMessage');
            successMessage.style.display = 'block';
            
            // Reset form
            form.reset();
            
            // Hide success message after 3 seconds
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
            
            console.log('Patient registered:', patient);
            console.log('All patients:', patients);
        }

        // Logout Function
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logged out successfully!');
                // In a real application, you would redirect to login page
                // window.location.href = 'login.html';
            }
        }

        // Navigation Functions
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all items
                document.querySelectorAll('.nav-item').forEach(nav => {
                    nav.classList.remove('active');
                });
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Update page title based on navigation
                const pageTitle = document.querySelector('.page-title');
                const itemText = this.querySelector('span').textContent;
                
                if (itemText === 'Patients') {
                    pageTitle.innerHTML = '<span style="margin-right: 10px;">👤</span>Register New Patient';
                } else if (itemText === 'Appointment') {
                    pageTitle.innerHTML = '<span style="margin-right: 10px;">📅</span>Appointment Management';
                    alert('Appointment management feature coming soon!');
                }
            });
        });

        // Format IC Number input
        document.getElementById('icNumber').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            
            if (value.length >= 6) {
                value = value.substring(0, 6) + '-' + value.substring(6);
            }
            if (value.length >= 9) {
                value = value.substring(0, 9) + '-' + value.substring(9, 13);
            }
            
            e.target.value = value;
        });

        // Format phone number
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            e.target.value = value;
        });

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('HealthCare Patient Management System initialized');
        });