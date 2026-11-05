# HealthBridge Clinic Management System (`grp03_projectdb`)

**Course:** COMP 018 - Database Administration
**Group:** Group 03
**Members:**

* Lazarte, Andrei Miguel
* Opiña, Kisha Mae
* Quintab, Rainne T.

**Tech Stack:** PHP, PostgreSQL 17, HTML5, CSS3, JavaScript

---

## Project Architecture & Tech Stack

- **Backend & API:** PHP scripts in the `backend/` folder handling login, sessions, sign-up, appointments, password reset, and the database connection (PDO).
- **Database:** PostgreSQL 17 (`grp03_projectdb`) running a normalized schema (specialties, doctors, patients, appointments, medical_notes, prescriptions).
- **Frontend:** Responsive web pages (`HealthBridge-Admin.html`, `HealthBridge-Login.html`, `HealthBridge-Patient.html`, `HealthBridge-Staff.html`).

---

## Repository Structure

```
GRP03_PROJECTDB/
├── backend/      PHP scripts (login, sessions, appointments, sign-up, etc.)
├── database/     SQL files (schema, roles, seed data)
├── docs/         Project documentation
├── frontend/     HTML pages for admin, staff, patient, and login
├── .env.example
└── README.md
```

---

## Setup

1. Install XAMPP and PostgreSQL 17. Start **Apache** and **PostgreSQL**.
2. Put the project folder in `C:\xampp\htdocs\`.
3. In pgAdmin, create the database `grp03_projectdb` and run the SQL files in the `database/` folder (see `docs/Database_Documentation.md` for the order).
4. In the `backend/` folder, copy `config.sample.php` and rename the copy to `config.php`. Fill in your own database password. Never commit this file.
5. Open `http://localhost/GRP03_PROJECTDB/frontend/HealthBridge-Login.html` in your browser.

More details are in the `docs/` folder.
