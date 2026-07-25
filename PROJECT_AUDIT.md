# PROJECT AUDIT REPORT

**Project:** Global Student Mobility & Exchange Portal  
**Audit type:** MVP codebase review (static analysis)  
**Auditor role:** Software auditor  
**Audit date:** May 2026  
**Environment:** XAMPP · PHP · MySQL port `3307` · Base path `/exchange_portal`  
**Verdict:** **Presentation-ready MVP** with core workflows functional; not production-hardened.

---

## Executive Summary

The project delivers a **working 3-role exchange portal** (Admin, Coordinator, Student) on **8 database tables**, **33 browsable UI pages**, and **file-based routing**. Strengths include consistent Tailwind UI, PDO usage, CSRF on POST forms, and end-to-end flows for applications and documents. Gaps include **no charting library**, **no enrollment module**, **limited analytics**, seed documents without physical files, and operational risks (`install.php` exposure, no pagination).

| Area | Rating (1–5) | Notes |
|------|----------------|-------|
| Database design | 4 | Clean MVP schema; missing audit/history tables |
| Security | 3 | Good basics; needs production hardening |
| UI/UX | 4 | Professional SaaS appearance |
| Feature completeness | 3 | Core MVP only |
| Analytics | 2 | Counts + CSS bars; no real charts |
| Documentation | 4 | README + PROJECT_STATUS present |

---

## 1. Complete Database Tables

**Database:** `exchange_portal`  
**Engine:** InnoDB · **Charset:** utf8mb4_unicode_ci  
**Table count:** **8**

---

### 1.1 `universities`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `name` | VARCHAR(255) | NOT NULL |
| `code` | VARCHAR(20) | NOT NULL, UNIQUE |
| `country` | VARCHAR(100) | NOT NULL |
| `city` | VARCHAR(100) | NOT NULL |
| `status` | ENUM('active','inactive') | DEFAULT 'active' |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Referenced by:** `students.university_id`, `coordinators.university_id`, `courses.university_id`, `applications.home_university_id`, `applications.host_university_id`

---

### 1.2 `users`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `role` | ENUM('admin','coordinator','student') | NOT NULL |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE |
| `password_hash` | VARCHAR(255) | NOT NULL |
| `first_name` | VARCHAR(100) | NOT NULL |
| `last_name` | VARCHAR(100) | NOT NULL |
| `status` | ENUM('active','inactive') | DEFAULT 'active' |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Indexes:** `idx_users_role`, `idx_users_status`  
**Child tables:** `students`, `coordinators`, `notifications` (CASCADE on user delete)

---

### 1.3 `students`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `user_id` | INT UNSIGNED | NOT NULL, UNIQUE → `users.id` ON DELETE CASCADE |
| `student_number` | VARCHAR(50) | NOT NULL, UNIQUE |
| `university_id` | INT UNSIGNED | NOT NULL → `universities.id` ON DELETE RESTRICT |
| `phone` | VARCHAR(30) | NULL |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Index:** `idx_students_university`

---

### 1.4 `coordinators`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `user_id` | INT UNSIGNED | NOT NULL, UNIQUE → `users.id` ON DELETE CASCADE |
| `university_id` | INT UNSIGNED | NOT NULL → `universities.id` ON DELETE RESTRICT |
| `department` | VARCHAR(150) | NULL |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

---

### 1.5 `courses`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `university_id` | INT UNSIGNED | NOT NULL → `universities.id` ON DELETE CASCADE |
| `code` | VARCHAR(50) | NOT NULL |
| `title` | VARCHAR(255) | NOT NULL |
| `credits` | DECIMAL(4,1) | DEFAULT 3.0 |
| `semester` | VARCHAR(50) | NOT NULL |
| `capacity` | INT UNSIGNED | DEFAULT 30 |
| `status` | ENUM('open','closed') | DEFAULT 'open' |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Unique:** `(university_id, code)`  
**Index:** `idx_courses_semester`

---

### 1.6 `applications`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `student_id` | INT UNSIGNED | NOT NULL → `students.id` ON DELETE CASCADE |
| `home_university_id` | INT UNSIGNED | NOT NULL → `universities.id` ON DELETE RESTRICT |
| `host_university_id` | INT UNSIGNED | NOT NULL → `universities.id` ON DELETE RESTRICT |
| `semester` | VARCHAR(50) | NOT NULL |
| `status` | ENUM('draft','submitted','under_review','approved','rejected') | DEFAULT 'draft' |
| `personal_statement` | TEXT | NULL |
| `coordinator_notes` | TEXT | NULL |
| `submitted_at` | DATETIME | NULL |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

**Indexes:** `idx_applications_status`, `idx_applications_student`

---

### 1.7 `documents`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `student_id` | INT UNSIGNED | NOT NULL → `students.id` ON DELETE CASCADE |
| `application_id` | INT UNSIGNED | NULL → `applications.id` ON DELETE SET NULL |
| `title` | VARCHAR(255) | NOT NULL |
| `file_name` | VARCHAR(255) | NOT NULL |
| `file_path` | VARCHAR(500) | NOT NULL |
| `file_size` | INT UNSIGNED | DEFAULT 0 |
| `status` | ENUM('pending','approved','rejected') | DEFAULT 'pending' |
| `uploaded_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Index:** `idx_documents_status`

---

### 1.8 `notifications`

| Column | Type | Constraints |
|--------|------|-------------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT |
| `user_id` | INT UNSIGNED | NOT NULL → `users.id` ON DELETE CASCADE |
| `title` | VARCHAR(255) | NOT NULL |
| `message` | TEXT | NOT NULL |
| `is_read` | TINYINT(1) | DEFAULT 0 |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Index:** `idx_notifications_user_read (user_id, is_read)`

---

### 1.9 Seed data inventory (as shipped)

| Table | Rows |
|-------|------|
| universities | 5 |
| users | 8 |
| coordinators | 2 |
| students | 5 |
| courses | 8 |
| applications | 5 |
| documents | 8 |
| notifications | 8 |

**Finding:** Seed `documents.file_path` entries do not guarantee files exist under `uploads/` until a user uploads.

---

## 2. Current Sidebar Items

Defined in `includes/layout.php` (desktop sidebar + mobile pill nav). Header also includes notification bell (not a sidebar item).

### Admin sidebar (9 items)

| # | Label | Route | Nav key |
|---|--------|-------|---------|
| 1 | Dashboard | `/admin/index.php` | `dashboard` |
| 2 | Analytics | `/admin/analytics.php` | `analytics` |
| 3 | Students | `/admin/students.php` | `students` |
| 4 | Coordinators | `/admin/coordinators.php` | `coordinators` |
| 5 | Universities | `/admin/universities.php` | `universities` |
| 6 | Courses | `/admin/courses.php` | `courses` |
| 7 | Applications | `/admin/applications.php` | `applications` |
| 8 | Documents | `/admin/documents.php` | `documents` |
| 9 | Notifications | `/admin/notifications.php` | `notifications` |

**Footer block:** User initials, name, email, **Sign out** link.

---

### Coordinator sidebar (6 items)

| # | Label | Route | Nav key |
|---|--------|-------|---------|
| 1 | Dashboard | `/coordinator/index.php` | `dashboard` |
| 2 | Students | `/coordinator/students.php` | `students` |
| 3 | Applications | `/coordinator/applications.php` | `applications` |
| 4 | Documents | `/coordinator/documents.php` | `documents` |
| 5 | Courses | `/coordinator/courses.php` | `courses` |
| 6 | Notifications | `/coordinator/notifications.php` | `notifications` |

---

### Student sidebar (6 items)

| # | Label | Route | Nav key |
|---|--------|-------|---------|
| 1 | Dashboard | `/student/index.php` | `dashboard` |
| 2 | Applications | `/student/applications.php` | `applications` |
| 3 | Documents | `/student/documents.php` | `documents` |
| 4 | Courses | `/student/courses.php` | `courses` |
| 5 | Profile | `/student/profile.php` | `profile` |
| 6 | Notifications | `/student/notifications.php` | `notifications` |

---

### Guest (no sidebar)

Login and registration use standalone layouts (`login.php`, `register.php`) without the dashboard shell.

**Total sidebar nav links (all roles):** **21** (9 + 6 + 6)

---

## 3. Current Dashboard Screenshots Summary

*No screenshot files are stored in the repository. This section describes what each dashboard would show if captured.*

### 3.1 Login (`/login.php`)

- **Layout:** Split screen — indigo gradient marketing panel (left, desktop) + sign-in form (right).
- **Elements:** Email/password fields, CSRF token, link to register, demo account cheat sheet.
- **Brand:** “GE” logo mark, product title.

### 3.2 Admin dashboard (`/admin/index.php`)

- **Top:** Page title “Dashboard” + notification bell with unread badge.
- **Row 1:** Four gradient **KPI cards** — Total Students, Applications (with approved subtext), Pending Documents, Partner Universities.
- **Row 2 (left):** “Recent Applications” table — student name, host university, status badge (max 5 rows).
- **Row 2 (right):** “Quick Actions” panel — links to add student/university/course, view analytics; mini stats for Courses and Coordinators counts.

### 3.3 Admin analytics (`/admin/analytics.php`)

- **Row 1:** Four KPI cards — Students, Applications, In Review, Documents (pending subtext).
- **Panel A:** “Applications by Status” — labeled rows with **CSS horizontal progress bars** and percentage.
- **Panel B:** “Applications by Host University” — ranked list with numeric counts (not a chart).
- **Panel C:** “Document Verification Overview” — three stat tiles (pending / approved / rejected counts).

### 3.4 Coordinator dashboard (`/coordinator/index.php`)

- **Header card:** Assigned university name and department.
- **KPI row:** Pending Applications, Documents to Verify, Students (scoped counts).
- **Table:** Recent non-draft applications with review links.

### 3.5 Student dashboard (`/student/index.php`)

- **Hero banner:** Gradient welcome card with student name, university, student number.
- **KPI row:** Applications, Approved, Documents, Pending Review.
- **Panels:** Recent applications list (up to 3) + Quick Actions (new application, upload document, browse courses).

### 3.6 Visual system (all dashboards)

- **Sidebar:** Dark slate (`bg-slate-900`), active item indigo (`bg-brand-600`).
- **Content:** Light gray background (`bg-slate-50`), white cards, rounded-xl borders.
- **Typography:** Inter (Google Fonts), Tailwind CDN.
- **Responsive:** Sidebar hidden on mobile; horizontal scroll nav pills in header.

---

## 4. All Implemented CRUD Operations

Legend: ✅ Implemented · 🔶 Partial · ❌ Not implemented

### 4.1 By entity

| Entity | Create | Read | Update | Delete | Notes |
|--------|--------|------|--------|--------|-------|
| **users** | ✅ | ✅ | ✅ | ✅ | Via student/coordinator forms; delete via student cascade |
| **students** | ✅ | ✅ | ✅ | ✅ | Admin CRUD; self-register; coordinator read-only list |
| **coordinators** | ✅ | ✅ | ✅ | ❌ | Admin create/edit only; no delete UI |
| **universities** | ✅ | ✅ | ✅ | ✅ | Admin only; delete may fail if FK in use |
| **courses** | ✅ | ✅ | ✅ | ✅ | Admin (all unis); coordinator (own uni only) |
| **applications** | ✅ | ✅ | ✅ | ❌ | Student create/edit draft + submit; admin/coordinator status update |
| **documents** | ✅ | ✅ | 🔶 | ❌ | Student upload; coordinator status update; no delete |
| **notifications** | ✅ | ✅ | 🔶 | ❌ | System inserts; user mark read; no user delete |

### 4.2 By role and file

| Operation | Role | File(s) |
|-----------|------|---------|
| Create student + user | Admin | `admin/student-form.php` |
| Update student + user | Admin | `admin/student-form.php` |
| Delete student (+ user CASCADE) | Admin | `admin/student-delete.php` |
| List students | Admin | `admin/students.php` |
| Create coordinator + user | Admin | `admin/coordinator-form.php` |
| Update coordinator + user | Admin | `admin/coordinator-form.php` |
| List coordinators | Admin | `admin/coordinators.php` |
| Create university | Admin | `admin/university-form.php` |
| Update university | Admin | `admin/university-form.php` |
| Delete university | Admin | `admin/university-delete.php` |
| List universities | Admin | `admin/universities.php` |
| Create course | Admin, Coordinator | `admin/course-form.php`, `coordinator/course-form.php` |
| Update course | Admin, Coordinator | Same |
| Delete course | Admin | `admin/course-delete.php` |
| List courses | Admin, Coordinator, Student (read) | `admin/courses.php`, `coordinator/courses.php`, `student/courses.php` |
| Create application (draft) | Student | `student/application-form.php` |
| Update application (draft only) | Student | `student/application-form.php` |
| Submit application | Student | `student/application-form.php` (POST action) |
| List applications | All roles | Role-specific list pages |
| Update application status/notes | Admin, Coordinator | `admin/application-view.php`, `coordinator/application-review.php` |
| Upload document | Student | `student/document-upload.php` |
| List documents | All roles | Role-specific pages |
| Update document status | Coordinator | `coordinator/documents.php` |
| Download document | All (authorized) | `download.php` |
| Register student account | Guest | `register.php` |
| Update own profile | Student | `student/profile.php` |
| Mark notification read | All | `*/notifications.php` |
| Mark all notifications read | Admin | `admin/notifications.php` |

### 4.3 CRUD operation count (write operations)

| Type | Count |
|------|-------|
| Distinct CREATE flows | **9** |
| Distinct UPDATE flows | **10** |
| Distinct DELETE flows | **3** |
| **Total write endpoints** | **22** |

---

## 5. Missing Features

### 5.1 Critical (blocks “full product” claims)

| Feature | Impact |
|---------|--------|
| Course enrollment | Students cannot enroll; catalog is display-only |
| Application withdrawal | Students cannot cancel submitted applications |
| Document delete / replace versioning | No lifecycle management for uploads |
| Coordinator delete | Cannot remove coordinator accounts from UI |
| Forgot password / reset | Account recovery not available |
| Email delivery | Notifications are in-app only |

### 5.2 Important (enterprise / production)

| Feature | Impact |
|---------|--------|
| Application status history table | No audit trail of who changed status when |
| Dedicated passport / visa / transcript modules | Compliance tracking is generic “documents” only |
| Pagination on lists | `ITEMS_PER_PAGE` unused; large datasets will break UX |
| Chart.js / exportable reports | Analytics not suitable for executive reporting |
| Remove or protect `install.php` | Security risk if left on server |
| `public/` document root separation | PHP files web-accessible under project root |
| Physical seed files for demo downloads | Seed metadata without files confuses demos |

### 5.3 Nice-to-have (original blueprint)

- System settings (DB-driven config)  
- Lookup tables (countries, document types)  
- Activity / audit logs  
- Fine-grained permissions  
- REST API  
- Email verification on registration  
- Search and advanced filters  
- Bulk actions on admin tables  

---

## 6. Current Page Count

### 6.1 PHP files in project

| Category | Files |
|----------|-------|
| Total `.php` files | **45** |
| Includes / layout (non-routes) | 5 (`init`, `header`, `footer`, `layout`, `components`) |
| Config | 1 (`config.php`) |
| **Routable / entry PHP** | **39** |

### 6.2 User-facing pages (render HTML UI)

| Section | Count |
|---------|-------|
| Public (login, register, install) | 3 |
| Admin (lists, forms, views) | 14 |
| Coordinator | 8 |
| Student | 8 |
| **Browsable UI pages** | **33** |

### 6.3 Processing / utility endpoints (redirect or binary)

| Endpoint | Purpose |
|----------|---------|
| `index.php` | Role-based redirect |
| `logout.php` | Session destroy |
| `download.php` | File stream |
| `admin/student-delete.php` | DELETE + redirect |
| `admin/university-delete.php` | DELETE + redirect |
| `admin/course-delete.php` | DELETE + redirect |
| **Utility endpoints** | **6** |

### 6.4 Total unique URLs

**39** distinct entry points (`BASE_URL` + path).

---

## 7. Current Chart Count

| Visualization type | Count | Location |
|--------------------|-------|----------|
| **Chart.js / Apex / Canvas / SVG charts** | **0** | — |
| **Pie / line / bar chart widgets** | **0** | — |
| **CSS horizontal progress bars** | **1 panel** (dynamic bars per application status) | `admin/analytics.php` |
| **Ranked list with counts** (table-style analytics) | **1 panel** | `admin/analytics.php` (by host university) |
| **Stat tile groups** (numeric KPI blocks) | **1 panel** | `admin/analytics.php` (document statuses) |
| **Gradient KPI stat cards** (`stat_card()`) | **15 instances** across dashboards | Admin index (4), Admin analytics (4), Student index (4), Coordinator index (3) |

**Auditor conclusion:** The project has **zero formal charts**. Analytics rely on **SQL aggregates**, **KPI cards**, **CSS progress bars**, and **lists**. If “chart” means any data visualization, count = **1** progress-bar panel (+ optional count of ~5 bar segments driven by status groups).

---

## 8. Current Analytics Features

### 8.1 `dashboard_stats()` (`includes/init.php`)

Global metrics available to dashboards:

| Metric key | SQL basis |
|------------|-----------|
| `students` | COUNT students |
| `coordinators` | COUNT coordinators |
| `universities` | COUNT active universities |
| `courses` | COUNT courses |
| `applications` | COUNT applications |
| `documents` | COUNT documents |
| `pending_docs` | COUNT documents WHERE status = pending |
| `approved_apps` | COUNT applications WHERE status = approved |
| `submitted_apps` | COUNT applications WHERE status IN (submitted, under_review) |

### 8.2 Admin dashboard analytics

- Recent applications table (5 rows, joined student + host university).
- Embedded counts: courses, coordinators in quick-actions panel.

### 8.3 Admin analytics page (`/admin/analytics.php`)

| Feature | Type | Real-time |
|---------|------|-----------|
| KPI cards (4) | Aggregate counts | Yes |
| Applications by status | GROUP BY + % + progress bar | Yes |
| Applications by host university | LEFT JOIN + COUNT | Yes |
| Document verification overview | GROUP BY document status | Yes |

**Not present:** date ranges, filters, trends over time, export, drill-down, comparison semesters, coordinator performance.

### 8.4 Coordinator dashboard metrics

- Pending applications (scoped by university).
- Pending documents (scoped).
- Student count at assigned university.
- Recent applications table.

### 8.5 Student dashboard metrics

- Personal application count, approved count, document count, pending document count.
- Recent applications (3 max).

### 8.6 Analytics feature count

| Category | Count |
|----------|-------|
| Distinct SQL aggregate queries (dashboard_stats) | 9 |
| Dedicated analytics page panels | 3 |
| Role-specific dashboard metric widgets | 11+ |
| **Total distinct analytics widgets** | **~23** |

---

## 9. Current Upload Features

### 9.1 Configuration (`config.php`)

| Setting | Value |
|---------|-------|
| Storage directory | `uploads/` (project root) |
| Max size | 5 MB |
| Allowed extensions | pdf, doc, docx, jpg, jpeg, png |
| Web access to uploads | Blocked for `.php` via `uploads/.htaccess` |

### 9.2 Upload handler (`handle_upload()` in `includes/init.php`)

| Check | Implemented |
|-------|-------------|
| `UPLOAD_ERR_OK` | ✅ |
| File size limit | ✅ |
| Extension whitelist | ✅ |
| Unique stored filename (`uniqid` + ext) | ✅ |
| `move_uploaded_file()` | ✅ |
| MIME validation (magic bytes) | ❌ |
| Virus scanning | ❌ |
| Per-user quota | ❌ |

### 9.3 Upload UI

| Feature | Location |
|---------|----------|
| Upload form | `student/document-upload.php` |
| Fields: title, optional application link, file input | ✅ |
| Drag-style dashed border UI | ✅ (visual only) |
| CSRF protection | ✅ |
| Post-upload notification | ✅ |

### 9.4 Download / access

| Feature | Location |
|---------|----------|
| Secure download gate | `download.php` |
| Admin: all documents | ✅ |
| Student: own documents | ✅ |
| Coordinator: broad access (all docs in review flow) | ✅ (permissive) |
| Inline vs attachment headers | Inline disposition |
| Missing file handling | 404 message if seed file absent |

### 9.5 Document workflow

| Step | Supported |
|------|-----------|
| Student uploads | ✅ |
| Link to application | ✅ Optional |
| Coordinator approve | ✅ |
| Coordinator reject | ✅ |
| Admin view metadata | ✅ |
| Delete document | ❌ |
| Re-upload / version | ❌ |

### 9.6 Upload feature count summary

| Capability | Count |
|------------|-------|
| Upload entry points | 1 |
| Allowed file types | 6 extensions |
| Download entry point | 1 |
| Status workflow states | 3 |

---

## 10. Current Admin Capabilities

### 10.1 Access control

- Role gate: `require_role(['admin'])` on all `/admin/*` pages (except none — all protected).
- **Global scope:** No university filtering; admin sees all records.

### 10.2 Capability matrix

| Capability | Available |
|------------|-----------|
| View system-wide dashboard | ✅ |
| View analytics page | ✅ |
| Create / edit / delete students | ✅ |
| Create / edit coordinators | ✅ / ❌ delete |
| Create / edit / delete universities | ✅ |
| Create / edit / delete courses (any university) | ✅ |
| View all applications | ✅ |
| Change application status & notes | ✅ |
| View all documents | ✅ |
| Download any document | ✅ |
| Upload documents as admin | ❌ |
| Verify documents (approve/reject) | ❌ (coordinator function) |
| Manage own notifications | ✅ |
| Broadcast notifications to all users | ❌ |
| Manage system settings | ❌ |
| View activity logs | ❌ |
| Export reports (CSV/PDF) | ❌ |
| Impersonate users | ❌ |
| Bulk operations | ❌ |

### 10.3 Admin pages (17 files under `/admin/`)

1. `index.php` — Dashboard  
2. `analytics.php` — Analytics  
3. `students.php` — List  
4. `student-form.php` — Create/Edit  
5. `student-delete.php` — Delete  
6. `coordinators.php` — List  
7. `coordinator-form.php` — Create/Edit  
8. `universities.php` — List  
9. `university-form.php` — Create/Edit  
10. `university-delete.php` — Delete  
11. `courses.php` — List  
12. `course-form.php` — Create/Edit  
13. `course-delete.php` — Delete  
14. `applications.php` — List  
15. `application-view.php` — Review/Update  
16. `documents.php` — List/View  
17. `notifications.php` — Inbox  

### 10.4 Admin capability count

| Metric | Value |
|--------|-------|
| Admin-only pages | 17 |
| Full CRUD entities | 3 (students, universities, courses) |
| Partial CRUD entities | 2 (coordinators, applications) |
| Read-only admin views | 2 (documents list, notifications) |
| **Distinct admin capabilities** | **~28** |

---

## Audit Findings & Recommendations

### High priority

1. **Delete `install.php`** after database setup on any shared/staging server.  
2. **Import or upload real files** before demoing document download.  
3. **Add pagination** before datasets grow beyond seed size.  

### Medium priority

4. Implement **course enrollment** to complete the student journey.  
5. Add **Chart.js** (or similar) if “analytics dashboard” is a stakeholder requirement.  
6. Restrict **coordinator download access** to scoped documents only.  
7. Add **MIME type validation** on upload.  

### Low priority

8. Coordinator delete, application delete, document versioning.  
9. Move web root to `public/` for deployment best practice.  

---

## Appendix: Audit Evidence

| Item | Source file(s) |
|------|----------------|
| Schema | `database/database.sql` |
| Navigation | `includes/layout.php` |
| Stats helper | `includes/init.php` → `dashboard_stats()` |
| Upload helper | `includes/init.php` → `handle_upload()` |
| Analytics UI | `admin/analytics.php` |
| KPI component | `includes/components.php` → `stat_card()` |

---

*This audit reflects the repository state at MVP 1.0. For feature roadmap, see [PROJECT_STATUS.md](PROJECT_STATUS.md).*
