# Database Documentation: HealthBridge Clinic Management System

* **Database Name**: `grp03_projectdb`
* **DBMS**: PostgreSQL 17

## Tables & Schemas

### 1. `specialties`
* Parent lookup table for medical fields (e.g., Pediatrics, Cardiology).
* **Columns**: `specialty_id` (PK), `specialty_name` (VARCHAR).

### 2. `doctors`
* Stores professional credentials and profiles linked to specialties.
* **Columns**: `doctor_id` (PK), `specialty_id` (FK to `specialties`), `first_name`, `last_name`, `email`.

### 3. `patients`
* Stores personal demographics and account creation timestamps.
* **Columns**: `patient_id` (PK), `first_name`, `last_name`, `phone_number`, `address`, `created_at`.

### 4. `appointments`
* Associative table managing bookings between patients and doctors while preventing double-booking.
* **Columns**: `appointment_id` (PK), `patient_id` (FK), `doctor_id` (FK), `appointment_date`, `time_slot`, `status`.

### 5. `medical_notes`
* Stores doctor diagnoses and clinical observations tied to visits.
* **Columns**: `note_id` (PK), `appointment_id` (FK), `diagnosis`, `clinical_observations`, `recorded_at`.

### 6. `prescriptions`
* Tracks medications, dosages, and instructions linked to specific visits.
* **Columns**: `prescription_id` (PK), `appointment_id` (FK), `medication_name`, `dosage`, `instructions`.

## SQL DDL Implementation Scripts
```sql
CREATE DATABASE grp03_projectdb;
\c grp03_projectdb;

CREATE TABLE specialties (
    specialty_id SERIAL PRIMARY KEY,
    specialty_name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE doctors (
    doctor_id SERIAL PRIMARY KEY,
    specialty_id INT NOT NULL REFERENCES specialties(specialty_id) ON DELETE RESTRICT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE patients (
    patient_id SERIAL PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone_number VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE appointments (
    appointment_id SERIAL PRIMARY KEY,
    patient_id INT NOT NULL REFERENCES patients(patient_id) ON DELETE RESTRICT,
    doctor_id INT NOT NULL REFERENCES doctors(doctor_id) ON DELETE RESTRICT,
    appointment_date DATE NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'Scheduled' CHECK (status IN ('Scheduled', 'Completed', 'Cancelled'))
);

CREATE TABLE medical_notes (
    note_id SERIAL PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    diagnosis TEXT NOT NULL,
    clinical_observations TEXT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE prescriptions (
    prescription_id SERIAL PRIMARY KEY,
    appointment_id INT NOT NULL REFERENCES appointments(appointment_id) ON DELETE CASCADE,
    medication_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    instructions TEXT
);
