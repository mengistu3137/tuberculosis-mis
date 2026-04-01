-- 1. USERS TABLE (System Staff)
CREATE TABLE users (
    user_id VARCHAR(20) PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Doctor', 'Nurse', 'Lab Technician', 'Pharmacist', 'Clerk',"Radiologis") NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. PATIENTS TABLE (EMR Registry)
CREATE TABLE patients (
    patient_id VARCHAR(20) PRIMARY KEY,
    medical_record_number VARCHAR(30) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender VARCHAR(10) NOT NULL,
    address VARCHAR(255),
    contact_details VARCHAR(100),
    registered_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. MEDICAL VISITS (Encounters)
CREATE TABLE medical_visits (
    visit_id VARCHAR(20) PRIMARY KEY,
    patient_id VARCHAR(20),
    visit_date DATE NOT NULL,
    visit_type VARCHAR(20),
    clinical_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. VITAL SIGNS
CREATE TABLE vital_signs (
    vital_id VARCHAR(20) PRIMARY KEY,
    visit_id VARCHAR(20),
    temperature DECIMAL(4,1),
    blood_pressure VARCHAR(20),
    pulse INT,
    recorded_by VARCHAR(20),
   recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    recorded_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 5. DIAGNOSES
CREATE TABLE diagnoses (
    diagnosis_id VARCHAR(20) PRIMARY KEY,
    visit_id VARCHAR(20),
    doctor_id VARCHAR(20),
    diagnosis_details TEXT NOT NULL,
    diagnosis_date DATE NOT NULL,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 6. TREATMENT PLANS
CREATE TABLE treatment_plans (
    plan_id VARCHAR(20) PRIMARY KEY,
    diagnosis_id VARCHAR(20),
    description TEXT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    FOREIGN KEY (diagnosis_id) REFERENCES diagnoses(diagnosis_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. LABORATORY REQUESTS
CREATE TABLE lab_requests (
    request_id VARCHAR(20) PRIMARY KEY,
    visit_id VARCHAR(20),
    doctor_id VARCHAR(20),
    test_type VARCHAR(100) NOT NULL,
    request_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 8. LABORATORY RESULTS
CREATE TABLE lab_results (
    result_id VARCHAR(20) PRIMARY KEY,
    request_id VARCHAR(20),
    technician_id VARCHAR(20),
    result_details TEXT NOT NULL,
    performed_date DATE NOT NULL,
    FOREIGN KEY (request_id) REFERENCES lab_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 9. PRESCRIPTIONS
CREATE TABLE prescriptions (
    prescription_id VARCHAR(20) PRIMARY KEY,
    visit_id VARCHAR(20),
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    prescribed_by VARCHAR(20),
    is_dispensed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id) ON DELETE CASCADE,
    FOREIGN KEY (prescribed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 10. DISPENSING RECORDS
CREATE TABLE dispensing_records (
    dispense_id VARCHAR(20) PRIMARY KEY,
    prescription_id VARCHAR(20),
    pharmacist_id VARCHAR(20),
    dispense_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE CASCADE,
    FOREIGN KEY (pharmacist_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- 11. DISCHARGES
CREATE TABLE discharges (
    discharge_id VARCHAR(20) PRIMARY KEY,
    patient_id VARCHAR(20),
    visit_id VARCHAR(20),
    summary_of_care TEXT,
    condition_at_discharge TEXT,
    instructions TEXT,
    follow_up_date DATE,
    discharged_by VARCHAR(20),
    discharged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
    FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id),
    FOREIGN KEY (discharged_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Add assignment tracking to Visits (for Doctors)
ALTER TABLE medical_visits ADD COLUMN assigned_doctor_id VARCHAR(20) NULL;
ALTER TABLE medical_visits ADD CONSTRAINT fk_visit_doctor FOREIGN KEY (assigned_doctor_id) REFERENCES users(user_id);

-- Add assignment tracking to Lab Requests (for Technicians)
ALTER TABLE lab_requests ADD COLUMN assigned_tech_id VARCHAR(20) NULL;
ALTER TABLE lab_requests ADD CONSTRAINT fk_lab_tech FOREIGN KEY (assigned_tech_id) REFERENCES users(user_id);

-- Add assignment tracking to Lab Requests (for radiologist)
ALTER TABLE radiology_requests ADD COLUMN assigned_rad_id VARCHAR(20) NULL;
ALTER TABLE radiology_requests ADD CONSTRAINT fk_rad FOREIGN KEY (assigned_rad_id) REFERENCES users(user_id);

-- Add assignment tracking to Vitals/Nursing requests (for Nurses)
ALTER TABLE medical_visits ADD COLUMN assigned_nurse_id VARCHAR(20) NULL;
ALTER TABLE medical_visits ADD CONSTRAINT fk_visit_nurse FOREIGN KEY (assigned_nurse_id) REFERENCES users(user_id);

-- Visit ward assignments
CREATE TABLE visit_ward_assignments (
    visit_id VARCHAR(20) PRIMARY KEY,
    assignment_location VARCHAR(255) NOT NULL,
    assigned_by VARCHAR(20) NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ward_visit FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id) ON DELETE CASCADE,
    CONSTRAINT fk_ward_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Ward assignment requests (workflow queue)
CREATE TABLE visit_ward_assignment_requests (
    visit_id VARCHAR(20) PRIMARY KEY,
    requested_by VARCHAR(20) NOT NULL,
    status ENUM('pending','completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_ward_request_visit FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id) ON DELETE CASCADE,
    CONSTRAINT fk_ward_request_user FOREIGN KEY (requested_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Care-team history (doctor/nurse assignments)
CREATE TABLE visit_care_team_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id VARCHAR(20) NOT NULL,
    staff_id VARCHAR(20) NOT NULL,
    role ENUM('Doctor','Nurse') NOT NULL,
    assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_care_team_visit FOREIGN KEY (visit_id) REFERENCES medical_visits(visit_id) ON DELETE CASCADE,
    CONSTRAINT fk_care_team_staff FOREIGN KEY (staff_id) REFERENCES users(user_id)
) ENGINE=InnoDB;
-- 12. INITIAL ADMIN USER (Email: admin@mattu.edu | Password: admin123)
INSERT INTO users (user_id, email, password_hash, full_name, role, status) 
VALUES ('STF-001', 'admin@mattu.edu', '$2y$10$8V6Fv3GZ1rV6oO5.vXmCBuK0L2P8Z1LzBvGfXmCBuK0L2P8Z1LzBv', 'System Administrator', 'Admin', 'active');
-- First, check the highest user_id to continue from
-- Based on previous data, last was STF-080, so we'll start from STF-081

-- Insert 7 test users with simple passwords for each role
-- Password for all accounts: password123 (hashed with bcrypt)
INSERT INTO users (user_id, email, password_hash, full_name, role, status) VALUES
-- Admin
('STF-081', 'admin.test@hospital.com', '$2y$10$YourBcryptHashHereForPassword123', 'Test Admin User', 'Admin', 'active'),

-- Doctor
('STF-082', 'doctor.test@hospital.com', '$2y$10$YourBcryptHashHereForPassword123', 'Test Doctor User', 'Doctor', 'active'),

-- Nurse
('STF-083', 'nurse.test@hospital.com', '$2y$10$YourBcryptHashHereForPassword123', 'Test Nurse User', 'Nurse', 'active'),

-- Lab Technician
('STF-084', 'lab.test@hospital.com', '$2y$10$YourBcryptHashHereForPassword123', 'Test Lab Technician', 'Lab Technician', 'active'),

-- Pharmacist
('STF-085', 'pharmacist.test@hospital.com', '$2y$10$YourBcryptHashHereForPassword123', 'Test Pharmacist', 'Pharmacist', 'active'),

-- Clerk
('STF-086', 'clerk.test@hospital.com', '$2y$10$YourBcryptHashHereForPassword123', 'Test Clerk User', 'Clerk', 'active'),

-- Radiologist (using 'Radiologis' as per your ENUM)
('STF-087', 'radiologist.test@hospital.com', '$2y$10$YourBcryptHashHereForPassword123', 'Test Radiologist', 'Radiologis', 'active');

/*
note:I will add the clek has the right to acitate the patient visita and deactivate the patient visit file
the doctor can deactivete the patient the visit file if he discharge the patient
*/