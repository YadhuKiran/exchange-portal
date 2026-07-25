# Enterprise Transformation — Implementation Plan

**Project:** Global Student Mobility & Exchange Portal  
**Baseline:** MVP (8 tables · 33 UI pages · file-based PHP routing)  
**Target:** Enterprise-grade commercial university platform  
**Principle:** **Extend, do not rebuild** — all MVP routes, CRUD flows, and tables remain functional.

**Document purpose:** Pre-code blueprint for Phases 1–8. No implementation until approved.

---

## Table of Contents

1. [Architecture approach](#1-architecture-approach)
2. [Database plan (new tables)](#2-database-plan-new-tables)
3. [Migration strategy](#3-migration-strategy)
4. [Shared infrastructure (new files)](#4-shared-infrastructure-new-files)
5. [Phase 1 — Core modules](#5-phase-1--core-modules)
6. [Phase 2 — Admin dashboard charts](#6-phase-2--admin-dashboard-charts)
7. [Phase 3 — Enterprise sidebar](#7-phase-3--enterprise-sidebar)
8. [Phase 4 — Reports center & exports](#8-phase-4--reports-center--exports)
9. [Phase 5 — Activity logs](#9-phase-5--activity-logs)
10. [Phase 6 — Course enrollment](#10-phase-6--course-enrollment)
11. [Phase 7 — Passport & visa](#11-phase-7--passport--visa)
12. [Phase 8 — UI transformation](#12-phase-8--ui-transformation)
13. [Complete route map (after upgrade)](#13-complete-route-map-after-upgrade)
14. [Navigation map (enterprise)](#14-navigation-map-enterprise)
15. [Dashboard widget inventory](#15-dashboard-widget-inventory)
16. [Chart inventory (Chart.js)](#16-chart-inventory-chartjs)
17. [Files to modify (existing)](#17-files-to-modify-existing)
18. [Files to add (new)](#18-files-to-add-new)
19. [Implementation order & estimates](#19-implementation-order--estimates)
20. [Risks & constraints](#20-risks--constraints)

---

## 1. Architecture approach

### 1.1 What stays unchanged

| Asset | Action |
|-------|--------|
| 8 MVP tables | **Retain** — no breaking column removals |
| `login.php`, `register.php`, `logout.php` | **Retain** — add logging hooks only |
| Existing admin/coordinator/student CRUD pages | **Retain** — extend with calls to `log_activity()` |
| `documents` table & upload flow | **Retain** — passport/visa/transcript may **reference** `documents.id` optionally |
| `config.php` BASE_URL pattern | **Retain** |
| PDO + session auth model | **Retain** |

### 1.2 Extension pattern

```
includes/
  enterprise/
    ActivityLog.php      # log_activity(), fetch feed
    Settings.php         # get_setting(), set_setting()
    ChartData.php        # SQL → JSON for Chart.js
    ExportCsv.php        # streaming CSV helper
  widgets/               # reusable dashboard partials
  nav-config.php         # grouped navigation definitions
database/
  migrations/
    001_enterprise.sql   # additive DDL + seed settings
```

- **Single migration file** adds tables; MVP `database.sql` updated at end for fresh installs only.
- **No framework** — continue file-based routes; optional thin `admin/api/*.php` JSON endpoints for charts.
- **Coordinator scoping** — reuse existing `university_id` filter pattern from coordinator pages.

---

## 2. Database plan (new tables)

**After upgrade: 8 MVP tables + 7 new tables = 15 tables**  
(Optional lookup: `visa_types` — deferred; use `VARCHAR` on `visas.visa_type` for speed.)

### 2.1 `passports`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `student_id` | INT UNSIGNED FK → students | UNIQUE (one active record per student) |
| `passport_number` | VARCHAR(50) | |
| `issuing_country` | VARCHAR(100) | |
| `issue_date` | DATE | |
| `expiry_date` | DATE | |
| `document_id` | INT UNSIGNED NULL FK → documents | Scanned copy |
| `status` | ENUM | `pending`, `verified`, `rejected`, `expired` |
| `verified_by` | INT UNSIGNED NULL FK → users | Coordinator/admin |
| `verified_at` | DATETIME NULL | |
| `notes` | TEXT NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

### 2.2 `visas`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `student_id` | INT UNSIGNED FK → students | |
| `application_id` | INT UNSIGNED NULL FK → applications | |
| `visa_type` | VARCHAR(100) | e.g. Student, Exchange |
| `visa_number` | VARCHAR(50) NULL | |
| `issuing_country` | VARCHAR(100) | |
| `issue_date` | DATE NULL | |
| `expiry_date` | DATE | |
| `document_id` | INT UNSIGNED NULL FK → documents | |
| `status` | ENUM | `pending`, `verified`, `rejected`, `expired` |
| `verified_by` | INT UNSIGNED NULL | |
| `verified_at` | DATETIME NULL | |
| `notes` | TEXT NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

### 2.3 `transcripts`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `student_id` | INT UNSIGNED FK → students | |
| `institution_name` | VARCHAR(255) | |
| `degree_program` | VARCHAR(255) NULL | |
| `gpa` | DECIMAL(4,2) NULL | |
| `grading_scale` | VARCHAR(50) NULL | |
| `issue_date` | DATE NULL | |
| `document_id` | INT UNSIGNED NULL FK → documents | |
| `is_official` | TINYINT(1) DEFAULT 0 | |
| `status` | ENUM | `pending`, `verified`, `rejected` |
| `verified_by` | INT UNSIGNED NULL | |
| `verified_at` | DATETIME NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

### 2.4 `enrollments`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `student_id` | INT UNSIGNED FK → students | |
| `course_id` | INT UNSIGNED FK → courses | |
| `application_id` | INT UNSIGNED NULL FK → applications | Required if setting enforces approved app |
| `status` | ENUM | `pending`, `approved`, `dropped`, `completed`, `rejected` |
| `approved_by` | INT UNSIGNED NULL FK → users | Coordinator |
| `enrolled_at` | TIMESTAMP | |
| `dropped_at` | DATETIME NULL | |
| UNIQUE | `(student_id, course_id)` | Prevent duplicate enrollment |

### 2.5 `application_status_history`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `application_id` | INT UNSIGNED FK → applications | |
| `from_status` | VARCHAR(50) NULL | |
| `to_status` | VARCHAR(50) NOT NULL | |
| `changed_by_user_id` | INT UNSIGNED FK → users | |
| `comment` | TEXT NULL | |
| `created_at` | TIMESTAMP | |

**Trigger point:** Every status change in `admin/application-view.php`, `coordinator/application-review.php`, `student/application-form.php` (submit).

### 2.6 `activity_logs`

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | High volume |
| `user_id` | INT UNSIGNED NULL FK → users | NULL = system |
| `action` | VARCHAR(100) NOT NULL | e.g. `login`, `application.status_change` |
| `entity_type` | VARCHAR(50) NULL | `application`, `document`, `course`, … |
| `entity_id` | INT UNSIGNED NULL | |
| `description` | VARCHAR(500) NOT NULL | Human-readable |
| `ip_address` | VARCHAR(45) NULL | |
| `user_agent` | VARCHAR(255) NULL | |
| `metadata` | JSON NULL | Extra context |
| `created_at` | TIMESTAMP | INDEX `(created_at)`, `(user_id, created_at)` |

### 2.7 `system_settings`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT UNSIGNED PK | |
| `setting_key` | VARCHAR(100) UNIQUE | |
| `setting_value` | TEXT | |
| `setting_group` | VARCHAR(50) | `general`, `uploads`, `enrollment`, `branding` |
| `data_type` | ENUM | `string`, `integer`, `boolean`, `json` |
| `label` | VARCHAR(255) | Admin UI label |
| `updated_by` | INT UNSIGNED NULL | |
| `updated_at` | TIMESTAMP | |

**Seed keys:** `site_name`, `max_upload_mb`, `enrollment_requires_approved_application`, `academic_year`, `application_prefix`, `session_timeout_minutes`, `chart_months_range` (default 12).

### 2.8 Entity relationship (additive)

```
students ──┬── passports (1:1)
           ├── visas (1:N)
           ├── transcripts (1:N)
           ├── enrollments (N)── courses
           └── applications ──┬── application_status_history
                              └── enrollments (optional FK)

documents ── optional FK from passports, visas, transcripts

users ── activity_logs, application_status_history.changed_by
system_settings ── standalone
```

---

## 3. Migration strategy

| Step | File | Action |
|------|------|--------|
| 1 | `database/migrations/001_enterprise.sql` | `CREATE TABLE` × 7, indexes, FKs |
| 2 | Same file | `INSERT` seed `system_settings` (~10 rows) |
| 3 | Same file | Optional seed: 2–3 sample enrollments, history rows for existing applications |
| 4 | `install.php` | Run migration after base schema OR document two-step install |
| 5 | `database/database.sql` | Append new tables for **new** installs only (post-implementation) |
| 6 | README | Document: import MVP → run `001_enterprise.sql` |

**No DROP** of MVP tables. Existing data preserved.

---

## 4. Shared infrastructure (new files)

| File | Responsibility |
|------|----------------|
| `includes/enterprise/ActivityLog.php` | `log_activity($action, $description, $entityType, $entityId, $metadata)` |
| `includes/enterprise/Settings.php` | `setting($key, $default)`, `set_setting($key, $value)` with cache |
| `includes/enterprise/ApplicationHistory.php` | `record_status_change($appId, $from, $to, $userId, $comment)` |
| `includes/enterprise/ChartData.php` | Methods returning arrays for each chart |
| `includes/enterprise/ExportCsv.php` | `stream_csv($filename, $headers, $rows)` |
| `includes/nav-config.php` | Grouped nav arrays per role |
| `includes/widgets/activity_feed.php` | Last N activity_logs |
| `includes/widgets/verification_queue.php` | Pending docs + passports + visas |
| `includes/widgets/application_timeline.php` | application_status_history for one app |
| `includes/widgets/chart_card.php` | Wrapper: title + canvas + height |
| `includes/widgets/kpi_strip.php` | Enhanced KPI row (enterprise styling) |
| `includes/widgets/recent_actions.php` | Role-filtered activity subset |
| `admin/api/chart-data.php` | JSON endpoint (admin only) — optional if inline PHP preferred |

**Modify `includes/init.php`:**  
`require_once` enterprise classes; helper wrappers `log_activity()`, `setting()`.

**Modify `includes/header.php`:**  
Load Chart.js CDN on dashboard pages via `$loadCharts = true` flag.

**Modify `includes/components.php`:**  
Keep `stat_card()`; add `enterprise_page_header()`, `queue_badge()`.

---

## 5. Phase 1 — Core modules

Maps to user Phases 1, 5, 6, 7 partially, plus settings.

### 5.1 Module checklist

| # | Module | New routes | Primary files |
|---|--------|------------|---------------|
| 1 | Passport management | 6 student + 4 coord + 4 admin | See §11 |
| 2 | Visa management | 6 + 4 + 4 | See §11 |
| 3 | Transcript management | 6 + 4 + 4 | See §13 |
| 4 | Course enrollment | 5 + 4 + 5 | See §10 |
| 5 | Application status history | 0 new pages (widget + auto-write) | Widget + hooks in 3 review files |
| 6 | Activity logs | 2 admin + 1 coord | `admin/activity-logs.php`, `coordinator/activity-logs.php` (read-only scoped) |
| 7 | Reports center | 3 admin + 3 coord + 3 export | See §8 |
| 8 | System settings | 1 admin | `admin/settings.php` |

### 5.2 Hooks into existing files (no rebuild)

| Existing file | Addition |
|---------------|----------|
| `login.php` | `log_activity('auth.login', ...)` on success |
| `logout.php` | `log_activity('auth.logout', ...)` |
| `admin/application-view.php` | `record_status_change()` + log |
| `coordinator/application-review.php` | Same |
| `student/application-form.php` | History on submit |
| `coordinator/documents.php` | Log approve/reject |
| `admin/course-form.php`, `course-delete.php` | Log create/update/delete |
| `admin/university-form.php`, `university-delete.php` | Log update/delete |
| `coordinator/course-form.php` | Log create/update |
| `admin/student-form.php`, `student-delete.php` | Log create/delete |

---

## 6. Phase 2 — Admin dashboard charts

**Page:** Replace/enhance `admin/index.php` (keep `admin/analytics.php` as deep-dive or merge).

### 6.1 Chart.js integration

| Item | Detail |
|------|--------|
| Library | Chart.js 4.x CDN in `includes/header.php` when `$loadCharts = true` |
| Data source | `includes/enterprise/ChartData.php` called inline in `admin/index.php` OR `admin/api/chart-data.php` |
| Colors | Enterprise palette: indigo, emerald, amber, rose, slate (match Tailwind brand) |

### 6.2 Five required charts

| # | Chart title | Chart.js type | Data query (summary) | Canvas ID |
|---|-------------|---------------|----------------------|-----------|
| 1 | Application trend | **Line** (multi-series optional) | Applications `GROUP BY DATE(created_at)` last N months | `chartAppTrend` |
| 2 | Student distribution by university | **Doughnut** or **Bar** | `students JOIN universities GROUP BY university_id` | `chartStudentsByUni` |
| 3 | Application approval rate | **Doughnut** | Count approved vs (rejected + pending + other) | `chartApprovalRate` |
| 4 | Document verification | **Bar** stacked | `documents GROUP BY status` | `chartDocVerification` |
| 5 | Monthly application growth | **Bar** | `COUNT` per month `YEAR-MONTH` for 12 months | `chartMonthlyGrowth` |

### 6.3 Layout on admin dashboard (Phase 2 + 8)

```
┌─────────────────────────────────────────────────────────────┐
│ KPI strip (6 metrics — enhanced)                            │
├──────────────────────────┬──────────────────────────────────┤
│ Application Trend (line)   │ Approval Rate (doughnut)         │
├──────────────────────────┼──────────────────────────────────┤
│ Students by Uni (bar)    │ Monthly Growth (bar)             │
├──────────────────────────┴──────────────────────────────────┤
│ Document Verification (bar) │ Verification Queue (widget)   │
├─────────────────────────────┴───────────────────────────────┤
│ Activity Feed │ Recent Applications table │ Status summary  │
└─────────────────────────────────────────────────────────────┘
```

**MVP KPI cards:** Wrapped in `kpi_strip` widget — not removed, repositioned above charts.

---

## 7. Phase 3 — Enterprise sidebar

**Replace** flat `$navItems` array in `includes/layout.php` with `includes/nav-config.php`.

### 7.1 Visual design (Workday / M365 Admin style)

| Element | Spec |
|---------|------|
| Sidebar width | `w-64` → `w-72` on xl |
| Groups | Uppercase section labels, `text-[10px] tracking-wider text-slate-500` |
| Items | Icon (Heroicon outline 20px) + label + optional badge count |
| Active state | Left border accent `border-l-2 border-brand-500` + `bg-slate-800/80` |
| Collapse | Optional chevron per group (JS toggle, localStorage) |
| Footer | User card unchanged |

### 7.2 Admin grouped navigation

| Group | Items |
|-------|-------|
| **Overview** | Dashboard, Analytics |
| **Mobility** | Applications, Enrollments |
| **Compliance** | Documents, Passports, Visas, Transcripts |
| **Directory** | Students, Coordinators, Universities |
| **Academics** | Courses |
| **Insights** | Reports Center, Activity Logs |
| **System** | Settings, Notifications |

### 7.3 Coordinator grouped navigation

| Group | Items |
|-------|-------|
| **Overview** | Dashboard |
| **Mobility** | Applications, Enrollments |
| **Compliance** | Documents, Passports, Visas, Transcripts |
| **Directory** | Students |
| **Academics** | Courses |
| **Insights** | Reports Center, Activity Logs (scoped) |
| **Account** | Notifications |

### 7.4 Student grouped navigation

| Group | Items |
|-------|-------|
| **Overview** | Dashboard |
| **My Mobility** | Applications, Enrollments |
| **Compliance** | Documents, Passport, Visas, Transcripts |
| **Academics** | Course Catalog |
| **Account** | Profile, Notifications |

### 7.5 Nav config structure (PHP)

```php
// includes/nav-config.php
return [
  'admin' => [
    ['group' => 'Overview', 'items' => [
      ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/admin/index.php', 'icon' => 'home', 'badge' => null],
      ...
    ]],
    ...
  ],
];
```

**Modify:** `includes/layout.php` — loop groups; mobile nav shows group separators.

---

## 8. Phase 4 — Reports center & exports

### 8.1 UI pages

| Route | Role | Purpose |
|-------|------|---------|
| `/admin/reports/index.php` | Admin | Hub: cards + export buttons + filters |
| `/admin/reports/export-applications.php` | Admin | CSV stream |
| `/admin/reports/export-students.php` | Admin | CSV stream |
| `/admin/reports/export-documents.php` | Admin | CSV stream |
| `/coordinator/reports/index.php` | Coordinator | Same UI, scoped data |
| `/coordinator/reports/export-applications.php` | Coordinator | Scoped CSV |
| `/coordinator/reports/export-students.php` | Coordinator | Scoped CSV |
| `/coordinator/reports/export-documents.php` | Coordinator | Scoped CSV |

### 8.2 Export columns

**Applications CSV:** id, student name, email, home uni, host uni, semester, status, submitted_at, updated_at, coordinator_notes  

**Students CSV:** student_number, first_name, last_name, email, university, phone, status, created_at  

**Documents CSV:** id, student, title, file_name, status, application_id, uploaded_at, file_size  

### 8.3 Filters (query params)

`?status=&from=&to=&university_id=` — admin only for university_id; coordinator auto-scoped.

**Security:** `require_role`, CSRF not needed for GET export; rate-limit via session check only.

---

## 9. Phase 5 — Activity logs

Covered in §2.6 and §5.2.

### 9.1 Actions to log (explicit)

| Action code | Trigger location |
|-------------|------------------|
| `auth.login` | `login.php` |
| `auth.logout` | `logout.php` |
| `application.status_change` | Admin/coordinator application review + student submit |
| `document.approved` / `document.rejected` | `coordinator/documents.php` |
| `course.created` / `course.updated` | `admin/course-form.php`, `coordinator/course-form.php` |
| `course.deleted` | `admin/course-delete.php` |
| `university.updated` | `admin/university-form.php` (create + update) |
| `university.deleted` | `admin/university-delete.php` |
| `passport.verified` / `visa.verified` | New verify actions Phase 7 |
| `enrollment.approved` / `enrollment.dropped` | Phase 6 |

### 9.2 UI

| Page | Features |
|------|----------|
| `admin/activity-logs.php` | Paginated table, filter by action/user/date, expandable metadata JSON |
| `coordinator/activity-logs.php` | Same, filtered to users/applications/docs tied to coordinator university |
| Dashboard widget | Last 10 entries on admin/coordinator/student dashboards |

---

## 10. Phase 6 — Course enrollment

### 10.1 Business rules

| Rule | Source |
|------|--------|
| Student may enroll only in `courses.status = 'open'` | Code |
| Host university ≠ home university | Same as catalog |
| Optional: require `applications.status = 'approved'` for host uni | `system_settings.enrollment_requires_approved_application` |
| Capacity check | `COUNT(enrollments WHERE course_id AND status IN ('pending','approved')) < courses.capacity` |
| Coordinator approves `pending` → `approved` | Coordinator UI |
| Student drop | `approved` → `dropped` |

### 10.2 Routes

| Route | Role | CRUD |
|-------|------|------|
| `/student/courses.php` | Student | **Modify** — add Enroll button + my enrollments section |
| `/student/enrollments.php` | Student | List + drop |
| `/student/enroll.php` | Student | POST processor |
| `/student/enrollment-drop.php` | Student | POST processor |
| `/coordinator/enrollments.php` | Coordinator | List pending + approve/reject |
| `/admin/enrollments.php` | Admin | Full list, filter, status override |
| `/admin/enrollment-view.php` | Admin | Detail (optional) |

### 10.3 Existing file changes

- **`student/courses.php`** — Add enrollment status badge per course; link to enroll POST.
- **Do not remove** catalog browse behavior.

---

## 11. Phase 7 — Passport & visa

### 11.1 Passport routes

| Route | Role |
|-------|------|
| `/student/passport.php` | View/edit own passport + upload link |
| `/student/passport-save.php` | POST save |
| `/coordinator/passports.php` | Queue list |
| `/coordinator/passport-verify.php` | POST verify/reject |
| `/admin/passports.php` | Audit all |
| `/admin/passport-view.php` | Detail |

### 11.2 Visa routes

| Route | Role |
|-------|------|
| `/student/visas.php` | List |
| `/student/visa-form.php` | Create/edit |
| `/student/visa-delete.php` | Delete own (if pending) |
| `/coordinator/visas.php` | Verify queue |
| `/coordinator/visa-verify.php` | POST |
| `/admin/visas.php` | Audit all |

### 11.3 Transcript routes

| Route | Role |
|-------|------|
| `/student/transcripts.php` | List + upload metadata |
| `/student/transcript-form.php` | Add/edit |
| `/coordinator/transcripts.php` | Verify queue |
| `/coordinator/transcript-verify.php` | POST |
| `/admin/transcripts.php` | Audit all |

### 11.4 Document integration

- Reuse `handle_upload()` → insert `documents` row → set `passports.document_id` / `visas.document_id` / `transcripts.document_id`.
- Coordinator verification updates **both** compliance record status and optional linked `documents.status`.

---

## 12. Phase 8 — UI transformation

### 12.1 Scope per dashboard

| Dashboard | Transformations |
|-----------|-----------------|
| **Admin** | Chart grid (Phase 2), verification queue, activity feed, recent applications, status summary pills, “institution scale” copy |
| **Coordinator** | Mini charts (2): pending apps trend, doc verification; queues for apps/docs/passports/visas; activity feed scoped |
| **Student** | Application timeline widget, enrollment summary, compliance checklist (% complete), upcoming expiry alerts (passport/visa) |

### 12.2 Enterprise visual tokens

| Token | Value |
|-------|-------|
| Page background | `bg-slate-100` |
| Card | `bg-white shadow-sm ring-1 ring-slate-200/60 rounded-xl` |
| Section title | `text-xs font-semibold uppercase tracking-wide text-slate-500` |
| Density | Tighter tables `text-sm`, more whitespace on dashboards |

### 12.3 Widgets (reusable)

| Widget | Used on |
|--------|---------|
| `activity_feed.php` | Admin, coordinator, student dashboards |
| `verification_queue.php` | Admin, coordinator |
| `application_timeline.php` | Application review pages + student dashboard |
| `compliance_checklist.php` | Student dashboard |
| `status_summary.php` | Admin dashboard (counts by status) |
| `recent_applications_table.php` | Extract from current admin index |

### 12.4 “Hundreds of students” credibility

- Migration seed: add **~20–30 synthetic students** via `002_demo_scale.sql` (optional, flagged in settings `demo_mode`) OR document as post-launch seed.
- Dashboard queries use real `COUNT(*)` — scale reads authentically once seed expanded.

---

## 13. Complete route map (after upgrade)

### 13.1 Route count summary

| Category | MVP | New | Total |
|----------|-----|-----|-------|
| Public | 6 | 0 | 6 |
| Admin | 17 | **+22** | **39** |
| Coordinator | 8 | **+16** | **24** |
| Student | 8 | **+12** | **20** |
| API | 0 | **+1** | 1 |
| **Browsable UI** | 33 | **+~50** | **~83** |
| **Export processors** | 0 | 6 | 6 |

### 13.2 New routes only (alphabetical)

**Admin:**  
`activity-logs.php`, `enrollments.php`, `passports.php`, `passport-view.php`, `reports/index.php`, `reports/export-*.php` (×3), `settings.php`, `transcripts.php`, `visas.php`, `api/chart-data.php` (optional)

**Coordinator:**  
`activity-logs.php`, `enrollments.php`, `passports.php`, `passport-verify.php`, `reports/*` (×4), `transcripts.php`, `transcript-verify.php`, `visas.php`, `visa-verify.php`

**Student:**  
`enrollments.php`, `enroll.php`, `enrollment-drop.php`, `passport.php`, `passport-save.php`, `transcripts.php`, `transcript-form.php`, `visas.php`, `visa-form.php`, `visa-delete.php`

---

## 14. Navigation map (enterprise)

### 14.1 Sidebar item counts

| Role | Groups | Total links |
|------|--------|-------------|
| Admin | 7 | **17** |
| Coordinator | 6 | **13** |
| Student | 5 | **11** |

*(Up from 9 / 6 / 6 flat items.)*

### 14.2 Badge counts on nav (live)

| Item | Badge query |
|------|-------------|
| Documents | `COUNT(documents WHERE status=pending)` scoped |
| Passports | pending passports |
| Visas | pending visas |
| Applications | submitted + under_review |
| Enrollments | pending enrollments |

---

## 15. Dashboard widget inventory

| Widget | Admin | Coordinator | Student |
|--------|-------|-------------|---------|
| KPI strip (6 metrics) | ✅ | ✅ (4) | ✅ (4) |
| Application trend chart | ✅ | mini | — |
| Students by university chart | ✅ | — | — |
| Approval rate chart | ✅ | — | — |
| Document verification chart | ✅ | ✅ mini | — |
| Monthly growth chart | ✅ | — | — |
| Verification queue | ✅ | ✅ | — |
| Activity feed | ✅ | ✅ | ✅ (own) |
| Recent applications table | ✅ | ✅ | ✅ |
| Status summary bar | ✅ | ✅ | — |
| Application timeline | review pages | review | ✅ dashboard |
| Compliance checklist | — | — | ✅ |
| Enrollment summary | ✅ | ✅ | ✅ |
| Expiry alerts (passport/visa) | — | — | ✅ |

**Total distinct widget types:** **14**

---

## 16. Chart inventory (Chart.js)

| # | Chart | Type | Page(s) | Phase |
|---|-------|------|---------|-------|
| 1 | Application trend | Line | `admin/index.php` | 2 |
| 2 | Student distribution by university | Bar or Doughnut | `admin/index.php` | 2 |
| 3 | Application approval rate | Doughnut | `admin/index.php` | 2 |
| 4 | Document verification | Bar | `admin/index.php` | 2 |
| 5 | Monthly application growth | Bar | `admin/index.php` | 2 |
| 6 | Coordinator pending trend (optional) | Line | `coordinator/index.php` | 8 |
| 7 | Coordinator doc status (optional) | Doughnut | `coordinator/index.php` | 8 |

**Total Chart.js instances (minimum):** **5** admin + **0–2** coordinator = **5–7**

**Library load:** Conditional in `header.php` via `$loadCharts = true`.

---

## 17. Files to modify (existing)

| File | Changes |
|------|---------|
| `includes/init.php` | Require enterprise classes; wrappers; optional `last_login` update on login |
| `includes/header.php` | Chart.js CDN; enterprise meta |
| `includes/layout.php` | Grouped nav from `nav-config.php`; badge support |
| `includes/components.php` | New enterprise helpers |
| `includes/footer.php` | Chart init script slot |
| `config.php` | `CHART_MONTHS_RANGE` default |
| `login.php` | Activity log |
| `logout.php` | Activity log |
| `admin/index.php` | Enterprise dashboard layout + charts |
| `admin/analytics.php` | Align with Chart.js or redirect to dashboard |
| `admin/application-view.php` | Status history + logging |
| `admin/course-form.php` | Logging |
| `admin/course-delete.php` | Logging |
| `admin/university-form.php` | Logging |
| `admin/university-delete.php` | Logging |
| `admin/student-form.php` | Logging (optional) |
| `coordinator/index.php` | Enterprise widgets + mini charts |
| `coordinator/application-review.php` | Status history + logging |
| `coordinator/documents.php` | Logging |
| `coordinator/course-form.php` | Logging |
| `student/index.php` | Timeline, compliance, enrollment summary |
| `student/courses.php` | Enroll actions |
| `student/application-form.php` | History on submit |
| `download.php` | Allow passport/visa/transcript linked docs |
| `install.php` | Run enterprise migration |
| `database/database.sql` | Append new tables (post-build) |
| `README.md` | Enterprise setup steps |
| `PROJECT_STATUS.md` | Update after delivery |

**Estimated modified files:** **~24**

---

## 18. Files to add (new)

### 18.1 Database

- `database/migrations/001_enterprise.sql`
- `database/migrations/002_demo_scale.sql` (optional)

### 18.2 Includes

- `includes/nav-config.php`
- `includes/enterprise/ActivityLog.php`
- `includes/enterprise/Settings.php`
- `includes/enterprise/ApplicationHistory.php`
- `includes/enterprise/ChartData.php`
- `includes/enterprise/ExportCsv.php`
- `includes/widgets/activity_feed.php`
- `includes/widgets/verification_queue.php`
- `includes/widgets/application_timeline.php`
- `includes/widgets/chart_card.php`
- `includes/widgets/kpi_strip.php`
- `includes/widgets/status_summary.php`
- `includes/widgets/compliance_checklist.php`
- `includes/widgets/recent_applications_table.php`
- `includes/widgets/enrollment_summary.php`
- `includes/widgets/expiry_alerts.php`

### 18.3 Admin pages (new)

- `admin/activity-logs.php`
- `admin/settings.php`
- `admin/enrollments.php`
- `admin/passports.php`
- `admin/passport-view.php`
- `admin/visas.php`
- `admin/transcripts.php`
- `admin/reports/index.php`
- `admin/reports/export-applications.php`
- `admin/reports/export-students.php`
- `admin/reports/export-documents.php`
- `admin/api/chart-data.php` (optional)

### 18.4 Coordinator pages (new)

- `coordinator/activity-logs.php`
- `coordinator/enrollments.php`
- `coordinator/passports.php`
- `coordinator/passport-verify.php`
- `coordinator/visas.php`
- `coordinator/visa-verify.php`
- `coordinator/transcripts.php`
- `coordinator/transcript-verify.php`
- `coordinator/reports/index.php`
- `coordinator/reports/export-applications.php`
- `coordinator/reports/export-students.php`
- `coordinator/reports/export-documents.php`

### 18.5 Student pages (new)

- `student/enrollments.php`
- `student/enroll.php`
- `student/enrollment-drop.php`
- `student/passport.php`
- `student/passport-save.php`
- `student/visas.php`
- `student/visa-form.php`
- `student/visa-delete.php`
- `student/transcripts.php`
- `student/transcript-form.php`

**Estimated new files:** **~45**

---

## 19. Implementation order & estimates

Execute in this order to avoid broken dependencies:

| Step | Phase | Deliverable | Est. effort |
|------|-------|-------------|-------------|
| 1 | Migration | `001_enterprise.sql` + run on DB | 2 h |
| 2 | Infrastructure | ActivityLog, Settings, ApplicationHistory | 3 h |
| 3 | Phase 5 | Wire logs into existing pages | 2 h |
| 4 | Phase 1 (history) | Status history hooks + timeline widget | 2 h |
| 5 | Phase 8 (partial) | nav-config + layout sidebar | 3 h |
| 6 | Phase 6 | Enrollment module | 4 h |
| 7 | Phase 7 | Passport, visa, transcript | 6 h |
| 8 | Phase 4 | Reports + CSV exports | 3 h |
| 9 | Phase 1 | System settings admin page | 2 h |
| 10 | Phase 2 | ChartData + 5 Chart.js on admin dashboard | 4 h |
| 11 | Phase 8 | All dashboard widgets + coordinator/student upgrades | 5 h |
| 12 | QA | Cross-role testing, migration doc | 3 h |

**Total estimate:** **~39 hours** (single developer)

### 19.1 Suggested PR / commit slices

1. `feat(db): enterprise migration 001`  
2. `feat(core): activity log + settings + history`  
3. `feat(nav): enterprise grouped sidebar`  
4. `feat(enrollment): student enroll + coordinator approve`  
5. `feat(compliance): passport visa transcript`  
6. `feat(reports): CSV export center`  
7. `feat(dashboard): Chart.js admin + enterprise widgets`  
8. `docs: update PROJECT_STATUS and README`

---

## 20. Risks & constraints

| Risk | Mitigation |
|------|------------|
| Breaking MVP after migration | Additive-only SQL; smoke test all 33 original URLs |
| Chart.js on slow networks | CDN + defer script; fallback “data table” under canvas |
| Coordinator scope bugs | Central `scope_for_coordinator($sql)` helper |
| Duplicate enrollment | DB UNIQUE + application-level check |
| `install.php` security | Document deletion; no new install on production |
| File upload growth | Reuse existing `uploads/`; settings for max MB |
| Page count explosion | Grouped nav + consistent layout components |

---

## Approval checklist (before coding)

- [ ] Confirm **15 tables** (8 + 7) acceptable  
- [ ] Confirm passport **1:1** per student vs 1:N history (plan: **1 active**, future history optional)  
- [ ] Confirm enrollment requires **approved application** (default: **yes**, toggle in settings)  
- [ ] Confirm optional **002_demo_scale.sql** for evaluator demo (~30 students)  
- [ ] Confirm `admin/analytics.php` merged into dashboard vs kept separate  

---

## Summary metrics (target after implementation)

| Metric | MVP | Enterprise target |
|--------|-----|-------------------|
| Database tables | 8 | **15** |
| UI pages | 33 | **~83** |
| Chart.js charts | 0 | **5–7** |
| Sidebar groups | 0 | **5–7 per role** |
| CSV exports | 0 | **6 endpoints** |
| Activity log actions | 0 | **12+ types** |
| Dashboard widgets | ~4 KPI cards | **14 widget types** |

---

*Next step upon approval: implement Step 1 (`database/migrations/001_enterprise.sql`) and shared infrastructure, then proceed through §19 order.*
