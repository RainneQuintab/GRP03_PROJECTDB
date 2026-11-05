-- Run once in pgAdmin / psql on grp03_projectdb before using signup.php

-- 1) room for hashed passwords (a bcrypt hash is ~60 characters)
ALTER TABLE patients ALTER COLUMN password TYPE VARCHAR(255);

-- 2) one account per email (case-insensitive); NULL emails are still allowed
CREATE UNIQUE INDEX IF NOT EXISTS patients_email_unique ON patients (LOWER(email));
