-- ============================================================
-- HealthBridge Clinic Management System - Schema
-- Database: grp03_projectdb (PostgreSQL)
-- Run order on an EMPTY database:  schema.sql -> seed.sql -> roles.sql
-- ============================================================

CREATE EXTENSION IF NOT EXISTS pgcrypto;   -- used by seed.sql to hash starter passwords

CREATE TABLE specialties (
    specialty_id   SERIAL PRIMARY KEY,
    specialty_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE doctors (
    doctor_id    SERIAL PRIMARY KEY,
    first_name   VARCHAR(50)  NOT NULL,
    last_name    VARCHAR(50)  NOT NULL,
    specialty_id INT          NOT NULL REFERENCES specialties(specialty_id),
    email        VARCHAR(100) UNIQUE NOT NULL,
    phone        VARCHAR(20)
);

CREATE TABLE patients (
    patient_id     SERIAL PRIMARY KEY,
    first_name     VARCHAR(50) NOT NULL,
    last_name      VARCHAR(50) NOT NULL,
    date_of_birth  DATE        NOT NULL,
    gender         VARCHAR(20),
    contact_number VARCHAR(20),
    address        TEXT,
    email          VARCHAR(100),            -- portal login (NULL for walk-in patients)
    password_hash  VARCHAR(255)             -- bcrypt hash from password_hash(); NULL until the patient creates a password
);
-- one portal account per email (case-insensitive); NULL emails allowed
CREATE UNIQUE INDEX patients_email_unique ON patients (LOWER(email));

CREATE TABLE appointments (
    appointment_id   SERIAL PRIMARY KEY,
    patient_id       INT       NOT NULL REFERENCES patients(patient_id),
    doctor_id        INT       NOT NULL REFERENCES doctors(doctor_id),
    appointment_date TIMESTAMP NOT NULL,
    status           VARCHAR(30) DEFAULT 'Scheduled'      -- Pending | Scheduled | Completed | Cancelled
);
-- a doctor cannot have two active appointments at the same time (cancelled ones free the slot)
CREATE UNIQUE INDEX appointments_doctor_slot ON appointments (doctor_id, appointment_date) WHERE status <> 'Cancelled';

CREATE TABLE medical_notes (
    note_id               SERIAL PRIMARY KEY,
    appointment_id        INT  NOT NULL REFERENCES appointments(appointment_id),
    diagnosis             TEXT NOT NULL,
    clinical_observations TEXT,
    recorded_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE prescriptions (
    prescription_id SERIAL PRIMARY KEY,
    appointment_id  INT          NOT NULL REFERENCES appointments(appointment_id),
    medication_name VARCHAR(100) NOT NULL,
    dosage          VARCHAR(50)  NOT NULL,
    instructions    TEXT         NOT NULL
);

-- ---------- staff / admin / doctor logins (created by the clinic, no public signup) ----------
CREATE TABLE staff_users (
    user_id       SERIAL PRIMARY KEY,
    first_name    VARCHAR(50)  NOT NULL,
    last_name     VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10)  NOT NULL CHECK (role IN ('admin', 'staff', 'doctor')),
    doctor_id     INT REFERENCES doctors(doctor_id),     -- required when role = 'doctor'
    phone         VARCHAR(20),                           -- e.g. 09171234567, for SMS reset codes
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT staff_users_doctor_link CHECK (role <> 'doctor' OR doctor_id IS NOT NULL)
);
CREATE UNIQUE INDEX staff_users_email_unique  ON staff_users (LOWER(email));
CREATE UNIQUE INDEX staff_users_phone_unique  ON staff_users (phone)     WHERE phone IS NOT NULL;
CREATE UNIQUE INDEX staff_users_doctor_unique ON staff_users (doctor_id) WHERE doctor_id IS NOT NULL;

-- one row per password-reset code sent (only a hash of the code is stored)
CREATE TABLE staff_password_resets (
    reset_id   SERIAL PRIMARY KEY,
    user_id    INT          NOT NULL REFERENCES staff_users(user_id) ON DELETE CASCADE,
    code_hash  VARCHAR(255) NOT NULL,
    method     VARCHAR(5)   NOT NULL CHECK (method IN ('email', 'phone')),
    expires_at TIMESTAMP    NOT NULL,
    attempts   INT          NOT NULL DEFAULT 0,
    used       BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX staff_password_resets_user_idx ON staff_password_resets (user_id, created_at DESC);

-- one row per patient password-reset code sent (only a hash of the code is stored)
CREATE TABLE patient_password_resets (
    reset_id   SERIAL PRIMARY KEY,
    patient_id INT          NOT NULL REFERENCES patients(patient_id) ON DELETE CASCADE,
    code_hash  VARCHAR(255) NOT NULL,
    method     VARCHAR(5)   NOT NULL CHECK (method IN ('email', 'phone')),
    expires_at TIMESTAMP    NOT NULL,
    attempts   INT          NOT NULL DEFAULT 0,
    used       BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX patient_password_resets_idx ON patient_password_resets (patient_id, created_at DESC);

-- backup copy of deleted patients / appointments (used by backend/staff_delete.php and backup.php)
CREATE TABLE deleted_records (
    record_id  SERIAL PRIMARY KEY,
    rec_type   VARCHAR(20)  NOT NULL CHECK (rec_type IN ('patient', 'appointment')),
    ref_id     INT          NOT NULL,
    label      VARCHAR(150) NOT NULL,
    payload    JSONB        NOT NULL,
    deleted_by VARCHAR(10),
    deleted_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);