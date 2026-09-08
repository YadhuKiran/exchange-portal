# Global Student Mobility & Exchange Portal (MVP)

Production-style university exchange portal built with **Core PHP**, **MySQL**, **PDO**, **Tailwind CSS**, and **PHP Sessions**.

## Quick Start (XAMPP)
1. **Start** Apache and MySQL in XAMPP.
2. **Import database** (required):
   - Import `database/database.sql` then `database/migrations/001_enterprise.sql` (port **3307**)
   - **Or browser:** `http://localhost/exchange_portal/install.php` (runs both)
   - **Or PowerShell:**
     ```powershell
     Get-Content database\database.sql -Raw | c:\xampp\mysql\bin\mysql.exe -u root -P 3307 --protocol=TCP
     Get-Content database\migrations\001_enterprise.sql -Raw | c:\xampp\mysql\bin\mysql.exe -u root -P 3307 --protocol=TCP
     ```
3. **Configure** (if needed): edit `config.php` for DB credentials, port (`DB_PORT`, default `3307` for XAMPP), or `BASE_URL`.
4. **Open:** [http://localhost/exchange_portal/](http://localhost/exchange_portal/)

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@exchangeportal.com | password123 |
| Coordinator | james.chen@mit.edu | password123 |
| Student | alex.johnson@student.mit.edu | password123 |

## MVP Features

- Authentication & role-based access (Admin, Coordinator, Student)
- Admin dashboard + analytics
- Student & coordinator dashboards
- CRUD: students, coordinators, universities, courses
- Exchange applications (create, submit, review, approve/reject)
- Document upload & coordinator verification
- In-app notifications

## Folder Structure

```
exchange_portal/
├── admin/           Admin pages
├── coordinator/     Coordinator pages
├── student/         Student pages
├── database/        database.sql
├── includes/        Core helpers, layout
├── uploads/         Uploaded files (secured)
├── config.php
├── login.php
└── index.php
```

## Database Tables (8)

`users`, `students`, `coordinators`, `universities`, `courses`, `applications`, `documents`, `notifications`

## Latest Updates
Minor documentation updates.
