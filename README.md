# HealthBridge Clinic Management System (`grp03_projectdb`)

A clinic system for patient intake, appointment requests and clinical consultation, built with **PHP (PDO)**, **PostgreSQL** and plain **HTML/CSS/JS** pages.

## Structure

```text
GRP03_PROJECTDB/
├── backend/                    # PHP JSON endpoints
│   ├── config.sample.php       # copy to config.php (git-ignored) and fill in
│   ├── db.php                  # PDO connection (reads config.php)
│   ├── signup.php, login.php   # patient accounts
│   ├── patient_reset.php       # patient forgot ID/password (code by email or SMS)
│   ├── patient_session.php, patient_logout.php  # is the patient signed in? / sign out
│   ├── appointments.php        # a patient's own appointments: list, book, cancel
│   ├── staff_login.php, staff_session.php, staff_logout.php, staff_reset.php   # staff/admin/doctor
│   ├── staff_appointments.php  # appointment queue for staff/admin/doctor: list, assign doctor, reschedule, set status
│   ├── notify.php              # sends reset codes by email (SMTP/PHPMailer) or SMS (Semaphore)
│   └── get_doctors.php
├── database/
│   ├── schema.sql              # all 9 tables (clinical, staff_users, and the two password-reset tables)
│   ├── seed.sql                # specialties, sample data, starter logins
│   ├── roles.sql               # hb_admin, hb_staff, hb_app roles and privileges
│   ├── update_patient_reset.sql # one-time upgrade for a database created before patient reset existed
│   ├── update_appointments.sql  # one-time upgrade: no double-booking of a doctor's time slot
│   └── legacy/                 # old patch scripts, reference only (a new database does not need them)
└── frontend/
    ├── HealthBridge-Login.html        # patient sign in / sign up
    ├── HealthBridge-Patient.html      # patient portal
    ├── HealthBridge-StaffLogin.html   # staff / admin / doctor sign in + password reset
    ├── HealthBridge-staff.html        # staff & doctor dashboard
    └── HealthBridge-admin.html        # admin dashboard
```

## Setup

1. Create the database `grp03_projectdb` in PostgreSQL.
2. Run, in this order: `database/schema.sql`, `database/seed.sql`, `database/roles.sql`
   (edit the `hb_app` password in `roles.sql` first).
3. Copy `backend/config.sample.php` to `backend/config.php` and enter the same `hb_app` password.
   Keep `'demo_mode' => true` while testing: reset codes are shown on screen. For real use set it to `false` and fill in `smtp` (email) and/or `sms` (Semaphore) in the same file; for SMTP also run `composer require phpmailer/phpmailer` inside `backend/`.
   Already have a database from an earlier version? Run `database/update_patient_reset.sql` and `database/update_appointments.sql` once instead of rebuilding.
4. Serve the project folder with PHP (XAMPP/Apache, or `php -S localhost:8000` from the project root) with the `pdo_pgsql` extension enabled.
5. Open `http://localhost:8000/frontend/HealthBridge-Login.html` (patients) or `HealthBridge-StaffLogin.html` (staff).

Starter logins from `seed.sql`: staff `admin@healthbridge.com`, `staff@healthbridge.com` and each doctor's email; sample patients `HB-00001` / `HB-00002` (or `ana.cruz@example.com`, `pedro.penduko@example.com`). The starter passwords are listed in `seed.sql` and are public, so change them after the first login. Staff accounts are added directly in PostgreSQL; there is no staff sign-up.

## Appointment endpoints

Statuses: `Pending` (requested by a patient), `Scheduled`, `Completed`, `Cancelled`. All requests are form-encoded and need a signed-in session.

| Endpoint | Who | What |
|---|---|---|
| `GET appointments.php?action=mine` | patient | own appointments |
| `POST appointments.php` `action=book`, `doctor_id`, `date` (`YYYY-MM-DD HH:MM`) | patient | request an appointment (`Pending`); `409` if the doctor's slot is taken |
| `POST appointments.php` `action=cancel`, `appointment_id` | patient | cancel own `Pending`/`Scheduled` appointment |
| `GET staff_appointments.php?status=&date=` | staff, admin, doctor | the queue (doctors only see their own) |
| `POST staff_appointments.php` `action=update`, `appointment_id`, `status` / `doctor_id` / `date` | staff, admin | set status, assign a doctor, reschedule |
| `POST staff_appointments.php` `action=update`, `appointment_id`, `status=Completed` | doctor | complete their own `Scheduled` appointment |

## Current status

- Working against the database: patient signup/login/password reset, patient session check and sign-out, staff/admin/doctor login and sessions, staff password reset, doctors list, and the appointment endpoints above.
- Still prototype: the patient, staff and admin dashboards keep appointments, notes and prescriptions in browser `localStorage`, not in PostgreSQL. The appointment endpoints exist but the dashboards do not call them yet, and there are no endpoints yet for medical notes or prescriptions.
