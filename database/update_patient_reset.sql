-- Run ONCE in pgAdmin on your existing grp03_projectdb (connected as postgres).
-- Adds patient password reset, and gives the sample patients Ana and Pedro a login.
-- (A fresh database does not need this file: schema.sql, seed.sql and roles.sql already include it.)

CREATE TABLE IF NOT EXISTS patient_password_resets (
    reset_id   SERIAL PRIMARY KEY,
    patient_id INT          NOT NULL REFERENCES patients(patient_id) ON DELETE CASCADE,
    code_hash  VARCHAR(255) NOT NULL,
    method     VARCHAR(5)   NOT NULL CHECK (method IN ('email', 'phone')),
    expires_at TIMESTAMP    NOT NULL,
    attempts   INT          NOT NULL DEFAULT 0,
    used       BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS patient_password_resets_idx ON patient_password_resets (patient_id, created_at DESC);

GRANT SELECT, INSERT, UPDATE ON patient_password_resets TO hb_staff;
GRANT ALL ON patient_password_resets TO hb_admin;
GRANT USAGE, SELECT ON SEQUENCE patient_password_resets_reset_id_seq TO hb_staff, hb_admin;

-- Sample patients: sign in with HB-00001 / HB-00002 or the email, password Patient#2026 (change it later)
UPDATE patients SET email = 'ana.cruz@example.com',       password_hash = crypt('Patient#2026', gen_salt('bf'))
WHERE patient_id = 1 AND email IS NULL;
UPDATE patients SET email = 'pedro.penduko@example.com',  password_hash = crypt('Patient#2026', gen_salt('bf'))
WHERE patient_id = 2 AND email IS NULL;
