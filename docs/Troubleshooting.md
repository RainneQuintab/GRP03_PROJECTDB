# Troubleshooting Guide

### 1. Connection Refused Error (`PQconnectPoll() failed`)
* **Cause**: PostgreSQL service is not running or the port (`5432`) is incorrect.
* **Solution**: Verified Windows Services to ensure the PostgreSQL service was running active on port `5432`.

### 2. Foreign Key Constraint Violation
* **Cause**: Attempting to insert appointment or clinical records before parent records exist.
* **Solution**: Ensured parent `patients` and `doctors` seed data were executed prior to transactional appointment inserts.

### 3. Local Asset or Routing Issues
* **Cause**: Incorrect path configurations or backend server routing misalignments.
* **Solution**: Check server connection logs in `app.py` and verify that the `.env` parameters match local PostgreSQL configurations.
