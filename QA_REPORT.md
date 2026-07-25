# QA Audit Report — Global Exchange Portal

**Audit date:** May 2026  
**Environment:** XAMPP · PHP CLI lint · MySQL port `3307` · HTTP probe (`localhost`)  
**Scope:** Full route, sidebar, CRUD, uploads, roles, charts, exports, dashboards  
**Method:** Static code review + `scripts/qa_smoke.php` + selective HTTP checks (no browser automation)

---

## Executive summary

| Severity | Count |
|----------|-------|
| **Critical** | 4 |
| **High** | 7 |
| **Medium** | 12 |
| **Low** | 10 |

**Overall:** With **both** `database/database.sql` and `database/migrations/001_enterprise.sql` applied, core MVP and most enterprise features **load and function**. Primary risks are **security** (`install.php`, document download scope), **admin compliance audit gaps** (visas/transcripts verify UI missing), **seed file downloads**, and **ungraceful failures** if the enterprise migration was skipped.

**Automated smoke result:** `scripts/qa_smoke.php` → `[]` (no missing nav files, all 15 tables present, chart functions execute).

**PHP syntax:** All 86 `.php` files pass `php -l` (no parse errors).

---

## Test environment assumptions

| Check | Result |
|-------|--------|
| MySQL `exchange_portal` with 15 tables | Pass (when migration applied) |
| Apache serving `/exchange_portal` | Pass (HTTP 200 on login, 302 on protected routes for guests) |
| Chart.js CDN reachable | Assumed (not verified offline) |
| Tailwind CDN reachable | Assumed |

---

## 1. Route inventory & test results

### 1.1 Public routes

| Route | Expected | Result | Notes |
|-------|----------|--------|-------|
| `/index.php` | Redirect by role or login | **Pass** | Guest → login; logged-in → role dashboard |
| `/login.php` | 200 form | **Pass** | HTTP 200, no PHP fatal |
| `/register.php` | 200 form | **Pass** | HTTP 200 |
| `/logout.php` | Destroy session → login | **Pass** | Code review; logs `auth.logout` when tables exist |
| `/download.php?id=` | Auth + file stream | **Partial** | See downloads section |
| `/install.php` | DB installer | **Critical risk** | Works but must not remain on production |

### 1.2 Admin routes (17 sidebar + utilities)

| Route | Load | CRUD / action | Notes |
|-------|------|---------------|-------|
| `/admin/index.php` | **Pass** | Dashboard | 5 Chart.js canvases + widgets |
| `/admin/analytics.php` | **Pass** | Redirect → `/admin/index.php` | Sidebar “Analytics” still valid |
| `/admin/students.php` | **Pass** | List | |
| `/admin/student-form.php` | **Pass** | Create/Update | |
| `/admin/student-delete.php` | **Pass** | Delete | Cascades user |
| `/admin/coordinators.php` | **Pass** | List | |
| `/admin/coordinator-form.php` | **Pass** | Create/Update | No delete UI (by design) |
| `/admin/universities.php` | **Pass** | List | |
| `/admin/university-form.php` | **Pass** | Create/Update | Logs activity |
| `/admin/university-delete.php` | **Pass** | Delete | FK may block |
| `/admin/courses.php` | **Pass** | List | |
| `/admin/course-form.php` | **Pass** | Create/Update | Logs activity |
| `/admin/course-delete.php` | **Pass** | Delete | Logs activity |
| `/admin/applications.php` | **Pass** | List | |
| `/admin/application-view.php` | **Pass** | Update status + timeline | `record_status_change` wired |
| `/admin/documents.php` | **Pass** | List / View link | No download link in table (only via `download.php` if URL known) |
| `/admin/enrollments.php` | **Pass** | Approve/Reject | Requires enterprise tables |
| `/admin/passports.php` | **Pass** | Verify/Reject UI | |
| `/admin/visas.php` | **Partial** | List only | **No verify UI** (POST handler dead) |
| `/admin/transcripts.php` | **Partial** | List only | **No verify UI** (POST handler dead) |
| `/admin/reports/index.php` | **Pass** | Hub | |
| `/admin/reports/export-*.php` | **Pass** | CSV download | Logs export activity |
| `/admin/activity-logs.php` | **Pass** | Read | Empty if no logs |
| `/admin/settings.php` | **Pass** | Update | Boolean settings as text fields |
| `/admin/notifications.php` | **Pass** | Read / mark read | MVP |

**Admin delete/action URLs (non-sidebar):** `student-delete.php`, `course-delete.php`, `university-delete.php` — **Pass** (redirect after action).

### 1.3 Coordinator routes (13 sidebar + utilities)

| Route | Load | CRUD / action | Notes |
|-------|------|---------------|-------|
| `/coordinator/index.php` | **Pass** | Dashboard | 1 chart + queue + feed |
| `/coordinator/students.php` | **Pass** | Read (scoped) | |
| `/coordinator/applications.php` | **Pass** | List | |
| `/coordinator/application-review.php` | **Pass** | Update + timeline | |
| `/coordinator/documents.php` | **Pass** | Approve/Reject | Logs `document.*` |
| `/coordinator/enrollments.php` | **Pass** | Approve/Reject | Notifies student |
| `/coordinator/passports.php` | **Pass** | Verify/Reject | |
| `/coordinator/visas.php` | **Pass** | Verify/Reject | No linked-doc sync / notify (see High) |
| `/coordinator/transcripts.php` | **Pass** | Verify/Reject | No student notify |
| `/coordinator/courses.php` | **Pass** | List | |
| `/coordinator/course-form.php` | **Pass** | Create/Update | |
| `/coordinator/reports/index.php` | **Pass** | Hub | |
| `/coordinator/reports/export-*.php` | **Pass** | Scoped CSV | |
| `/coordinator/activity-logs.php` | **Pass** | Read (scoped feed query) | |
| `/coordinator/notifications.php` | **Pass** | Read | |

### 1.4 Student routes (11 sidebar + utilities)

| Route | Load | CRUD / action | Notes |
|-------|------|---------------|-------|
| `/student/index.php` | **Pass** | Dashboard | Compliance checklist |
| `/student/applications.php` | **Pass** | List | MVP |
| `/student/application-form.php` | **Pass** | Create/Update/Submit | History on submit |
| `/student/documents.php` | **Pass** | List + download link | |
| `/student/document-upload.php` | **Pass** | Upload | |
| `/student/courses.php` | **Conditional** | Catalog + Enroll | **Fatal if `enrollments` table missing** |
| `/student/enrollments.php` | **Conditional** | List + drop | Empty list if no migration |
| `/student/enroll.php` | **Conditional** | POST only | **Fatal if `enrollments` missing** |
| `/student/enrollment-drop.php` | **Conditional** | POST drop | |
| `/student/passport.php` | **Conditional** | Save + upload | Graceful message if no migration |
| `/student/visas.php` | **Conditional** | List | Empty if no migration |
| `/student/visa-form.php` | **Conditional** | Create/Edit | Form shown even if migration missing |
| `/student/transcripts.php` | **Conditional** | List | |
| `/student/transcript-form.php` | **Conditional** | Create + upload | POST guarded |
| `/student/profile.php` | **Pass** | Update | MVP |
| `/student/notifications.php` | **Pass** | Read | MVP |

---

## 2. Sidebar navigation audit

All items in `includes/nav-config.php` resolve to existing PHP files (**Pass** — verified by `scripts/qa_smoke.php`).

| Role | Groups | Links | Badge keys |
|------|--------|-------|------------|
| Admin | 7 | 17 | applications, enrollments, documents, passports, visas |
| Coordinator | 6 | 13 | applications, enrollments, documents |
| Student | 5 | 11 | None |

**Issues:**
- **Medium:** Mobile nav flattens all links — long horizontal scroll (usability).
- **Low:** Admin “Analytics” goes to redirect page (same as dashboard content) — not broken but redundant.

---

## 3. CRUD operations

### 3.1 MVP entities (unchanged)

| Entity | Create | Read | Update | Delete | Verdict |
|--------|--------|------|--------|--------|---------|
| Students (admin) | Pass | Pass | Pass | Pass | OK |
| Coordinators (admin) | Pass | Pass | Pass | N/A | OK |
| Universities | Pass | Pass | Pass | Pass | OK |
| Courses (admin/coord) | Pass | Pass | Pass | Pass (admin) | OK |
| Applications | Pass | Pass | Pass | N/A | OK |
| Documents | Pass | Pass | Status | N/A | OK |
| Notifications | System | Pass | Mark read | N/A | OK |
| Student self-register | Pass | — | — | — | OK |
| Profile | — | Pass | Pass | — | OK |

### 3.2 Enterprise entities

| Entity | Create | Read | Update/Verify | Delete | Verdict |
|--------|--------|------|---------------|--------|---------|
| Enrollments | Pass (student) | Pass | Pass (coord/admin) | Drop (student) | OK if migrated |
| Passports | Pass (student) | Pass | Pass (coord/admin UI) | N/A | OK |
| Visas | Pass | Pass | Pass (coord only UI) | N/A | **Admin verify UI missing** |
| Transcripts | Pass | Pass | Pass (coord only UI) | N/A | **Admin verify UI missing** |
| System settings | N/A | Pass | Pass | N/A | OK |
| Activity logs | Auto | Pass | N/A | N/A | OK |
| App status history | Auto | Pass (timeline) | N/A | N/A | OK |

---

## 4. Upload workflows

| Workflow | Page | Result | Issues |
|----------|------|--------|--------|
| Generic document upload | `student/document-upload.php` | **Pass** | Validates size/type; stores under `uploads/` |
| Passport scan (optional) | `student/passport.php` | **Pass** | Creates `documents` row + links `passports.document_id` |
| Visa scan (optional) | `student/visa-form.php` | **Pass** | Creates document row |
| Transcript file (required) | `student/transcript-form.php` | **Pass** | Creates document + transcript row |
| Upload without enterprise DB | Any enterprise upload | **Fail** | SQL error on insert to missing tables |

**Upload config:** `max_upload_mb` from `system_settings` when migrated, else `config.php` default (5 MB). **Pass**

**CSRF:** Present on POST forms reviewed. **Pass**

---

## 5. Download workflows

| Case | Result | Issue |
|------|--------|-------|
| Student downloads own document | **Pass** (if file exists on disk) | |
| Admin downloads any document | **Pass** | |
| Coordinator downloads any document | **Pass** | **High: no university scope check** |
| Seed documents (`documents` seed rows) | **Fail** | Files not on disk → 404 message (expected) |
| Passport-linked document | **Pass** | Same `download.php` by `documents.id` |

---

## 6. Role & permission testing

| Test | Expected | Result |
|------|----------|--------|
| Guest → `/admin/*` | Redirect login | **Pass** (302) |
| Student → `/admin/*` | Denied → login flash | **Pass** (`require_role`) |
| Coordinator → `/admin/*` | Denied | **Pass** |
| Coordinator data scope (applications) | Home/host uni | **Pass** (SQL scoped) |
| Coordinator data scope (downloads) | Should be scoped | **Fail** (global access) |
| Student → other student data | Blocked | **Pass** (queries use own `student_id`) |
| Inactive user login | Rejected | **Pass** (`status = active`) |

---

## 7. Charts (Chart.js)

| Chart | Page | ID | Result | Notes |
|-------|------|-----|--------|-------|
| Application trend | Admin dashboard | `chartAppTrend` | **Pass** | Line chart; data from `applications.created_at` |
| Students by university | Admin dashboard | `chartStudentsByUni` | **Pass** | Bar chart |
| Approval rate | Admin dashboard | `chartApprovalRate` | **Pass** | Doughnut; may show zeros if no approved/rejected |
| Document verification | Admin dashboard | `chartDocVerification` | **Pass** | Bar chart |
| Monthly application growth | Admin dashboard | `chartMonthlyGrowth` | **Partial** | Uses `submitted_at` — **drafts excluded**; often low/zero bars |
| Coordinator pending trend | Coordinator dashboard | `chartCoordTrend` | **Pass** | 6-week line |

**Empty / blank chart risks:**
- **Medium:** If Chart.js CDN blocked or loads after `DOMContentLoaded` race (`defer` in `<head>`), `typeof Chart === 'undefined'` → **silent empty canvases** (coordinator chart checks; admin script does not).
- **Low:** All-zero data still renders valid charts (not broken, looks “empty”).

---

## 8. CSV exports

| Export | Role | Result | Notes |
|--------|------|--------|-------|
| Applications | Admin | **Pass** | UTF-8 BOM |
| Students | Admin | **Pass** | |
| Documents | Admin | **Pass** | |
| Applications | Coordinator | **Pass** | University-scoped |
| Students | Coordinator | **Pass** | Scoped |
| Documents | Coordinator | **Pass** | Scoped |

**Not tested:** Excel open, very large datasets (only seed volume).

---

## 9. Dashboard widgets

| Widget | Admin | Coordinator | Student | Result |
|--------|-------|-------------|---------|--------|
| KPI stat cards | Yes | Yes | Yes | **Pass** |
| Verification queue | Yes | Yes | No | **Pass** |
| Activity feed | Yes | Yes | Yes | **Pass** |
| Recent applications table | Yes | Yes | Partial | **Pass** |
| Status summary | Yes | No | No | **Pass** |
| Compliance checklist | No | No | Yes | **Pass** |
| Application timeline | Review pages | Review pages | No | **Pass** |

---

## 10. Issues by severity

### Critical

| ID | Area | Problem | Reproduction |
|----|------|---------|--------------|
| C-1 | Security | **`install.php` exposed** — anyone can run installer / re-apply schema against DB | Visit `/install.php` while file exists |
| C-2 | Permissions | **`download.php` — coordinators can download any student’s document** (no `university_id` scope) | Login as coordinator; open `download.php?id=` for out-of-scope document |
| C-3 | Database | **`student/courses.php` crashes** if `enrollments` table missing — calls `course_enrollment_count()` unconditionally in loop | Skip `001_enterprise.sql`; open Course Catalog as student |
| C-4 | Database | **`student/enroll.php` crashes** on POST if `enrollments` table missing | Click Enroll without migration |

### High

| ID | Area | Problem | Reproduction |
|----|------|---------|--------------|
| H-1 | Admin UI | **`/admin/visas.php` — POST verify handler exists but no Verify/Reject buttons** — admin cannot audit-verify visas from UI | Open admin visas; pending records stay pending |
| H-2 | Admin UI | **`/admin/transcripts.php` — same missing verify UI** | Same as H-1 |
| H-3 | Downloads | **All seed `documents` rows fail download** — metadata only, no files in `uploads/` | Click download on seed docs → 404 text response |
| H-4 | Charts | **Admin charts may not render if Chart.js fails to load** — no fallback message; `admin_charts_script.php` assumes `Chart` global | Block CDN; reload admin dashboard |
| H-5 | Compliance | **`ComplianceVerify` only syncs linked `documents` row for passports** — visas/transcripts verify do not update `documents.status` or notify students | Verify visa as coordinator; student not notified |
| H-6 | Enrollment | **Enrollment blocked when setting requires approved app** — correct by rules but **no in-UI explanation** on course card (only flash after POST) | Student without approved app clicks Enroll |
| H-7 | Operations | **Partial DB (enterprise without MVP)** breaks entire app | Import only `001_enterprise.sql` without base schema |

### Medium

| ID | Area | Problem |
|----|------|---------|
| M-1 | Student UI | `visa-form.php` / `transcript-form.php` render forms when enterprise tables absent; save appears possible but POST does nothing useful |
| M-2 | Analytics | Monthly growth chart undercounts — uses `submitted_at`; many applications have `NULL` until submitted |
| M-3 | Activity logs | Coordinator activity feed query is very broad (`entity_type IN (...)` OR) — may show low-relevance global entries |
| M-4 | Application review | Document rows on review pages have **no download link** — must know `download.php?id=` |
| M-5 | Admin documents | List page has no per-row download action (unlike student documents list) |
| M-6 | Status badges | `status_badge('verified')` works; document status uses `approved` — UI consistent but naming confusing |
| M-7 | Settings | Boolean settings (`enrollment_requires_approved_application`) edited as plain text field — user can enter invalid values |
| M-8 | History | Updating application **notes only** without status change does not append history row (by design in `record_status_change`) |
| M-9 | Redirects | `require_role` failure sends user to **login** with flash, not HTTP 403 page |
| M-10 | Passport verify | Admin/coordinator reject passport sets `documents.status` to `rejected` but passport status `rejected` — OK; expired status never auto-set from `expiry_date` |
| M-11 | Enrollments | Admin enrollment approve form uses two submit buttons with same `name="status"` — works in HTML but fragile |
| M-12 | Duplicate logging | `record_status_change` logs once per change — OK; re-saving same status logs nothing (may look like “broken save”) |

### Low

| ID | Area | Problem |
|----|------|---------|
| L-1 | Branding | Login hero title differs from `APP_NAME` in config |
| L-2 | Config | `ITEMS_PER_PAGE` defined but unused — no pagination on long lists |
| L-3 | Empty states | Several admin compliance pages show blank area when zero rows (no empty-state copy) |
| L-4 | Plan gap | `admin/passport-view.php` not implemented (not linked; optional) |
| L-5 | Plan gap | `student/visa-delete.php` not implemented |
| L-6 | UX | Sidebar badge counts not shown on mobile pill nav |
| L-7 | CSRF | GET-based delete URLs (`student-delete.php`, etc.) — protected by role but vulnerable to CSRF if session hijacked |
| L-8 | Login | `log_activity` on login before session fully established — works; uses `user_id` from DB row |
| L-9 | Charts | Doughnut/bar colors array length may not match dynamic label count when only one document status exists |
| L-10 | install.php | SQL splitter in installer may fail on some statement formats (edge case) |

---

## 11. Broken pages summary

| Page | Condition | Symptom |
|------|-----------|---------|
| `student/courses.php` | No `enrollments` table | **PDO fatal error** |
| `student/enroll.php` | No `enrollments` table | **PDO fatal error** |
| `download.php` | Seed document IDs | **404** “File not found on server” (not PHP crash) |
| `admin/visas.php` | Always | Page loads; **verify action non-functional** (no buttons) |
| `admin/transcripts.php` | Always | Same as visas |
| Any page | No DB / wrong port in `config.php` | Connection error |
| `install.php` | Public access | **Security incident**, not display bug |

**No broken pages found** for: login, register, admin dashboard (with full DB), coordinator dashboard, student dashboard (with full DB), MVP CRUD lists, reports exports (with auth session).

---

## 12. Missing includes / undefined variables

| Check | Result |
|-------|--------|
| Missing `init.php` on routed pages | **None found** |
| Missing `layout.php` / `footer.php` on dashboards | **None found** |
| `admin_charts_script.php` without chart variables | **Only included from `admin/index.php`** (defines all five) — **OK** |
| `header.php` uses `$user` when guest on login | **OK** (`$user` null; no notice) |
| Widget partials without required vars | **OK** if included as documented |

---

## 13. SQL / schema notes

- Foreign keys enforce sane deletes (universities with students blocked). **Pass**
- `application_status_history` backfill in migration requires at least one admin user. **Pass** with seed data
- `verify_compliance_record` uses whitelisted table names only. **Pass** (no injection via table param)

---

## 14. Recommended fix order (fixes only — not implemented in this audit)

1. **C-1** — Remove or password-protect `install.php` on any shared host  
2. **C-2** — Scope `download.php` for coordinators (and optionally students) by university  
3. **C-3 / C-4** — Guard `course_enrollment_count()` and enroll POST with `enterprise_tables_ready()`  
4. **H-1 / H-2** — Add verify/reject UI on admin visas/transcripts (or remove dead POST handlers)  
5. **H-3** — Document seed limitation or add placeholder files  
6. **H-4** — Load Chart.js before init script or add `if (typeof Chart !== 'undefined')` guard + message on admin dashboard  
7. **H-5** — Extend `ComplianceVerify` for visas/transcripts (notify + document sync)

---

## 15. QA artifacts in repo

| File | Purpose |
|------|---------|
| `scripts/qa_smoke.php` | CLI: tables, nav files, chart functions |
| `scripts/qa_http.php` | CLI: guest HTTP status codes |

---

## 16. Sign-off matrix

| Area | Status |
|------|--------|
| MVP routes | **Pass** (with DB) |
| Enterprise routes | **Pass** (with migration) |
| Sidebar (41 links) | **Pass** |
| CRUD (MVP) | **Pass** |
| CRUD (enterprise) | **Partial** (admin visa/transcript verify gap) |
| Uploads | **Pass** |
| Downloads | **Partial** (seed + coordinator scope) |
| Charts | **Pass** (CDN dependent) |
| Exports | **Pass** |
| Dashboards | **Pass** |
| Production-ready | **No** — address Critical/High first |

---

*End of QA report. No code changes were made during this audit.*
