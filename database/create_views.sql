CREATE OR REPLACE VIEW appointment_details AS
SELECT a.appointment_id,
       a.appointment_date,
       a.status,
       a.reason,
       p.patient_id,
       p.first_name || ' ' || p.last_name AS patient_name,
       d.doctor_id,
       'Dr. ' || d.first_name || ' ' || d.last_name AS doctor_name,
       s.specialty_name
FROM appointments a
JOIN patients p ON p.patient_id = a.patient_id
JOIN doctors d ON d.doctor_id = a.doctor_id
LEFT JOIN specialties s ON s.specialty_id = d.specialty_id
ORDER BY a.appointment_date;

CREATE OR REPLACE VIEW visit_records AS
SELECT a.appointment_id,
       a.appointment_date,
       p.first_name || ' ' || p.last_name AS patient_name,
       'Dr. ' || d.first_name || ' ' || d.last_name AS doctor_name,
       n.diagnosis,
       n.clinical_observations,
       pr.medication_name,
       pr.dosage,
       pr.instructions
FROM appointments a
JOIN patients p ON p.patient_id = a.patient_id
JOIN doctors d ON d.doctor_id = a.doctor_id
JOIN medical_notes n ON n.appointment_id = a.appointment_id
LEFT JOIN prescriptions pr ON pr.appointment_id = a.appointment_id
ORDER BY a.appointment_date, a.appointment_id;