-- Activity 4: Database Access Control & Role Setup
-- System: HealthBridge Clinic Management System (grp03_projectdb)
-- ============================================================

CREATE ROLE hb_admin;
CREATE ROLE hb_staff;

CREATE USER hb_app WITH PASSWORD 'HealthBridge2026!Secure';

GRANT CONNECT ON DATABASE grp03_projectdb TO hb_admin, hb_staff, hb_app;
GRANT USAGE ON SCHEMA public TO hb_admin, hb_staff, hb_app;

GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO hb_admin;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO hb_admin;

GRANT SELECT, INSERT, UPDATE ON TABLE patients, appointments, medical_notes, prescriptions TO hb_staff;
GRANT SELECT ON TABLE doctors, specialties TO hb_staff;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO hb_staff;

GRANT hb_staff TO hb_app;

ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT, INSERT, UPDATE ON TABLES TO hb_staff;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO hb_admin;
