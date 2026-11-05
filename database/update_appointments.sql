-- Run ONCE in pgAdmin on your existing grp03_projectdb (connected as postgres).
-- Adds the rule used by backend/appointments.php and backend/staff_appointments.php:
-- a doctor cannot have two active appointments at the same time (a Cancelled one frees the slot).
-- (A fresh database does not need this file: schema.sql already includes it.)

CREATE UNIQUE INDEX IF NOT EXISTS appointments_doctor_slot
    ON appointments (doctor_id, appointment_date) WHERE status <> 'Cancelled';

-- If this fails with "could not create unique index", two active appointments already share a doctor and time:
--   SELECT doctor_id, appointment_date, COUNT(*) FROM appointments WHERE status <> 'Cancelled'
--   GROUP BY 1, 2 HAVING COUNT(*) > 1;
-- Cancel or move one of each pair, then run this file again.
