# HealthBridge Clinic Management System

**Course:** COMP 018 - Database Administration  
**Group:** Group 03  
**Members:**
* Lazarte, Andrei Miguel
* Opiña, Kisha Mae
* Quintab, Rainne T.

**Tech Stack:** Python, PostgreSQL 17, HTML5, CSS3, JavaScript

---

## 🏥 Project Architecture & Tech Stack

* **Backend & API:** Python (`app.py`) managing routing, database connections, and operational logic.
* **Database:** PostgreSQL 17 (`grp03_projectdb`) running a normalized schema (specialties, doctors, patients, appointments, medical_notes, prescriptions).
* **Frontend:** Responsive web interface templates (`HealthBridge-Admin.html`, `HealthBridge-Login.html`, `HealthBridge-Patient.html`, `HealthBridge-Staff.html`).

---

## 📂 Repository Structure

```text
GRP03_PROJECTDB/
├── database/
│   ├── .gitkeep
│   ├── roles.sql            # PostgreSQL group roles & access levels
│   ├── schema.sql           # PostgreSQL DDL schema & constraints
│   └── seed.sql             # Initial sample test data
├── docs/
│   ├── Database_Documentation.md
│   ├── HBCMS_App_Roles.md
│   ├── Troubleshooting.md
│   └── User_Role_setup.md
├── frontend/
│   ├── HealthBridge-Admin.html
│   ├── HealthBridge-Login.html
│   ├── HealthBridge-Patient.html
│   └── HealthBridge-Staff.html
├── .env.example             # Configuration environment template
├── .gitignore               # Excludes secrets & local cache
├── app.py                   # Main backend application entry point
├── README.md                # Project documentation & setup manual
└── requirements.txt         # Python dependencies
