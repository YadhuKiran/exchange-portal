# Enterprise Upgrade — Implementation Plan

**Project:** Global Student Mobility & Exchange Portal  
**Goal:** Premium enterprise SaaS appearance and UX without architectural rebuild  
**Constraint:** Keep file-based PHP routing, 15-table schema pattern, existing business logic  
**Date:** May 2026

---

## 1. Current State Analysis

### What exists (keep)

| Layer | State |
|-------|--------|
| **Routing** | Direct PHP pages (`admin/*`, `coordinator/*`, `student/*`) |
| **Auth** | Session + `require_role()` + CSRF |
| **Database** | 15 tables (MVP + enterprise migration) |
| **Features** | Applications, documents, passport/visa/transcript, enrollments, activity logs, CSV reports, Chart.js dashboards |
| **Layout** | `includes/layout.php` + `nav-config.php` + Tailwind CDN |
| **Components** | `stat_card()`, `page_actions()`, `empty_state()`, widgets |

### Gaps vs. enterprise target

| Area | Current | Target |
|------|---------|--------|
| **Visual design** | Tailwind defaults, “GE” text logo, plain white cards | Design system, SVG brand, glass/gradient, motion |
| **Tables** | Static HTML tables, no `#`, no search/pagination | Data tables with S/N, filters, bulk actions |
| **Analytics** | `admin/analytics.php` redirects to dashboard | Dedicated analytics center |
| **Document views** | List + `download.php` link only | Dedicated view pages + PDF embed + verification timeline |
| **Admin users** | Single seed admin; no admin CRUD | Admin management module |
| **Profiles** | Student profile only | All roles + avatar + dedicated password page |
| **Universities** | Text fields for country/city (exist) | Logo/image upload + card grid UI |
| **Coordinators** | Free-text department | Department dropdown + profile/activity |
| **Activity logs** | Simple 200-row table | Audit center with filters + CSV/PDF export |
| **Icons** | Inline SVG in few places | Consistent icon set (Heroicons/Lucide via SVG sprite) |

### File inventory (87 PHP files today)

- **Shared:** `includes/init.php`, `header.php`, `layout.php`, `footer.php`, `components.php`, `nav-config.php`, 6 widgets, 7 enterprise helpers  
- **Admin:** 28 pages (lists, forms, reports, settings)  
- **Coordinator:** 18 pages  
- **Student:** 16 pages  
- **Public:** login, register, download, index, logout, install  

---

## 2. Architecture Principles (non-negotiable)

1. **No framework migration** — remain Core PHP + PDO + includes.  
2. **Additive DB only** — new migration `002_enterprise_ui.sql`; never drop existing columns/tables.  
3. **Extend, don’t replace** — wrap existing queries in shared table/analytics helpers.  
4. **Single design layer** — all visual changes flow through `assets/css/enterprise.css`, `includes/components/*`, `header.php`, `layout.php`.  
5. **Reuse existing helpers** — `handle_upload()`, `log_activity()`, `verify_compliance_record()`, `stream_csv()`, Chart.js functions.

---

## 3. Database Changes

**Migration file:** `database/migrations/002_enterprise_ui.sql`

### 3.1 `users` table (additive)

| Column | Type | Purpose |
|--------|------|---------|
| `profile_picture` | VARCHAR(500) NULL | Relative path under `uploads/avatars/` |
| `updated_at` | TIMESTAMP ON UPDATE | Profile audit (optional) |

### 3.2 `universities` table (additive)

| Column | Type | Purpose |
|--------|------|---------|
| `logo_path` | VARCHAR(500) NULL | Small logo under `uploads/universities/` |
| `image_path` | VARCHAR(500) NULL | Banner/campus image |

*Note: `country` and `city` already exist — Phase 6 is UI/upload only for those fields.*

### 3.3 `system_settings` seed (no new table)

Insert configurable list for coordinator departments:

```sql
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_group, data_type, label)
VALUES ('coordinator_departments', '["International Programs","Global Mobility Office",...]', 'coordinator', 'json', 'Coordinator Departments');
```

### 3.4 Optional lookup table (Phase 5 — only if dropdown needs CRUD)

`departments (id, name, status)` — **defer**; JSON in `system_settings` is sufficient for presentation.

### 3.5 No schema change required

- Activity log filtering — query params only  
- Analytics charts — new PHP functions only  
- PDF export — server-side HTML render, no new tables  
- Admin management — uses existing `users.role = 'admin'`  
- Verification history — reuse `application_status_history` + `activity_logs` + compliance `verified_at`

### 3.6 Upload directories (filesystem)

```
uploads/avatars/
uploads/universities/
uploads/.htaccess          (existing — extend if needed)
```

---

## 4. New Shared Assets & Includes

### 4.1 Static assets (new)

| Path | Purpose |
|------|---------|
| `assets/css/enterprise.css` | Design tokens, glass cards, animations, skeletons |
| `assets/js/enterprise.js` | Page transitions, sidebar, flash, skeleton toggles |
| `assets/js/data-table.js` | Client search/sort + server pagination hooks |
| `assets/img/logo.svg` | Globe + graduation cap brand mark |
| `assets/img/logo-mark.svg` | Sidebar compact logo |
| `assets/img/illustrations/empty-applications.svg` | Empty states |
| `assets/img/illustrations/empty-documents.svg` | Empty states |
| `assets/img/illustrations/empty-analytics.svg` | Empty states |
| `assets/img/illustrations/hero-mobility.svg` | Login / dashboard hero |

### 4.2 PHP components (new)

| Path | Purpose |
|------|---------|
| `includes/brand-logo.php` | `brand_logo($size)` SVG helper |
| `includes/components/data-table.php` | Reusable table shell: S/N, search, filters, pagination, bulk bar |
| `includes/components/form-field.php` | Consistent labels, errors, selects |
| `includes/components/page-header.php` | Title, breadcrumbs, actions |
| `includes/components/document-preview.php` | PDF iframe + download + metadata |
| `includes/components/user-avatar.php` | Avatar image or initials |
| `includes/components/audit-timeline.php` | Verification/history timeline |
| `includes/enterprise/TableQuery.php` | `paginate_query()`, filters, sort whitelist |
| `includes/enterprise/AnalyticsData.php` | Extended chart datasets (Phase 2) |
| `includes/enterprise/ExportPdf.php` | HTML → PDF via Dompdf (single vendored lib) or print-friendly HTML export |
| `includes/enterprise/ProfileHelper.php` | Avatar upload, password change validation |
| `includes/enterprise/AdminUserHelper.php` | Create/deactivate/reset admin |

### 4.3 Modified core includes

| File | Changes |
|------|---------|
| `includes/header.php` | Link `enterprise.css`, expanded Tailwind theme, meta theme-color |
| `includes/layout.php` | SVG logo, glass sidebar, profile link, fade-in main, icon nav items |
| `includes/footer.php` | Load `enterprise.js` |
| `includes/components.php` | Upgrade `stat_card`, `empty_state` with illustrations + motion |
| `includes/init.php` | Require new helpers; `avatar_url()`, extend `handle_upload()` for avatars/logos |
| `includes/nav-config.php` | Add Admin Users, Account/Profile links per role |

---

## 5. Phase-by-Phase Plan

### PHASE 1 — Complete UI/UX Redesign (~foundation for all phases)

**Objective:** Premium design system applied globally.

**UI changes:**

- Color palette: deep navy sidebar, indigo/violet accents, soft page gradient background  
- Typography: Inter + optional display font for headings (`Plus Jakarta Sans`)  
- Cards: `glass-card`, elevated shadow, hover lift (`translate-y`, shadow-lg)  
- Buttons: primary/secondary/ghost variants with transition  
- Sidebar: collapse animation, active indicator, icon + label  
- Loading: skeleton placeholders on dashboard widgets  
- Motion: `fade-in-up` on main content, staggered widget entrance  
- Login/register: hero illustration, branded panel, remove “GE” text  

**Files to modify (Phase 1):**

| File |
|------|
| `includes/header.php` |
| `includes/layout.php` |
| `includes/footer.php` |
| `includes/components.php` |
| `login.php` |
| `register.php` |
| `includes/widgets/activity_feed.php` |
| `includes/widgets/verification_queue.php` |
| `includes/widgets/compliance_checklist.php` |
| `includes/widgets/status_summary.php` |
| `includes/widgets/application_timeline.php` |
| `admin/index.php` |
| `coordinator/index.php` |
| `student/index.php` |

**Files to create (Phase 1):**

| File |
|------|
| `assets/css/enterprise.css` |
| `assets/js/enterprise.js` |
| `assets/img/logo.svg` |
| `assets/img/logo-mark.svg` |
| `assets/img/illustrations/*.svg` (4–6 files) |
| `includes/brand-logo.php` |
| `includes/components/page-header.php` |

**Database:** None in Phase 1.

---

### PHASE 2 — Admin Experience Upgrade

**Objective:** Enterprise data tables + dedicated analytics center.

#### 2A — Data tables (all admin list pages)

Add to each table:

- `#` serial column (row offset from pagination)  
- Toolbar: search box, status filter, date filter where applicable  
- Server pagination via `ITEMS_PER_PAGE` (10) + `TableQuery.php`  
- Row hover + selected state for bulk actions  
- Sortable column headers (whitelist columns)  
- Bulk: export selected / mark read (where safe)

**Admin pages to upgrade:**

| Page | File |
|------|------|
| Applications | `admin/applications.php` |
| Documents | `admin/documents.php` |
| Passports | `admin/passports.php` → convert to table |
| Visas | `admin/visas.php` → convert to table |
| Transcripts | `admin/transcripts.php` → convert to table |
| Students | `admin/students.php` |
| Coordinators | `admin/coordinators.php` |
| Universities | `admin/universities.php` |
| Courses | `admin/courses.php` |
| Enrollments | `admin/enrollments.php` |
| Activity Logs | `admin/activity-logs.php` (Phase 7 extends) |

**New/ modified includes:**

- `includes/components/data-table.php`  
- `includes/enterprise/TableQuery.php`  
- `assets/js/data-table.js`  

#### 2B — Analytics center

Replace redirect in `admin/analytics.php` with full page:

| Section | Chart / widget |
|---------|----------------|
| University comparison | Bar: applications by host university |
| Application trends | Line: 12-month + status breakdown |
| Enrollment analytics | Bar: enrollments by status / university |
| Compliance analytics | Stacked bar: passport/visa/transcript pending vs verified |
| Student distribution | Doughnut: students by home university |
| Export | Button to download chart data CSV |

**New files:**

| File |
|------|
| `admin/analytics.php` (rewrite) |
| `includes/enterprise/AnalyticsData.php` |
| `includes/widgets/analytics_charts_script.php` |
| `admin/reports/export-analytics.php` (optional) |

**Extend:**

| File |
|------|
| `includes/enterprise/ChartData.php` (or delegate to AnalyticsData) |

**Database:** None (queries only).

---

### PHASE 3 — Document Management Improvement

**Objective:** Rich view pages with preview, download, verification history.

**New pages:**

| Page | File |
|------|------|
| Document view (admin) | `admin/document-view.php?id=` |
| Passport view (admin) | `admin/passport-view.php?id=` |
| Visa view (admin) | `admin/visa-view.php?id=` |
| Transcript view (admin) | `admin/transcript-view.php?id=` |
| Document view (coordinator) | `coordinator/document-view.php?id=` |
| Passport/visa/transcript views (coordinator) | `coordinator/passport-view.php`, etc. (optional shared partial) |

**Shared components:**

- `includes/components/document-preview.php` — `<iframe>` for PDF, `<img>` for JPG/PNG, fallback download  
- `includes/components/audit-timeline.php` — merge `activity_logs` + compliance verify events for entity  

**List page updates:** Change “View” links from `download.php` to dedicated view pages; keep download button on view page.

**Files to modify:**

| File |
|------|
| `admin/documents.php` |
| `admin/passports.php` |
| `admin/visas.php` |
| `admin/transcripts.php` |
| `coordinator/documents.php` |
| `coordinator/passports.php` |
| `coordinator/visas.php` |
| `coordinator/transcripts.php` |
| `student/documents.php` (optional inline preview) |
| `download.php` (support inline disposition for iframe) |

**Database:** None.

---

### PHASE 4 — User Management Improvement

**Objective:** Multi-admin support, profiles, avatars, password flows.

**New pages:**

| Page | File |
|------|------|
| Admin list | `admin/admins.php` |
| Admin create/edit | `admin/admin-form.php` |
| Admin deactivate | `admin/admin-deactivate.php` (POST) |
| Admin password reset | `admin/admin-reset-password.php` (POST) |
| Admin profile | `admin/profile.php` |
| Admin change password | `admin/change-password.php` |
| Coordinator profile | `coordinator/profile.php` |
| Coordinator change password | `coordinator/change-password.php` |
| Student change password | `student/change-password.php` (split from profile) |

**Enhance:**

| File | Change |
|------|--------|
| `student/profile.php` | Avatar upload, link to change-password |
| `admin/student-form.php` | Avatar field (optional) |

**New helper:**

- `includes/enterprise/AdminUserHelper.php`  
- `includes/enterprise/ProfileHelper.php`  

**Nav updates:**

- `includes/nav-config.php` — System → Admin Users; Account → Profile  

**Database:** `002_enterprise_ui.sql` — `users.profile_picture`

---

### PHASE 5 — Coordinator Management Improvement

**Objective:** Better coordinator UX and self-service.

**Changes:**

- `admin/coordinator-form.php` — department `<select>` from `system_settings` JSON; university select with logo + country label  
- `coordinator/profile.php` — university, department, avatar, activity summary  
- `coordinator/change-password.php`  
- `coordinator/index.php` — activity overview widget (last 10 actions by user)  

**Optional new page:**

- `coordinator/activity.php` — personal activity feed (reuse `fetch_activity_feed` with `userId`)

**Database:** `system_settings` seed for departments (no new table).

**Files to modify:**

| File |
|------|
| `admin/coordinator-form.php` |
| `admin/coordinators.php` |
| `coordinator/index.php` |
| `includes/nav-config.php` |

---

### PHASE 6 — University Management Improvement

**Objective:** Visual university directory with media.

**Changes:**

- `admin/university-form.php` — logo + banner upload, country/city enhanced layout  
- `admin/universities.php` — card grid view toggle OR table with logo column  
- `uploads/universities/` storage  

**Display logo on:**

- Student course catalog (partner uni cards)  
- Application forms (host university)  

**Files to modify:**

| File |
|------|
| `admin/university-form.php` |
| `admin/universities.php` |
| `student/courses.php` |
| `student/application-form.php` |
| `includes/init.php` (logo upload helper) |

**Database:** `002_enterprise_ui.sql` — `logo_path`, `image_path`

---

### PHASE 7 — Activity Log Center

**Objective:** Enterprise audit center.

**Features on `admin/activity-logs.php`:**

- Serial `#` column  
- Filters: user (dropdown), action (dropdown), entity_type, date from/to  
- Pagination (server-side)  
- KPI strip: actions today, top users, top actions  
- Export CSV: `admin/reports/export-activity-logs.php`  
- Export PDF: `admin/reports/export-activity-pdf.php` (HTML template + Dompdf or browser print CSS)  

**New files:**

| File |
|------|
| `admin/reports/export-activity-logs.php` |
| `admin/reports/export-activity-pdf.php` |
| `includes/enterprise/ActivityLogQuery.php` |
| `includes/templates/audit-report-pdf.php` |

**Extend:**

| File |
|------|
| `includes/enterprise/ActivityLog.php` — `fetch_activity_logs_filtered()` |
| `admin/reports/index.php` — link to activity export |

**Database:** None.

---

### PHASE 8 — Professional Polish

**Objective:** Commercial product finish.

**Tasks:**

- Consistent breadcrumbs on all inner pages  
- Empty states with illustrations on every list  
- Toast notifications (upgrade flash beyond pulse)  
- Favicon + `logo-mark.svg`  
- Print styles for reports  
- Coordinator + student table polish (lighter pass)  
- Update `PRESENTATION_CHECKLIST.md`, demo accounts in `login.php`  
- QA: run `scripts/qa_smoke.php` after each phase  

**Files:** Touch all pages for `page-header`, empty states, favicon in `header.php`.

---

## 6. Complete File Modification Matrix

### New files (~35)

```
assets/css/enterprise.css
assets/js/enterprise.js
assets/js/data-table.js
assets/img/logo.svg
assets/img/logo-mark.svg
assets/img/favicon.svg
assets/img/illustrations/empty-applications.svg
assets/img/illustrations/empty-documents.svg
assets/img/illustrations/empty-analytics.svg
assets/img/illustrations/hero-mobility.svg
assets/img/illustrations/empty-audit.svg
includes/brand-logo.php
includes/components/data-table.php
includes/components/form-field.php
includes/components/page-header.php
includes/components/document-preview.php
includes/components/user-avatar.php
includes/components/audit-timeline.php
includes/enterprise/TableQuery.php
includes/enterprise/AnalyticsData.php
includes/enterprise/ExportPdf.php
includes/enterprise/ProfileHelper.php
includes/enterprise/AdminUserHelper.php
includes/enterprise/ActivityLogQuery.php
includes/widgets/analytics_charts_script.php
includes/templates/audit-report-pdf.php
database/migrations/002_enterprise_ui.sql
admin/document-view.php
admin/passport-view.php
admin/visa-view.php
admin/transcript-view.php
admin/admins.php
admin/admin-form.php
admin/admin-deactivate.php
admin/admin-reset-password.php
admin/profile.php
admin/change-password.php
admin/reports/export-activity-logs.php
admin/reports/export-activity-pdf.php
coordinator/profile.php
coordinator/change-password.php
coordinator/document-view.php
student/change-password.php
```

### Modified files (~45)

All files listed in Phases 1–8 above, plus:

```
config.php                          (AVATAR_DIR, UNIVERSITY_UPLOAD_DIR constants)
download.php                        (Content-Disposition inline for preview)
includes/init.php
includes/header.php
includes/layout.php
includes/footer.php
includes/components.php
includes/nav-config.php
includes/enterprise/ActivityLog.php
includes/enterprise/ChartData.php
includes/enterprise/ExportCsv.php
login.php
register.php
admin/index.php
admin/analytics.php
admin/applications.php
admin/documents.php
admin/passports.php
admin/visas.php
admin/transcripts.php
admin/students.php
admin/coordinators.php
admin/universities.php
admin/university-form.php
admin/courses.php
admin/enrollments.php
admin/activity-logs.php
admin/reports/index.php
admin/application-view.php
coordinator/index.php
coordinator/documents.php
coordinator/passports.php
coordinator/visas.php
coordinator/transcripts.php
coordinator/applications.php
coordinator/students.php
coordinator/coordinator-form.php (N/A - admin only)
student/index.php
student/profile.php
student/documents.php
student/courses.php
All widget files under includes/widgets/
```

---

## 7. UI Change Summary

| Element | Before | After |
|---------|--------|-------|
| Logo | “GE” div | SVG globe + cap, sidebar + login |
| Page background | `bg-slate-100` flat | Gradient mesh + subtle pattern |
| Cards | White border | Glass / soft shadow / hover lift |
| Sidebar | `bg-slate-950` flat | Glass dark + icons + transitions |
| Tables | Plain | Toolbar + S/N + pagination + filters |
| Empty states | One line text | Illustration + CTA |
| Analytics | Redirect | 6-panel analytics center |
| Documents | Download link | View page + PDF embed + timeline |
| Profiles | Student only | All roles + avatar |
| Universities | Table only | Logos + card option |
| Activity | 4-column table | Audit center + exports |
| Motion | Minimal | Fade-in, skeletons, button hover |

---

## 8. Implementation Order & Dependencies

```
Phase 1 (Design System) ──┬──► Phase 2 (Tables + Analytics)
                          ├──► Phase 3 (Document Views)
                          ├──► Phase 4 (Users + Profiles) ── requires 002 migration
                          ├──► Phase 5 (Coordinators)
                          ├──► Phase 6 (Universities) ── requires 002 migration
                          └──► Phase 8 (Polish) — ongoing

Phase 2 ──► Phase 7 (Activity center uses TableQuery from Phase 2)

Migration 002 ── run before Phase 4 & 6
```

**Recommended sprint breakdown:**

| Sprint | Phases | Est. effort |
|--------|--------|-------------|
| 1 | Phase 1 + migration 002 file (empty cols) | 1–2 days |
| 2 | Phase 2A (tables) | 1–2 days |
| 3 | Phase 2B (analytics) + Phase 3 | 1–2 days |
| 4 | Phase 4 + 5 | 1 day |
| 5 | Phase 6 + 7 | 1 day |
| 6 | Phase 8 + QA | 0.5 day |

---

## 9. Third-Party Additions (minimal)

| Library | Use | Delivery |
|---------|-----|----------|
| **Heroicons** | Nav/table icons | Inline SVG (no npm) |
| **Chart.js** | Already used | Keep CDN |
| **Dompdf** (optional) | PDF export Phase 7 | Single folder `vendor/dompdf` or HTML-print fallback |

No Composer required unless team prefers `composer require dompdf/dompdf`.

---

## 10. Testing Checklist (per phase)

- [ ] All 41 sidebar links load without PHP errors  
- [ ] `scripts/qa_smoke.php` passes  
- [ ] Role gates unchanged (student cannot access admin)  
- [ ] CSRF on all new POST forms  
- [ ] Upload size/type limits on avatar and university images  
- [ ] Pagination with 50+ demo students  
- [ ] PDF preview works for seed documents  
- [ ] Analytics charts render with DEMO_DATA / FIX_DEMO_DATA  
- [ ] Activity CSV/PDF export downloads  
- [ ] Cannot deactivate last active admin  

---

## 11. Out of Scope (explicit)

- REST API layer  
- Email/SMTP notifications  
- Multi-language i18n  
- Moving web root to `public/`  
- Replacing Tailwind CDN with build pipeline (optional later)  
- Mobile native apps  

---

## 12. Next Step

**Begin Phase 1 implementation:**

1. Create `assets/css/enterprise.css` + design tokens  
2. Create `assets/img/logo.svg` + `includes/brand-logo.php`  
3. Update `header.php`, `layout.php`, `login.php`  
4. Apply new card/button classes to dashboards  

No backend logic changes until Phase 2.

---

*This plan preserves the existing architecture and extends the portal incrementally for enterprise presentation impact.*
