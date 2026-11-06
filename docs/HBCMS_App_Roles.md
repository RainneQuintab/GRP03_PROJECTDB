# HealthBridge - Application User Roles

HealthBridge has four application roles. Application roles describe what a person can do after logging in.
Database roles describe what a PostgreSQL account can do. They are not the same thing: end users have no
database login, and the backend connects through the runtime account `hb_app`.

| Application role | Responsibilities in the application | Functions and data access | Intended database role |
| :--- | :--- | :--- | :--- |
| **Administrator** | Oversees the clinic and the deleted-records backup. | Everything Staff can do, plus view the Backup, restore deleted records, or delete them permanently; dashboard overview and recent activity. | `hb_admin` (elevated group role, not a superuser) |
| **Staff (front desk)** | Reviews appointment requests and runs the daily queue. | Review requests, assign doctor and time slot, cancel appointments, view/edit patients, add bills and record payments, delete Completed/Cancelled appointments and patients (copied to Backup first). | `hb_staff` |
| **Doctor** | Conducts consultations. | See own queue only; complete a visit with a diagnosis and prescriptions. | `hb_staff` |
| **Patient** | Manages own profile and bookings. | Sign up, sign in, request an appointment, view own visits, prescriptions and bills, pay online, reset password. | `hb_staff` |
| **Application runtime (system account)** | Account the PHP backend uses to connect. | Only the queries the backend needs. | `hb_app` (member of `hb_staff`) |

Patient, Doctor and Staff actions all reach the database through `hb_app`; the PHP code limits each user to
their own data (for example, a patient only sees their own appointments).
