GRANT SELECT, INSERT, UPDATE, DELETE ON deleted_records TO hb_staff;
GRANT USAGE, SELECT ON SEQUENCE deleted_records_record_id_seq TO hb_staff;
GRANT DELETE ON patients, appointments, medical_notes, prescriptions TO hb_staff;