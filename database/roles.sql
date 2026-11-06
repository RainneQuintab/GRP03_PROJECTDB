-- ============================================================
-- Database access control. Run AFTER schema.sql and seed.sql.
-- Replace CHANGE_ME with a strong password, and put the same value in backend/config.php.
-- ============================================================

CREATE ROLE hb_admin;
CREATE ROLE hb_staff;
CREATE USER hb_app WITH PASSWORD 'CHANGE_ME';

GRANT CONNECT ON DATABASE grp03_projectdb TO hb_admin, hb_staff, hb_app;
GRANT USAGE ON SCHEMA public TO hb_admin, hb_staff, hb_app;

-- admin: everything
GRANT ALL PRIVILEGES ON ALL TABLES    IN SCHEMA public TO hb_admin;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO hb_admin;

-- staff: clinical data (read/write), reference data (read only)
GRANT SELECT, INSERT, UPDATE ON patients, appointments, medical_notes, prescriptions TO hb_staff;
GRANT SELECT ON doctors, specialties TO hb_staff;

-- login & password-reset tables used by the PHP backend
GRANT SELECT, UPDATE         ON staff_users           TO hb_staff;
GRANT SELECT, INSERT, UPDATE ON staff_password_resets, patient_password_resets TO hb_staff;

GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hb_staff;

-- the PHP backend connects as hb_app, which inherits hb_staff
GRANT hb_staff TO hb_app;

ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO hb_admin;

-- staff may delete clinical records; every delete is backed up first (see deleted_records)
GRANT DELETE ON patients, appointments, medical_notes, prescriptions TO hb_staff;
GRANT SELECT, INSERT, UPDATE, DELETE ON deleted_records TO hb_staff;
GRANT USAGE, SELECT ON SEQUENCE deleted_records_record_id_seq TO hb_staff;