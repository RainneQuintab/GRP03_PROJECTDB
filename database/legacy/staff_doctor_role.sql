-- Adds a third account type, 'doctor', linked to the doctors table.
-- Run once in pgAdmin on grp03_projectdb (after staff_users.sql and schema.sql/seed.sql).

-- 1) which doctor record this login belongs to
ALTER TABLE staff_users ADD COLUMN IF NOT EXISTS doctor_id INT REFERENCES doctors(doctor_id);

-- 2) allow the new role (and require a doctor_id for doctors)
ALTER TABLE staff_users DROP CONSTRAINT IF EXISTS staff_users_role_check;
ALTER TABLE staff_users ADD CONSTRAINT staff_users_role_check CHECK (role IN ('admin', 'staff', 'doctor'));
ALTER TABLE staff_users DROP CONSTRAINT IF EXISTS staff_users_doctor_link;
ALTER TABLE staff_users ADD CONSTRAINT staff_users_doctor_link CHECK (role <> 'doctor' OR doctor_id IS NOT NULL);

-- one login per doctor
CREATE UNIQUE INDEX IF NOT EXISTS staff_users_doctor_unique ON staff_users (doctor_id) WHERE doctor_id IS NOT NULL;

-- 3) starter doctor logins from the doctors already in the database
--    (email = the doctor's email, password = Doctor#2026!  -> CHANGE before sharing)
INSERT INTO staff_users (first_name, last_name, email, password_hash, role, doctor_id)
SELECT d.first_name, d.last_name, d.email, crypt('Doctor#2026!', gen_salt('bf')), 'doctor', d.doctor_id
FROM doctors d
ON CONFLICT DO NOTHING;
