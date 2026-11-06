# Troubleshooting Guide

| Message or symptom | Likely cause and fix |
| --- | --- |
| "Server is not configured" | `backend/config.php` does not exist. Copy it from `config.sample.php`. |
| "Database connection failed" / `connection refused` on port 5432 | PostgreSQL is not running (check Windows Services), the `hb_app` password in `config.php` is wrong, or `pdo_pgsql` is not enabled in `php.ini`. |
| Pages redirect to the login page | Apache is not running, or the project is not in `C:\xampp\htdocs\GRP03_PROJECTDB`. |
| "Server error while deleting" or "Server error in Backup" | `roles.sql` was not run, or ran before `deleted_records` existed. Re-run the grants at the end of `roles.sql`. |
| Billing errors / "permission denied for table billing" | `update_billing.sql` ran before `roles.sql`. Run `roles.sql` first, or re-run the two billing grants in `update_billing.sql`. |
| Foreign key violation when inserting test data | Parent rows must exist first. Run `schema.sql` then `seed.sql` before any appointment data. |
| Appointment form says the doctor is busy | That doctor already has an active appointment at that time. Pick another slot. |
| Password reset shows the code on screen | Expected: `demo_mode` is `true` in `config.php`. Set it to `false` and fill in SMTP/SMS settings to send real messages. |
