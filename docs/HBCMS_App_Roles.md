# Database Documentation: HealthBridge Clinic Management System

- **Database name:** `grp03_projectdb`
- **DBMS:** PostgreSQL 17, schema `public`, extension `pgcrypto`
- **Build order on an empty database:** `schema.sql` -> `seed.sql` -> `roles.sql` -> `update_reason.sql` -> `update_note_unique.sql` -> `update_billing.sql` -> `create_views.sql` -> `demo_accounts.sql`

## Tables (11)

| Table | Purpose | Key columns | Keys / constraints |
| --- | --- | --- | --- |
| `specialties` | Lookup of medical fields | `specialty_id`, `specialty_name` | PK; `specialty_name` UNIQUE NOT NULL |
| `doctors` | Doctor profiles | `doctor_id`, `first_name`, `last_name`, `specialty_id`, `email`, `phone` | PK; FK -> `specialties`; `email` UNIQUE |
| `patients` | Patient demographics and portal login | `patient_id`, `first_name`, `last_name`, `date_of_birth`, `gender`, `contact_number`, `address`, `email`, `password_hash` | PK; unique index on `LOWER(email)` |
| `appointments` | Bookings between patients and doctors | `appointment_id`, `patient_id`, `doctor_id`, `appointment_date`, `status`, `reason` | PK; FKs -> `patients`, `doctors`; partial unique index `(doctor_id, appointment_date) WHERE status <> 'Cancelled'` prevents double-booking |
| `medical_notes` | Diagnosis and observations per visit | `note_id`, `appointment_id`, `diagnosis`, `clinical_observations`, `recorded_at` | PK; FK -> `appointments`; one note per appointment (`update_note_unique.sql`) |
| `prescriptions` | Medications per visit | `prescription_id`, `appointment_id`, `medication_name`, `dosage`, `instructions` | PK; FK -> `appointments` |
| `billing` | Bills and payments | `bill_id`, `appointment_id`, `amount`, `status`, `payment_method`, `billed_at`, `paid_at` | PK; `appointment_id` UNIQUE FK; `amount >= 0`; status Unpaid/Paid; `paid_at` set only when Paid |
| `staff_users` | Admin, staff and doctor logins | `user_id`, `first_name`, `last_name`, `email`, `password_hash`, `role`, `doctor_id`, `phone`, `is_active`, `created_at` | PK; `role` IN (admin, staff, doctor); doctor role requires `doctor_id`; unique email/phone/doctor |
| `staff_password_resets` | Staff reset codes (hashed) | `reset_id`, `user_id`, `code_hash`, `method`, `expires_at`, `attempts`, `used`, `created_at` | PK; FK -> `staff_users` ON DELETE CASCADE |
| `patient_password_resets` | Patient reset codes (hashed) | `reset_id`, `patient_id`, `code_hash`, `method`, `expires_at`, `attempts`, `used`, `created_at` | PK; FK -> `patients` ON DELETE CASCADE |
| `deleted_records` | Backup of deleted patients and appointments | `record_id`, `rec_type`, `ref_id`, `label`, `payload` (JSONB), `deleted_by`, `deleted_at` | PK; `rec_type` IN (patient, appointment) |

## Views
`appointment_details`, `visit_records` (created by `create_views.sql`).

## Changes from the original (Activity 2) design
- Added `billing`, `staff_users`, `staff_password_resets`, `patient_password_resets` and `deleted_records` to support payments, staff/doctor/admin logins, password resets and the delete-backup feature.
- Added `appointments.reason` and the status value `Pending` so patients can request a visit before staff assign a doctor.
- `appointment_date` is a TIMESTAMP (date and time slot together) so the partial unique index can block double-booking.

The full DDL is in `database/schema.sql` and the `update_*.sql` files.
