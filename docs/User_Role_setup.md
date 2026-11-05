# Database User and Role Setup Guide

Run these commands in PostgreSQL as a superuser to configure the database groups and access levels for the HealthBridge system.

## 1. Create Group Roles
```sql
CREATE ROLE app_runtime_grp;
CREATE ROLE app_admin_grp;

CREATE USER healthbridge_app_user WITH PASSWORD 'placeholder_secure_password';
GRANT app_runtime_grp TO healthbridge_app_user;

GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO app_runtime_grp;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO app_runtime_grp;
