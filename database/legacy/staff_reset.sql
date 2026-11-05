-- Password reset support for staff/admin accounts. Run once in pgAdmin on grp03_projectdb
-- (after staff_users.sql).

-- 1) a mobile number per staff account, so a code can be sent by SMS
ALTER TABLE staff_users ADD COLUMN IF NOT EXISTS phone VARCHAR(20);

-- one account per mobile number (NULL allowed). Store as 09171234567.
CREATE UNIQUE INDEX IF NOT EXISTS staff_users_phone_unique ON staff_users (phone) WHERE phone IS NOT NULL;

-- 2) one row per code that was sent (only a hash of the code is stored)
CREATE TABLE IF NOT EXISTS staff_password_resets (
    reset_id    SERIAL PRIMARY KEY,
    user_id     INT          NOT NULL REFERENCES staff_users(user_id) ON DELETE CASCADE,
    code_hash   VARCHAR(255) NOT NULL,
    method      VARCHAR(5)   NOT NULL CHECK (method IN ('email', 'phone')),
    expires_at  TIMESTAMP    NOT NULL,
    attempts    INT          NOT NULL DEFAULT 0,
    used        BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS staff_password_resets_user_idx ON staff_password_resets (user_id, created_at DESC);

-- 3) give the starter accounts a number (CHANGE to real numbers)
-- UPDATE staff_users SET phone = '09171234567' WHERE email = 'admin@healthbridge.com';
-- UPDATE staff_users SET phone = '09181234567' WHERE email = 'staff@healthbridge.com';
