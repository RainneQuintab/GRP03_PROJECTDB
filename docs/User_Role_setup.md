# Database User and Role Setup

These roles are created by `database/roles.sql` (run it as `postgres` after `schema.sql` and `seed.sql`).
Replace `CHANGE_ME` in that file with your own password. No real passwords are committed.

| Database role | Type | Purpose | Privileges |
| --- | --- | --- | --- |
| `hb_admin` | Group role (NOLOGIN, not a superuser) | Elevated administrative role | ALL on all tables and sequences in `public` (and default privileges on future tables) |
| `hb_staff` | Group role (NOLOGIN) | Day-to-day clinic operations | SELECT/INSERT/UPDATE/DELETE on `patients`, `appointments`, `medical_notes`, `prescriptions`, `billing`; SELECT only on `doctors`, `specialties`; SELECT/UPDATE on `staff_users`; SELECT/INSERT/UPDATE on both password-reset tables; SELECT/INSERT/UPDATE/DELETE on `deleted_records`; USAGE on sequences |
| `hb_app` | Login account (runtime) | The account `backend/config.php` uses to connect | Member of `hb_staff` (inherits its privileges); no superuser rights |

## Setup commands (already in roles.sql)
```sql
CREATE ROLE hb_admin;
CREATE ROLE hb_staff;
CREATE USER hb_app WITH PASSWORD 'CHANGE_ME';   -- replace before running

GRANT CONNECT ON DATABASE grp03_projectdb TO hb_admin, hb_staff, hb_app;
GRANT USAGE ON SCHEMA public TO hb_admin, hb_staff, hb_app;
GRANT hb_staff TO hb_app;
```

## Notes
- `billing` is granted to `hb_staff` by `update_billing.sql`, so run `roles.sql` **before** `update_billing.sql`.
- End users (patients, staff, doctors, admins) do **not** have PostgreSQL logins. They sign in through the app
  (`patients` / `staff_users` tables), and the backend always connects as `hb_app`.
- `hb_admin` is created for the elevated administrative role. Currently the backend connects only as `hb_app`.
  Admin-only actions (view backup, restore, delete forever) are enforced in the PHP layer.
  Activity 4 can tighten this by giving admin sessions their own login.
