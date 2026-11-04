# HealthBridge Clinic Management System

**Course:** COMP 018 - Database Administration  
**Group:** Group 03  
**Members:** 
- Lazarte, Andrei Miguel
- Opiña, Kisha Mae
- Quintab, Rainne T.

**Tech Stack:** HTML5, CSS3, JavaScript, PHP (PDO), PostgreSQL 17

---

## 🏗️ Project Architecture & Tech Stack

- **Backend:** PHP (PDO) managing API routing, database connections, and operational logic.
- **Database:** PostgreSQL 17 (`grp03_projectdb`) running a 6-table normalized schema (`specialties`, `doctors`, `patients`, `appointments`, `medical_notes`, `prescriptions`)[cite: 24, 25].
- **Frontend:** Responsive web interface templates (`HealthBridge-Admin.html`, `HealthBridge-Login.html`, `HealthBridge-Patient.html`, `HealthBridge-Staff.html`).

---

## 📁 Repository Structure

```text
GRP03_PROJECTDB/
├── backend/
│   ├── db.php                  # PostgreSQL PDO connection script
│   └── api/                    # API endpoints (login, appointments, queues)
├── database/
│   ├── schema.sql              # PostgreSQL DDL schema & constraints
│   └── seeds.sql               # Initial sample test data
├── frontend/                   # UI interfaces & client templates
│   ├── HealthBridge-Admin.html
│   ├── HealthBridge-Login.html
│   ├── HealthBridge-Patient.html
│   └── HealthBridge-Staff.html
├── .env.example                # Configuration environment template
└── README.md                   # Project documentation & setup manual
