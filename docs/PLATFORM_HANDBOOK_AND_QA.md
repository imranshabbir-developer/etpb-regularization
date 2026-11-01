# ETPB Regularization Platform — Handbook & QA Verification

**Scheme:** Regularization of Possession · Clause 3(ii) · Scheme 1977  
**Stack:** Laravel (PHP) + Blade + Vite/Tailwind + MySQL  
**Audience:** Demo to government officers · deployment readiness  
**Verified:** 1 November 2026 (Feature suite + role E2E smoke + live report exports)

---

## 1. What this platform is

A production-oriented case-management system for the Evacuee Trust Property Board (ETPB) to regularize **existing possession** of trust property as **tenancy** (not freehold allotment).

Citizens (or walk-in counters) file an application. Accounts unlocks it after confirming the Rs. 5,000 processing fee. The District Officer runs scrutiny → assessment → notice/objections → rent fixation → arrears. The Administrator approves. The District Officer executes the tenancy agreement and issues the regularization order. Chairman / Secretary / Auditor see live performance and registers; they are not the Clause 3(ii) deciding desk (Chairman alone may correct wrong entries).

---

## 2. How to run (local demo)

| Service | Command / URL |
|---------|----------------|
| MySQL | Database `etpb_regularization` (app) · `etpb_regularization_test` (PHPUnit) |
| App | `cd back-end && php artisan serve` → http://127.0.0.1:8000 |
| Assets | `npm run dev` (Vite) |
| Seed | `php artisan migrate:fresh --seed` |
| Verify seed | `php tools/verify-seed.php` |
| Report smoke | `php tools/smoke-reports.php` |
| Role E2E | `php tools/e2e-role-smoke.php` |
| Feature tests | `php artisan test tests/Feature` |

**Staff password (seed):** `Etpb@2026#Change`  
**Public demo applicant:** `imran.shabbir@example.com` / `Imran@Portal2026` (also `demo.applicant@example.com`)

---

## 3. Roles — who does what, where

| Role | Typical login | Home dashboard | Primary work | Reports |
|------|---------------|----------------|--------------|---------|
| **Applicant** | Public register / demo emails | Applicant home (my files + actions) | Apply wizard · evidence · biometric · fee · pay arrears | Own case deep report |
| **Dealing Assistant** | `da.lhr@etpb.gov.pk` | Officer home | One-window intake for walk-ins (`/apply`) | — |
| **Accounts Officer** | `accounts.lhr@etpb.gov.pk` | Officer home | Confirm fee (`/queues/scrutiny`) · post arrears receipts (`/queues/arrears`) | Fee register |
| **District Officer** | `do.lhr@etpb.gov.pk` | Officer home + queues | Scrutiny, assessment, notices, objections, rent, arrears, agreement/order | Case deep + registers |
| **Administrator** | `admin.lhr@etpb.gov.pk` | Officer home | Approval queue (`/approvals`) · may upload missing docs | District-scoped |
| **Legal Officer** | `legal.lhr@etpb.gov.pk` | Officer home | Litigation / sub judice (`/queues/litigation`) | Litigation register |
| **Chairman** | `chairman@etpb.gov.pk` | Executive home | Oversight · **`records.correct`** (only role that may correct entries) | Glimpse · Executive · Registers |
| **Secretary** | `secretary@etpb.gov.pk` | Executive home | **View-only** Board secretariat (no `records.correct`) | Same as Chairman (read) |
| **Auditor** | `audit@etpb.gov.pk` | Executive / audit | Audit trail (`/admin/audit`) | Executive |
| **System Admin** | `admin@etpb.gov.pk` | Admin | Users · settings · reference data · audit | All (ops) |

### Permission highlights (meeting policy)

- All **evidence document types are optional** — submission is not blocked by missing papers; Administrator / DO / DA / Accounts (where permitted) can attach later from the case file.
- **Biometric** is mandatory before submit.
- **Chairman only:** `records.correct`.
- **Secretary:** reports + view; cannot correct records or manage users/settings.

---

## 4. End-to-end data flow (happy path)

```
Applicant / DA
  │  create applicants + properties + property_areas + possession_details
  │  optional application_documents · mandatory biometric · fee_payments (PENDING)
  │  status: DRAFT → SUBMITTED
  ▼
Accounts Officer
  │  fee_payments.status → PAID · applications.payment_status → PAID
  │  unlocks processing
  ▼
District Officer
  │  scrutiny → site inspection → assessment_rounds / rate_inputs / comparables
  │  public_notices (15-day objection · 60-day assessment clock)
  │  objections / hearings / decisions
  │  assessment_decisions → rent_schedules → arrears_ledger
  │  payment_receipts / instalment_plans / remissions
  │  status → PENDING_ADMINISTRATOR_APPROVAL
  ▼
Administrator
  │  approvals row · reasons · ≤ 1 month SLA
  │  status → APPROVED (or REMANDED / REJECTED)
  ▼
District Officer
  │  nominees · tenancy_agreements · regularization_orders
  │  status → REGULARIZED
  ▼
Chairman / Secretary / Auditor
     live ReportDataService queries (not static files)
```

Side paths: **Returned (Deficient)** · **Rejected (Ineligible)** · **Sub Judice** (Legal) · **Remanded** · Chairman record correction.

Full stage table: `docs/APPLICATION_COMPLETE_CYCLE.md`.

---

## 5. Core table relationships

### Identity & RBAC

```
users ──< user_role >── roles ──< role_permission >── permissions
users ──< login_attempts
users.district_id → districts (officer scoping)
```

### Geography

```
provinces → divisions → districts → tehsils → mouzas
districts → offices
```

### Case spine

```
users (applicant account)
  └── applicants
        └── applications ──→ districts, offices, unit_conversion_profiles
              ├── possession_details          (eligibility / cut-off)
              ├── application_status_history
              ├── application_documents → document_types
              ├── fee_payments
              ├── assessment_rounds
              │     ├── assessment_rate_inputs → rate_sources
              │     ├── assessment_comparables
              │     └── assessment_decisions
              ├── rent_schedules
              ├── arrears_ledger
              ├── payment_receipts
              ├── instalment_plans → instalment_schedules
              ├── remissions
              ├── public_notices
              ├── objections → objection_decisions
              ├── hearings
              ├── litigations
              ├── occupant_offers
              ├── approvals
              ├── nominees → nominee_heirs
              ├── tenancy_agreements
              └── regularization_orders

applications.property_id → properties
  ├── property_areas → unit_conversion_profiles (+ factors: KANAL, MARLA, SARSAI, GHUNTA, …)
  └── property_geo_tags
```

### Reference / config

| Table | Purpose |
|-------|---------|
| `settings` | Fee amount, cut-off date, SLA days, etc. |
| `unit_conversion_profiles` | URBAN (default, 225 sq ft / marla) · RURAL · … |
| `unit_conversion_factors` | Includes **GHUNTA = 1089 sq ft** |
| `document_types` | Evidence catalogue (all `is_mandatory = false`) |
| `rate_sources` | FBR / DC / market / DO determination |
| `audit_logs` | Who changed what |
| `report_snapshots` | Optional persisted snapshots (UI exports are **live**) |

### Property / usage enums (current)

`OPEN_PLOT` · `RESIDENTIAL` · `COMMERCIAL` · `RESIDENTIAL_CUM_COMMERCIAL` · `OTHER`  
(Legacy HOUSE/SHOP/PLOT values were migrated.)

---

## 6. Dashboards (what each screen shows)

| View | Who | Data source |
|------|-----|-------------|
| `dashboard.applicant` | Applicant | Own `applications` + action prompts (draft, deficient, unpaid fee, arrears) |
| `dashboard.officer` | DO, Admin, DA, Accounts, Legal | Work queues counts from live status / fee / litigation queries |
| `dashboard.executive` | Chairman, Secretary, Auditor | `ReportDataService` headlines, performance, ageing |

Queues (live filters, not canned lists):

- `/queues/scrutiny` — fee.verify **or** applications.scrutinise  
- `/queues/assessment` · `/queues/objections` · `/queues/arrears` · `/queues/litigation`  
- `/approvals` — Administrator / Chairman approval queue  

---

## 7. Reports — always from live data

`ReportController` builds payloads via **`ReportDataService`** (SQL against current tables) and stamps `generatedAt` / `generatedBy` / a fresh `reference` (e.g. `ETPB/ROP/PERF/2026/…`).

| Report | Route | Formats |
|--------|-------|---------|
| Performance glimpse | `/reports/glimpse?format=pdf\|docx\|xlsx` | PDF · DOCX · XLSX |
| Executive / consolidated | `/reports/executive?format=…` | same |
| Statutory registers | `/reports/registers/{name}?format=…` | fee, applications, assessment, notices, arrears, litigation, hearings, agreements, objections, regularized |
| Case deep dive | `/applications/{id}/report?format=…` | same |

**Verification (1 Nov 2026):** `php tools/smoke-reports.php` generated **all** of the above from the seeded DB with non-zero byte sizes (e.g. glimpse.pdf ~47 KB, deep.pdf ~73 KB). Exports are regenerated on each download — not stale static files.

---

## 8. Intake rules (Nov 2026 meeting)

| Topic | Rule in system |
|-------|----------------|
| Indigent / widow / orphan flags | **Removed** from applicants |
| Property number | **Optional** |
| Property / usage types | New enum set (see §5) |
| Area | Total / Covered / Open · floors · khasra · geo tags |
| Default unit profile | **URBAN** (225) |
| Ghunta | Factor **1089** sq ft |
| Court | Court name + gist (≤ 100 words) when judicial basis used |
| Evidence | **All optional** + Possession certificate · Payment receipt · Assessment sheet types |
| Biometric | **Mandatory** before submit |
| Fee | Rs. 5,000 recorded now; payment gateway API later |
| Corrections | **Chairman only** |

Apply UX: slim ~8–10 essential fields, file dropzones, optional panels, promo modal (`maryam_nawaz_2.jpg`) on login/dashboard.

---

## 9. QA / verification results (this run)

### Automated Feature tests

```
php artisan test tests/Feature
→ 78 passed, 0 failed (239 assertions)
```

Coverage includes rent schedule generation, workflow guards (eligibility cut-off, fee unlock, notice windows, arrears/instalments, litigation blocks, nomination), and submission with **no mandatory documents**.

### Seed integrity

```
php tools/verify-seed.php
→ users=12 · applications=28 · roles=10 · permissions=58 · Seed verification OK
```

### Live report exports

```
php tools/smoke-reports.php
→ All glimpse / executive / register-* / deep PDF+DOCX+XLSX = OK
```

### Role HTTP E2E smoke

```
php tools/e2e-role-smoke.php
→ passed=64 failed=0
```

Checked per role: login · dashboard · role-specific queues/admin/reports · Secretary forbidden from admin users/settings · data integrity flags (no indigent column, URBAN default, GHUNTA, zero mandatory docs, Chairman `records.correct`, Secretary without it, Admin can upload, every application has current property area, property types valid).

### Fixes applied during this QA pass

1. **GHUNTA** migration inserts only when URBAN already exists (avoids fresh-migrate duplicate with seeder).  
2. **Unit profiles / factors / document_types** seeder uses upsert/update-or-insert.  
3. Migration skips inserting possession-certificate extras on empty `document_types` (seeder owns fresh catalogue).  
4. Tests & public seeder: `HOUSE` → `RESIDENTIAL`.  
5. Workflow test updated for optional-evidence policy.  
6. E2E smoke paths corrected to real routes (`/queues/*`, `/approvals`, `/admin/reference-data`).

---

## 10. Demo script (short)

1. Login as **Applicant** → Apply → property/area/geo → possession before 1 Jan 2010 → optional evidence → biometric → record fee → submit.  
2. Login as **Accounts** → Scrutiny queue → confirm deposit.  
3. Login as **District Officer** → open case → scrutinise → assessment → issue notice → (optional objection) → fix rent → regenerate arrears → forward for approval.  
4. Login as **Administrator** → Approvals → approve with reasons.  
5. Back as **DO** → Completion → nominee (if any) → agreement → order → Regularized.  
6. Login as **Chairman** → Executive dashboard → download Glimpse PDF/XLSX → open a register → confirm figures match the case just moved.  
7. Login as **Secretary** → same reports; confirm cannot open `/admin/users`.

---

## 11. Deploy checklist (1–2 day window)

- [ ] Production `.env` (APP_KEY, DB, `APP_URL`, mail, session secure)  
- [ ] `php artisan migrate --force` then seed **only** reference/roles (not demo applicants) on empty prod  
- [ ] `npm run build` · serve `public/build`  
- [ ] Storage link · writable `storage/` · queue worker if notifications enabled  
- [ ] Change all seed passwords · force password change on first login  
- [ ] HTTPS + reverse proxy · backup MySQL  
- [ ] Re-run `verify-seed` / smoke tools against staging with anonymized copy  
- [ ] Payment API integration (fee) when bank credentials available  

---

## 12. Related docs

| File | Content |
|------|---------|
| `docs/APPLICATION_COMPLETE_CYCLE.md` | Who acts next at each stage |
| `docs/IMPLEMENTATION_PLAN_NOV2026.md` | Meeting change plan |
| `docs/PLATFORM_END_TO_END_FLOW.md` | Click-by-click flow (if present) |
| `docs/DEMO_FLOW_GUIDE.md` | Short demo script (if present) |
| `MASTER_PLAN.md` | Broader product plan |

---

*Generated as part of the Nov 2026 production-readiness QA. Reports and dashboards are driven by live MySQL data through `ReportDataService` / workflow services — downloads always regenerate from the current database.*
