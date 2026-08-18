# PROJECT STATUS



**Project:** Global Student Mobility & Exchange Portal  
**Version:** MVP 1.0  
**Stack:** Core PHP · MySQL (PDO) · Tailwind CSS CDN · PHP Sessions  
**Base URL:** `http://localhost/exchange_portal`  
**Database:** `exchange_portal` on MySQL port **3307**  
**Last updated:** May 2026

---

## Overview

This document tracks implementation progress for the presentation-ready MVP. The application uses file-based routing (direct PHP pages), role-based access control, and an 8-table MySQL schema with seed data.

---

## Completed Modules

| Module | Status | Notes |
|--------|--------|-------|
| **Authentication** | ✅ Complete | Login, logout, student registration, session handling, password hashing |
| **Role-Based Access Control** | ✅ Complete | Roles: `admin`, `coordinator`, `student`; `require_role()` guards on all portal pages |
| **Student Management** | ✅ Complete | Admin CRUD; coordinator read-only list (scoped); student self-registration |
| **Application Management** | ✅ Complete | Create draft, edit, submit; admin/coordinator review; status updates + notifications |
| **Document Upload** | ✅ Complete | Upload with validation; secure download; coordinator approve/reject |
| **Course Management** | ✅ Complete | Admin global CRUD; coordinator CRUD for own university; student catalog (read-only) |
| **Admin Dashboard** | ✅ Complete | Stats, recent applications, quick actions |
| **Coordinator Dashboard** | ✅ Complete | Pending queues, scoped students/applications |
| **Analytics Dashboard** | ✅ Complete | Admin analytics: applications by status/university, document overview |
| **Notifications** | ✅ Complete | In-app notifications; mark read; triggered on key actions |
| **Database & Seeds** | ✅ Complete | `database/database.sql` — 8 tables, demo users & sample records |
| **UI / Layout** | ✅ Complete | SaaS-style sidebar, responsive nav, Tailwind CDN, flash messages |
| **Security (MVP)** | ✅ Complete | PDO prepared statements, CSRF tokens, upload restrictions, `.htaccess` on uploads |
| **Installer** | ✅ Complete | `install.php` for one-time DB import (remove after use) |

---

## Current Modules

These modules are **live in the codebase** and intended for active use/demo:

| Module | Primary paths | Roles |
|--------|---------------|-------|
| Public auth | `login.php`, `register.php`, `logout.php` | Guest / all |
| Admin portal | `/admin/*` | Admin |
| Coordinator portal | `/coordinator/*` | Coordinator |
| Student portal | `/student/*` | Student |
| Shared services | `includes/init.php`, `includes/layout.php`, `download.php` | All authenticated |
| Configuration | `config.php` | — |
| File storage | `uploads/` | Students (write), all roles (read via `download.php`) |

**Core helpers** (`includes/init.php`): database connection, auth, CSRF, flash messages, notifications, file upload handler, status badges, dashboard stats.

---

## Pending Modules

Not implemented in MVP (from original full blueprint or common production needs):

| Module | Priority | Description |
|--------|----------|-------------|
| Course enrollment | High | Students enroll in host courses after approval; capacity tracking |
| Passport / visa management | Medium | Dedicated structured records (separate from generic documents) |
| Transcript module | Medium | Dedicated transcript entity with versioning |
| Application status history | Medium | Audit trail table for status changes |
| Email notifications | Medium | SMTP / mail for application and document events |
| Password reset / forgot password | Medium | Token-based reset flow |
| System settings (DB-driven) | Medium | Key-value admin settings (deadlines, upload limits, branding) |
| Lookup tables | Low | `countries`, `document_types`, `application_statuses` as CRUD entities |
| Reports & export | Low | CSV/PDF reports for coordinators and admin |
| Activity / audit logs | Low | User action logging |
| Pagination | Low | `ITEMS_PER_PAGE` defined but not applied to all lists |
| API layer | Low | REST endpoints for mobile or integrations |
| Email verification | Low | Verify student email on registration |
| Multi-language UI | Low | i18n support |
| Advanced permissions | Low | Fine-grained permission matrix beyond three roles |
| `public/` document root | Low | Move web root to `public/` for production hardening |
| Delete `install.php` in production | — | Security hygiene after DB setup |

---

## Database Tables

**Database name:** `exchange_portal`  
**Charset:** `utf8mb4_unicode_ci`  
**Total tables:** 8

### Entity relationship (simplified)

```
universities ──┬── students ──┬── applications ──┬── documents
               │              │                  │
               ├── coordinators                  │
               └── courses                       notifications
users ─────────┴── (1:1 students / coordinators)
```

### Table reference

| Table | Purpose | Key columns |
|-------|---------|-------------|
| **users** | All login accounts | `role`, `email`, `password_hash`, `first_name`, `last_name`, `status` |
| **students** | Student profile (1:1 user) | `user_id`, `student_number`, `university_id`, `phone` |
| **coordinators** | Coordinator profile (1:1 user) | `user_id`, `university_id`, `department` |
| **universities** | Partner institutions | `name`, `code`, `country`, `city`, `status` |
| **courses** | Courses per university | `university_id`, `code`, `title`, `credits`, `semester`, `capacity`, `status` |
| **applications** | Exchange applications | `student_id`, `home_university_id`, `host_university_id`, `semester`, `status`, `personal_statement`, `coordinator_notes`, `submitted_at` |
| **documents** | Uploaded files | `student_id`, `application_id`, `title`, `file_name`, `file_path`, `file_size`, `status` |
| **notifications** | In-app alerts | `user_id`, `title`, `message`, `is_read` |

### Application status values

`draft` · `submitted` · `under_review` · `approved` · `rejected`

### Document status values

`pending` · `approved` · `rejected`

### Seed data summary

| Entity | Count |
|--------|-------|
| Universities | 5 |
| Users | 8 (1 admin, 2 coordinators, 5 students) |
| Courses | 8 |
| Applications | 5 |
| Documents | 8 |
| Notifications | 8 |

**Demo password (all seed accounts):** `password123`

---

## Routes

All routes are relative to `BASE_URL` = `/exchange_portal`.  
Access control: **Guest** · **Student** · **Coordinator** · **Admin**

### Public / guest

| Route | File | Method | Description |
|-------|------|--------|-------------|
| `/` | `index.php` | GET | Redirect to login or role dashboard |
| `/login.php` | `login.php` | GET, POST | Sign in |
| `/register.php` | `register.php` | GET, POST | Student self-registration |
| `/logout.php` | `logout.php` | GET | Destroy session, redirect to login |
| `/install.php` | `install.php` | GET | One-time database installer (**remove after use**) |

### Shared (authenticated)

| Route | File | Roles | Description |
|-------|------|-------|-------------|
| `/download.php?id={id}` | `download.php` | All | Secure document download |

---

### Admin routes

| Route | File | Description |
|-------|------|-------------|
| `/admin/index.php` | Dashboard | Overview stats, recent applications, quick actions |
| `/admin/analytics.php` | Analytics | Charts/stats by status, university, documents |
| `/admin/students.php` | List students | |
| `/admin/student-form.php` | Create / edit student | `?id=` for edit |
| `/admin/student-delete.php` | Delete student | `?id=` (cascades user) |
| `/admin/coordinators.php` | List coordinators | |
| `/admin/coordinator-form.php` | Create / edit coordinator | `?id=` for edit |
| `/admin/universities.php` | List universities | |
| `/admin/university-form.php` | Create / edit university | `?id=` for edit |
| `/admin/university-delete.php` | Delete university | `?id=` |
| `/admin/courses.php` | List courses | |
| `/admin/course-form.php` | Create / edit course | `?id=` for edit |
| `/admin/course-delete.php` | Delete course | `?id=` |
| `/admin/applications.php` | List all applications | |
| `/admin/application-view.php` | Review / update application | `?id=` |
| `/admin/documents.php` | List all documents | View/download |
| `/admin/notifications.php` | Admin notifications | Mark read, mark all read |

---

### Student routes

| Route | File | Description |
|-------|------|-------------|
| `/student/index.php` | Dashboard | Welcome, stats, recent applications |
| `/student/profile.php` | Profile | Edit name, email, phone, password |
| `/student/applications.php` | List applications | |
| `/student/application-form.php` | Create / edit application | Draft only; submit action |
| `/student/documents.php` | List documents | |
| `/student/document-upload.php` | Upload document | Optional link to application |
| `/student/courses.php` | Course catalog | Read-only, partner universities |
| `/student/notifications.php` | Notifications | Mark as read |

---

### Coordinator routes

| Route | File | Description |
|-------|------|-------------|
| `/coordinator/index.php` | Dashboard | University scope, pending counts |
| `/coordinator/students.php` | Students | Scoped to home/host university |
| `/coordinator/applications.php` | Applications | Non-draft, scoped |
| `/coordinator/application-review.php` | Review application | Update status & notes |
| `/coordinator/documents.php` | Documents | Approve / reject pending |
| `/coordinator/courses.php` | Courses | Own university only |
| `/coordinator/course-form.php` | Create / edit course | `?id=` for edit |
| `/coordinator/notifications.php` | Notifications | Mark as read |

**Coordinator data scope:** Records where the coordinator’s `university_id` matches the student’s home university or the application’s home/host university.

---

## Admin Features

| Feature | Implemented |
|---------|-------------|
| Dashboard with KPI cards | ✅ |
| Analytics (applications by status, by host university, documents) | ✅ |
| Full student CRUD (create user + student profile) | ✅ |
| Full coordinator CRUD | ✅ |
| Full university CRUD | ✅ |
| Full course CRUD (all universities) | ✅ |
| View all applications | ✅ |
| Update application status & coordinator notes | ✅ |
| View all documents | ✅ |
| In-app notifications | ✅ |
| Activate / deactivate users | ✅ |
| Global access (no university scope) | ✅ |

---

## Student Features

| Feature | Implemented |
|---------|-------------|
| Self-registration with home university | ✅ |
| Login / logout | ✅ |
| Dashboard with application & document stats | ✅ |
| Profile management (name, email, phone, password) | ✅ |
| Create exchange application (draft) | ✅ |
| Edit draft application | ✅ |
| Submit application (notifies coordinators) | ✅ |
| View application list & statuses | ✅ |
| Upload documents (PDF, DOC, JPG, etc.) | ✅ |
| Link document to application (optional) | ✅ |
| View / download own documents | ✅ |
| Browse partner university course catalog | ✅ |
| In-app notifications | ✅ |
| Course enrollment | ❌ Pending |
| Dedicated passport / visa / transcript modules | ❌ Pending |

---

## Coordinator Features

| Feature | Implemented |
|---------|-------------|
| Dashboard (pending applications & documents) | ✅ |
| View students (university-scoped) | ✅ |
| View applications (university-scoped) | ✅ |
| Review application — update status | ✅ |
| Add coordinator notes on applications | ✅ |
| Verify documents — approve / reject | ✅ |
| Notify student on document decision | ✅ |
| Course CRUD for assigned university | ✅ |
| In-app notifications | ✅ |
| Generate reports / export | ❌ Pending |
| Request additional documents (workflow) | ❌ Pending (manual via notes only) |

---

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@exchangeportal.com | password123 |
| Coordinator (MIT) | james.chen@mit.edu | password123 |
| Coordinator (Oxford) | emma.walsh@oxford.ac.uk | password123 |
| Student | alex.johnson@student.mit.edu | password123 |
| Student | maria.garcia@student.mit.edu | password123 |

---

## Project Structure

```
exchange_portal/
├── admin/                 # Admin portal pages
├── coordinator/           # Coordinator portal pages
├── student/               # Student portal pages
├── database/
│   └── database.sql       # Schema + seed data
├── includes/
│   ├── init.php           # DB, auth, helpers
│   ├── layout.php         # Dashboard shell + navigation
│   ├── header.php
│   ├── footer.php
│   └── components.php     # Stat cards, UI helpers
├── uploads/               # Document storage
├── config.php             # App & DB configuration
├── index.php              # Entry redirect
├── login.php
├── register.php
├── logout.php
├── download.php
├── install.php            # One-time setup (delete after use)
├── README.md
└── PROJECT_STATUS.md      # This file
```

---

## Configuration

| Setting | Value | File |
|---------|-------|------|
| DB host | `localhost` | `config.php` |
| DB port | `3307` | `config.php` |
| DB name | `exchange_portal` | `config.php` |
| Base URL | `/exchange_portal` | `config.php` |
| Max upload size | 5 MB | `config.php` |
| Allowed extensions | pdf, doc, docx, jpg, jpeg, png | `config.php` |

---

## Known Limitations (MVP)

- Seed document files are metadata only; physical files must be uploaded to test downloads.
- No pagination on large lists despite `ITEMS_PER_PAGE` constant.
- No forgot-password or email delivery.
- Coordinator cannot delete students or applications.
- Student cannot withdraw a submitted application.
- Course catalog is view-only (no enrollment).
- `install.php` should not remain on a production server.

---

## Quick Setup Checklist

- [ ] XAMPP Apache + MySQL running (port **3307**)
- [ ] Import `database/database.sql` or run `install.php` once
- [ ] Confirm `config.php` matches your DB port and `BASE_URL`
- [ ] Open `http://localhost/exchange_portal/login.php`
- [ ] Delete `install.php` after successful setup

---

*For setup instructions, see [README.md](README.md).*
