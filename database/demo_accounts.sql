-- Run LAST on a fresh database. TEST ACCOUNTS ONLY - every password below is: password123
-- Safe to run more than once.

UPDATE specialties SET specialty_name = 'General Practice' WHERE specialty_name = 'General Medicine';

-- Dr. Carlo Dizon (Cardiology)
INSERT INTO doctors (first_name, last_name, specialty_id, email, phone)
SELECT 'Carlo', 'Dizon', specialty_id, 'carlo.dizon@healthbridge.com', '09201234567'
FROM specialties WHERE specialty_name = 'Cardiology'
ON CONFLICT (email) DO NOTHING;

INSERT INTO staff_users (first_name, last_name, email, password_hash, role, doctor_id, phone)
SELECT d.first_name, d.last_name, d.email, crypt('password123', gen_salt('bf')), 'doctor', d.doctor_id, d.phone
FROM doctors d
WHERE d.email = 'carlo.dizon@healthbridge.com'
  AND NOT EXISTS (SELECT 1 FROM staff_users s WHERE LOWER(s.email) = LOWER(d.email));

-- one shared test password for every demo login
UPDATE staff_users SET password_hash = crypt('password123', gen_salt('bf'))
WHERE LOWER(email) IN ('admin@healthbridge.com', 'staff@healthbridge.com',
                       'maria.santos@healthbridge.com', 'juan.reyes@healthbridge.com',
                       'carlo.dizon@healthbridge.com');

UPDATE patients SET password_hash = crypt('password123', gen_salt('bf'))
WHERE LOWER(email) IN ('ana.cruz@example.com', 'pedro.penduko@example.com');