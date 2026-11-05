-- Staff and admin accounts for HealthBridge. Run once in pgAdmin on grp03_projectdb.
-- Accounts are created by the clinic (there is no public signup for staff).

CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE IF NOT EXISTS staff_users (
    user_id       SERIAL PRIMARY KEY,
    first_name    VARCHAR(50)  NOT NULL,
    last_name     VARCHAR(50)  NOT NULL,
    email         VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10)  NOT NULL CHECK (role IN ('admin', 'staff')),
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS staff_users_email_unique ON staff_users (LOWER(email));

-- Starter accounts. CHANGE THESE PASSWORDS before showing or sharing the project.
INSERT INTO staff_users (first_name, last_name, email, password_hash, role) VALUES
 ('Clinic', 'Admin', 'admin@healthbridge.com', crypt('Admin#2026!', gen_salt('bf')), 'admin'),
 ('Front',  'Desk',  'staff@healthbridge.com', crypt('Staff#2026!', gen_salt('bf')), 'staff')
ON CONFLICT DO NOTHING;
