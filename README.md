# HealthBridge Clinic Management System

**Course:** COMP 018 - Database Administration  
**Group:** Group 03  
**Members:**

- Lazarte, Andrei Miguel
- Opiña, Kisha Mae
- Quintab, Rainne T.

HealthBridge is a clinic management system. Patients request appointments online, front-desk staff assign doctors, doctors record diagnoses and prescriptions, and admins oversee everything, including a backup of deleted records.

---

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP 8 (JSON API in `backend/`), served by Apache (XAMPP)
- **Database:** PostgreSQL 17 (`grp03_projectdb`)

---

## Features by Role

| Role                   | What they can do                                                                                                                                                  |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Patient**            | Sign up, sign in, request an appointment, view their visits and prescriptions, reset a forgotten password                                                         |
| **Staff (front desk)** | Review requests, assign a doctor and time slot, cancel appointments, edit patients, delete Completed/Cancelled appointments and patients (copied to Backup first) |
| **Doctor**             | See their own queue, complete a visit with a diagnosis and prescriptions                                                                                          |
| **Admin**              | Everything staff can do, plus view the Backup, restore deleted records, or delete them forever                                                                    |

---

## Database

**Tables:** `specialties`, `doctors`, `patients`, `appointments`, `medical_notes`, `prescriptions`, `staff_users`, `staff_password_resets`, `patient_password_resets`, `deleted_records`

**Roles:** `hb_admin` (full access), `hb_staff` (clinical data, limited), `hb_app` (the login the PHP backend uses; inherits `hb_staff`)

**Rules enforced by the database:**

- A doctor cannot have two active appointments at the same time (cancelled ones free the slot).
- Every delete is copied to `deleted_records` first, inside one transaction, so nothing is half-deleted.

---

## Repository Structure

```text
GRP03_PROJECTDB/
├── backend/        PHP API (login, appointments, visit notes, delete, backup)
│   ├── config.sample.php   copy to config.php (config.php is git-ignored)
│   └── db.php              PostgreSQL connection
├── database/
│   ├── schema.sql          tables and constraints
│   ├── seed.sql            sample doctors, patients, starter logins
│   ├── roles.sql           database roles and permissions
│   ├── update_reason.sql   adds the appointment "reason" column
│   ├── create_views.sql    reporting views
│   ├── demo_accounts.sql   test accounts (all passwords: password123)
│   └── legacy/             old one-time scripts, not needed for a new install
├── docs/           database documentation and role guides
├── frontend/       the five pages (Login, StaffLogin, Patient, Staff, Admin)
└── README.md
```

---

## Setup (about 10 minutes)

### 1. Install

- XAMPP (Apache + PHP) and PostgreSQL 17 with pgAdmin.
- In `C:\xampp\php\php.ini`, make sure these two lines have **no `;` in front**, then restart Apache:
  ```
  extension=pgsql
  extension=pdo_pgsql
  ```

### 2. Put the project in place

Copy the project folder to `C:\xampp\htdocs\GRP03_PROJECTDB`.

### 3. Build the database

In pgAdmin, create an **empty** database named `grp03_projectdb`. Open the Query Tool on it, connected as `postgres`, and run these files **in this order**:

1. `database/schema.sql`
2. `database/seed.sql`
3. `database/roles.sql` (first replace `CHANGE_ME` with a password of your choice)
4. `database/update_reason.sql`
5. `database/create_views.sql`
6. `database/demo_accounts.sql`

### 4. Connect the backend

Copy `backend/config.sample.php` to `backend/config.php`, then set:

- `'user'` to `hb_app`
- `'password'` to the password you chose in step 3

`demo_mode` is `true` by default, which shows password-reset codes on screen instead of sending email or SMS.

### 5. Start and open

Start **Apache** (XAMPP) and **PostgreSQL**, then open:

`http://localhost/GRP03_PROJECTDB/frontend/HealthBridge-Login.html`

---

## Test Accounts (class demo only)

All test passwords are `password123`. They are public in this repo, so never reuse them anywhere real.

| Role                      | Login                                   | Password    |
| ------------------------- | --------------------------------------- | ----------- |
| Admin                     | admin@healthbridge.com                  | password123 |
| Staff (front desk)        | staff@healthbridge.com                  | password123 |
| Doctor (General Practice) | maria.santos@healthbridge.com           | password123 |
| Doctor (Pediatrics)       | juan.reyes@healthbridge.com             | password123 |
| Doctor (Cardiology)       | carlo.dizon@healthbridge.com            | password123 |
| Patient                   | ana.cruz@example.com (or ID `HB-00001`) | password123 |

Patients sign in on `HealthBridge-Login.html`. Staff, admin and doctors sign in on `HealthBridge-StaffLogin.html`.

---

## Things to Try

1. **Patient:** sign in as Ana Cruz and request an appointment.
2. **Staff:** open Requests and assign a doctor and a time slot.
3. **Doctor:** sign in as Dr. Santos, open My queue, and complete the visit with a diagnosis and a prescription.
4. **Staff:** open Patients, click Edit, and delete a Completed or Cancelled appointment. Switch pages and come back; it stays deleted.
5. **Admin:** open Backup, click View on the deleted record, then Restore. The appointment returns under Patients.

---

## Troubleshooting

| Message or symptom                                        | Likely cause and fix                                                                                                            |
| --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| "Server is not configured"                                | `backend/config.php` does not exist. Copy it from `config.sample.php`.                                                          |
| "Database connection failed"                              | Wrong `hb_app` password in `config.php`, PostgreSQL is not running, or `pdo_pgsql` is not enabled in `php.ini`.                 |
| Pages redirect to the login page                          | Apache is not running, or the project is not in `htdocs\GRP03_PROJECTDB`.                                                       |
| "Server error while deleting" or "Server error in Backup" | `roles.sql` was not run (or ran before `deleted_records` existed in `schema.sql`). Re-run the grants at the end of `roles.sql`. |
| Appointment form says the doctor is busy                  | That doctor already has an active appointment at that time. Pick another slot.                                                  |
| Password reset shows the code on screen                   | Expected: `demo_mode` is `true`. Set it to `false` and fill in the SMTP/SMS settings to send real messages.                     |
