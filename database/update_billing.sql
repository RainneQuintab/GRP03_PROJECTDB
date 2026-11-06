CREATE TABLE IF NOT EXISTS billing (
    bill_id        SERIAL PRIMARY KEY,
    appointment_id INT NOT NULL UNIQUE REFERENCES appointments(appointment_id),
    amount         NUMERIC(10,2) NOT NULL CHECK (amount >= 0),
    status         VARCHAR(10) NOT NULL DEFAULT 'Unpaid' CHECK (status IN ('Unpaid', 'Paid')),
    payment_method VARCHAR(40),
    billed_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at        TIMESTAMP,
    CHECK ((status = 'Paid') = (paid_at IS NOT NULL))
);

DO $$
BEGIN
  IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'hb_staff') THEN
    GRANT SELECT, INSERT, UPDATE, DELETE ON billing TO hb_staff;
    GRANT USAGE, SELECT ON SEQUENCE billing_bill_id_seq TO hb_staff;
  END IF;
END $$;