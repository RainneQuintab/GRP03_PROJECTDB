# HealthBridge – Application User Roles Documentation

## 1. Overview
HealthBridge is a Clinic Management System with four application user roles: Administrator, Staff, Doctor, and Patient. Each role has defined responsibilities, functions, and access restrictions within the application.

## 2. Application User Roles

| Application Role | Responsibilities in the Application | Functions and Data Access | Intended Database Role |
| :--- | :--- | :--- | :--- |
| **Administrator** | Manages user accounts, doctor profiles, system settings, and clinic operations. | Create, view, update, and delete user accounts as authorized; manage clinic services and specialties; oversee appointments and records; view reports and audit logs. | `app_admin_grp` |
| **Staff** | Assists with appointment scheduling, queue management, and daily clinic front-desk operations. | Create and view appointments; update appointment statuses; manage patient information and clinic queues according to assigned permissions. Access to protected medical records is restricted. | `app_runtime_grp` |
| **Doctor** | Handles medical consultations and maintains patient health and prescription records. | View assigned appointments and patient information; create and update medical notes; record diagnoses, clinical observations, and digital prescriptions. | `app_runtime_grp` |
| **Patient** | Manages personal demographics, registered profiles, and clinic appointments. | Register and manage personal profile; book, reschedule, and cancel appointments; view appointment history and relevant medical records and prescriptions. | `app_runtime_grp` |

### Intended Database Roles
`app_runtime_grp` is the proposed PostgreSQL group role for routine backend database operations, with access limited to the permissions required by the application. `app_admin_grp` is the proposed group role for authorized elevated database operations; these database roles are distinct from application login roles and do not automatically grant PostgreSQL superuser access.

## 3. Application Role Restrictions

### Administrator
* User account creation, viewing, updating, and deletion are limited to authorized actions.
* Oversees appointments, doctor profiles, and records, and can view reports and audit logs.

### Staff
* Manages appointments, patient information, and clinic services according to assigned permissions.
* Access to protected medical records is restricted.

### Doctor
* Views assigned appointments and patient information.
* Creates and updates medical records, diagnoses, and prescriptions.

### Patient
* Manages their own personal information and profile details.
* Books, reschedules, and cancels appointments.
* Views appointment history and relevant medical and prescription records for their own visits.

## 4. Summary
HealthBridge uses four application roles to organize access to clinic functions and information. Administrators oversee accounts and clinic operations; Staff assist with appointments and clinic records; Doctors manage consultations and medical information; and Patients manage their appointments and view their relevant records. Access is subject to the permissions and limitations described above.
