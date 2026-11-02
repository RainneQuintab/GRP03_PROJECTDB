HealthBridge Clinic Management System (`grp03_projectdb`)

A fully implemented 3-tier clinic management system built to handle patient intake, appointment scheduling, and clinical consultation workflows using Flask (Python), PostgreSQL 17, and interactive HTML/CSS/JS web interfaces.

---

Project Architecture & Tech Stack

Backend: Python Flask API (`app.py`) managing routing, business logic, and API endpoints.
Database: PostgreSQL 17 (`grp03_projectdb`) running a 6-table normalized schema (`specialties`, `doctors`, `patients`, `appointments`, `medical_notes`, `prescriptions`).
Frontend: Responsive web administration and portal interfaces (`HealthBridge-Staff.html`, `HealthBridge-Admin.html`, `HealthBridge-Patient-Portal.html`).

---

Repository Structure

text
grp03_projectdb/
├── app.py                      # Flask backend application controller
├── requirements.txt            # Python environment dependencies
├── README.md                   # Project documentation
├── database/
│   ├── schema.sql              # PostgreSQL DDL table definitions & constraints
│   └── seed.sql                # Initial lookup values & sample records
└── frontend/                   # UI interface components & client templates
    ├── HealthBridge-Staff.html
    ├── HealthBridge-Admin.html
    └── HealthBridge-Patient-Portal.html
