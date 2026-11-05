-- ============================================================
-- HealthBridge Clinic Management System - Seed Data Script
-- Database: grp03_projectdb (PostgreSQL)
-- ============================================================

INSERT INTO specialties (specialty_name) VALUES 
('General Medicine'),
('Pediatrics'),
('Cardiology'),
('Dermatology');

INSERT INTO doctors (first_name, last_name, specialty_id, email, phone) VALUES 
('Maria', 'Santos', 1, 'maria.santos@healthbridge.com', '09171234567'),
('Juan', 'Reyes', 2, 'juan.reyes@healthbridge.com', '09181234567');

-- sample patients can sign in (patient ID HB-00001 / HB-00002 or the email). Sample password: Patient#2026
INSERT INTO patients (first_name, last_name, date_of_birth, gender, contact_number, address, email, password_hash) VALUES 
('Ana', 'Cruz', '1995-05-12', 'Female', '09223334455', 'San Pedro, Laguna', 'ana.cruz@example.com', crypt('Patient#2026', gen_salt('bf'))),
('Pedro', 'Penduko', '1988-11-20', 'Male', '09334445566', 'Biñan, Laguna', 'pedro.penduko@example.com', crypt('Patient#2026', gen_salt('bf')));

INSERT INTO appointments (patient_id, doctor_id, appointment_date, status) VALUES 
(1, 1, '2026-11-10 09:00:00', 'Scheduled'),
(2, 2, '2026-11-10 10:30:00', 'Scheduled');

INSERT INTO medical_notes (appointment_id, diagnosis, clinical_observations) VALUES 
(1, 'Viral Infection', 'Patient presents with mild fever and cough.'),
(2, 'Routine Pediatric Checkup', 'Child is growing normally with healthy vitals.');

INSERT INTO prescriptions (appointment_id, medication_name, dosage, instructions) VALUES 
(1, 'Paracetamol', '500mg', 'Take 1 tablet every 4 hours as needed for fever.'),
(2, 'Multivitamins Syrup', '5ml', 'Take once daily in the morning.');

-- ---------- starter logins (needed to sign in the first time) ----------
-- !! These passwords are public in this repo. Change them right after the first login. !!
INSERT INTO staff_users (first_name, last_name, email, password_hash, role) VALUES
 ('Clinic', 'Admin', 'admin@healthbridge.com', crypt('Admin#2026!', gen_salt('bf')), 'admin'),
 ('Front',  'Desk',  'staff@healthbridge.com', crypt('Staff#2026!', gen_salt('bf')), 'staff');

-- one doctor login per doctor (email = doctor's email, password Doctor#2026!)
INSERT INTO staff_users (first_name, last_name, email, password_hash, role, doctor_id, phone)
SELECT d.first_name, d.last_name, d.email, crypt('Doctor#2026!', gen_salt('bf')), 'doctor', d.doctor_id, d.phone
FROM doctors d;
