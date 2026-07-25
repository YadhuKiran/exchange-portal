# Presentation Checklist — Global Exchange Portal

**Target:** 10-minute live demo · maximum marks  
**Stack:** Core PHP · MySQL (15 tables) · Chart.js · Tailwind · role-based sessions  
**Base URL:** `http://localhost/exchange_portal`  
**Demo password (all accounts):** `password123`

---

## Before You Present (5 minutes — not counted)

| Step | Action | Why it matters |
|------|--------|----------------|
| ☐ | XAMPP: Apache + MySQL running (port **3307**) | Avoid connection errors on stage |
| ☐ | DB loaded: `database.sql` → `001_enterprise.sql` → **`DEMO_DATA.sql`** | Populated charts, queues, and lists |
| ☐ | `config.php`: `ALLOW_WEB_INSTALL = false` | Security hygiene |
| ☐ | Browser: one normal window + one incognito (second role) | Fast role switching |
| ☐ | Pre-login tabs: Admin dashboard URL bookmarked | Saves ~30s at the end |
| ☐ | Optional: open phpMyAdmin on `exchange_portal` | Database section backup |
| ☐ | Say aloud once: *"Three roles, one database, full audit trail"* | Frames the architecture for judges |

**Demo accounts (pin these):**

| Role | Email | University / scope |
|------|-------|-------------------|
| Admin | `admin@exchangeportal.com` | Global |
| Coordinator | `mia.harris@coord1.edu` | MIT (student home + host apps) |
| Student | `student01@demo.exchangeportal.com` | Emma Smith · MIT |

**DEMO_DATA scale (mention briefly):** 15 universities · 50 students · 150 applications · 200 documents · 80 enrollments · 30 passports/visas/transcripts each.

---

## 10-Minute Run Sheet

| Block | Time | Section |
|-------|------|---------|
| Opening | 0:00–0:30 | Problem + solution (1 sentence) |
| 1 | 0:30–2:30 | Student demo flow |
| 2 | 2:30–4:30 | Coordinator demo flow |
| 3 | 4:30–7:00 | Admin demo flow |
| 4 | 7:00–8:00 | Database demonstration |
| 5 | 8:00–9:00 | Analytics demonstration |
| 6 | 9:00–9:45 | Reports export |
| Close | 9:45–10:00 | Recap + Q&A buffer |

---

## 1. Student Demo Flow (~2 minutes)

**Login:** `student01@demo.exchangeportal.com` / `password123`  
**Narrative:** *"End-to-end student journey from application to compliance."*

| Order | Screen | Path | Show / say |
|-------|--------|------|------------|
| ☐ | Dashboard | `/student/index.php` | KPI cards · compliance checklist · recent applications |
| ☐ | Applications | `/student/applications.php` | Mix of statuses (seed data) |
| ☐ | New / edit application | `/student/application-form.php` | Draft → personal statement → **Submit** (or point at existing submitted row) |
| ☐ | Documents | `/student/documents.php` | List + **Download** link (seed PDF placeholder works) |
| ☐ | Upload | `/student/document-upload.php` | File validation · CSRF · link to application |
| ☐ | Passport | `/student/passport.php` | Structured compliance record + optional scan |
| ☐ | Visas / Transcripts | `/student/visas.php`, `/student/transcript-form.php` | Enterprise modules beyond generic uploads |
| ☐ | Course catalog | `/student/courses.php` | Partner universities · seat counts · enroll (or amber rule if no approved app) |
| ☐ | My enrollments | `/student/enrollments.php` | Pending / approved statuses |
| ☐ | Notifications | `/student/notifications.php` | In-app alerts after coordinator actions |

**Marks phrases:** self-service · draft/submit workflow · document lifecycle · enrollment gating · student-scoped data (cannot see other students).

**Skip if short on time:** Profile page, visa create form (list only).

---

## 2. Coordinator Demo Flow (~2 minutes)

**Logout → Login:** `mia.harris@coord1.edu` / `password123`  
**Narrative:** *"University-scoped operations — coordinators only see their institution's pipeline."*

| Order | Screen | Path | Show / say |
|-------|--------|------|------------|
| ☐ | Dashboard | `/coordinator/index.php` | University banner · pending KPIs · **Chart.js** 6-week trend · verification queue |
| ☐ | Applications | `/coordinator/applications.php` | Scoped list (non-draft) |
| ☐ | Review | `/coordinator/application-review.php?id=` | Change status · coordinator notes · **status timeline** |
| ☐ | Documents | `/coordinator/documents.php` | **Approve / Reject** pending · triggers notification |
| ☐ | Passports / Visas / Transcripts | `/coordinator/passports.php` etc. | **Verify / Reject** on pending rows |
| ☐ | Enrollments | `/coordinator/enrollments.php` | Approve or reject student course requests |
| ☐ | Students | `/coordinator/students.php` | Read-only directory · home/host scope |
| ☐ | Courses | `/coordinator/courses.php` | CRUD for **own** university only |

**Marks phrases:** role-based access control · university scoping · verification queue · audit-friendly review · student notification.

**Live action (high impact):** Approve one pending document or verify one visa → mention student receives notification.

**Skip if short on time:** Activity logs, course create form.

---

## 3. Admin Demo Flow (~2.5 minutes)

**Logout → Login:** `admin@exchangeportal.com` / `password123`  
**Narrative:** *"Central command for global mobility — all institutions, all compliance types."*

| Order | Screen | Path | Show / say |
|-------|--------|------|------------|
| ☐ | Dashboard | `/admin/index.php` | 6 KPI cards · **5 Chart.js charts** · verification queue · activity feed · recent applications |
| ☐ | Applications | `/admin/applications.php` → **View** | Global list · status update · timeline |
| ☐ | Enrollments | `/admin/enrollments.php` | Approve / reject across universities |
| ☐ | Compliance hub | `/admin/documents.php`, `/admin/passports.php`, `/admin/visas.php`, `/admin/transcripts.php` | Admin verify on visas/transcripts · document registry |
| ☐ | Directory | `/admin/students.php`, `/admin/universities.php` | CRUD · 15 partner institutions (DEMO_DATA) |
| ☐ | Settings | `/admin/settings.php` | DB-driven config (e.g. enrollment requires approved application) |
| ☐ | Activity logs | `/admin/activity-logs.php` | Who did what · timestamps |

**Marks phrases:** enterprise admin · global visibility · configurable business rules · separation of duties (admin vs coordinator).

**Skip if short on time:** Coordinator CRUD, course delete, notifications mark-all-read.

---

## 4. Database Demonstration Flow (~1 minute)

**Tool:** phpMyAdmin or MySQL CLI · database `exchange_portal` · port **3307**

| Order | Show | Say |
|-------|------|-----|
| ☐ | **15 tables** in one schema | Normalized design · InnoDB · UTF-8 |
| ☐ | MVP core: `users`, `students`, `applications`, `documents`, `universities`, `courses` | Three-role model |
| ☐ | Enterprise: `passports`, `visas`, `transcripts`, `enrollments`, `application_status_history`, `activity_logs`, `system_settings` | Additive migration — no schema break |
| ☐ | Open `applications` row | `home_university_id` / `host_university_id` FKs · status enum |
| ☐ | Open `application_status_history` | Audit trail per status change |
| ☐ | Quick `SELECT COUNT(*)` | 50 students · 150 applications · 200 documents (matches DEMO_DATA) |

**Marks phrases:** referential integrity · foreign keys · audit tables · seed + demo data separation (`database.sql` vs `DEMO_DATA.sql`).

**Do not:** run `install.php` on stage · show passwords in `users` table (scroll past `password_hash`).

---

## 5. Analytics Demonstration Flow (~1 minute)

**Stay on:** `/admin/index.php` (primary) · optional `/admin/analytics.php` (redirects to dashboard)

| Chart (canvas ID) | Type | Data source (say this) |
|-------------------|------|-------------------------|
| ☐ `chartAppTrend` | Line | Applications created per month (12-month window) |
| ☐ `chartApprovalRate` | Doughnut | Approved vs in-progress vs rejected |
| ☐ `chartStudentsByUni` | Bar | Students per partner university |
| ☐ `chartMonthlyGrowth` | Bar | Submitted applications by month (`submitted_at`) |
| ☐ `chartDocVerification` | Bar | Documents pending / approved / rejected |
| ☐ Coordinator `chartCoordTrend` | Line | Pending applications by week (coordinator dashboard) |

**Marks phrases:** Chart.js · real SQL aggregates · operational KPIs · data-driven decisions.

**If CDN blocked:** Charts show amber fallback message (mention graceful degradation).

**Widget callouts (10 seconds each):** verification queue · activity feed · sidebar badge counts on Applications / Documents / Enrollments.

---

## 6. Reports Export Demonstration Flow (~45 seconds)

| Role | Path | Exports |
|------|------|---------|
| ☐ Admin | `/admin/reports/index.php` | Applications · Students · Documents CSV |
| ☐ Coordinator | `/coordinator/reports/index.php` | Same three · **university-scoped** data |

| Step | Action | Show |
|------|--------|------|
| ☐ | Click **Download CSV** on Applications | File downloads |
| ☐ | Open in Excel / Notepad | UTF-8 BOM · column headers · 150 application rows (admin) |
| ☐ | Say | Export logged in **activity_logs** (admin action) |

**Marks phrases:** reporting for accreditation · CSV export · scoped coordinator reports vs global admin.

**Skip if short on time:** Only demo one export (Applications).

---

## Closing Script (~15 seconds)

> *"We built a three-role exchange portal on PHP and MySQL with fifteen tables, Chart.js analytics, compliance modules, audit history, and CSV reporting — demonstrated with fifty students and a hundred fifty applications in our demo dataset."*

---

## Judge-Ready Feature Matrix (tick mentally)

| Criterion | Where demonstrated |
|-----------|-------------------|
| Authentication & sessions | Login · logout · role redirect |
| RBAC | Student / coordinator / admin paths |
| CRUD | Admin students, universities, courses |
| Workflow | Application draft → submit → review → approve |
| File handling | Upload · secure download · coordinator scope |
| Enterprise compliance | Passport · visa · transcript |
| Enrollment | Catalog · approve · capacity |
| Analytics | Admin + coordinator charts |
| Audit | `application_status_history` · `activity_logs` |
| Configuration | `system_settings` |
| Reporting | CSV exports |
| Security | CSRF on forms · `install.php` disabled · hashed passwords |

---

## Emergency Fallbacks

| Problem | Recovery |
|---------|----------|
| Blank charts | Mention CDN; show KPI numbers and phpMyAdmin counts |
| Login fails | Re-import DEMO_DATA; confirm admin row exists |
| Empty lists | `enterprise_tables_ready()` false → run `001_enterprise.sql` |
| Download 404 | Use student documents list (seed placeholders in `download.php`) |
| Over time | Cut student visa/transcript + admin settings; keep Admin dashboard + one CSV export + DB slide |

---

## Optional Handout Line (if allowed)

**Repository artifacts:** `PROJECT_STATUS.md` · `QA_REPORT.md` · `DEMO_DATA.sql` · `scripts/qa_smoke.php`

---

*Last updated: May 2026 — aligned with enterprise portal + DEMO_DATA.sql.*
