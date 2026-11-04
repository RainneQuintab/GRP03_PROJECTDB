# Database User and Role Setup Guide

Run these commands in PostgreSQL as a superuser to configure the database groups and access levels for the HealthBridge system.

## 1. Create Group Roles
```sql
CREATE ROLE app_runtime_grp;
CREATE ROLE app_admin_grp;
