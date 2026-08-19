# Dashboard Guide, Roles, and Completion Audit

## Purpose

This document explains:

1. what the dashboard is doing right now,
2. what each user role can do,
3. what is complete vs partial vs pending against client requirements,
4. how to run a reliable client demo.

Project scope: **Regularization of Possession** under Scheme 1977, especially Clause 3(ii), Clause 10, Clause 11, Clause 12, Clause 13, Clause 21, and Clause 22.

---

## System Architecture (Current)

- **Frontend**: Blade views in `front-end/views`, Tailwind/CSS in `back-end/resources/css/app.css`.
- **Backend**: Laravel controllers/services in `back-end/app`.
- **Database**: MySQL schema via migrations in `back-end/database/migrations`.
- **Workflow core**: `WorkflowService` state machine controls legal/stage transitions.
- **Rule engines**:
  - `EligibilityService`: possession cutoff and arrears start basis.
  - `AreaConversionService`: Pakistani unit conversion to sqft.
  - `RentAssessmentService`: rent schedules with enhancement logic.
  - `ArrearsService`: ledger, receipts, installment/remission gating.

---

## What Dashboard Is Doing

The dashboard is intentionally **role-specific**:

- **Applicant dashboard** (`dashboard.applicant`):
  - shows only applicant's own cases,
  - highlights pending action (deposit pending, document deficiency, arrears due),
  - displays simplified progress across six major stages.

- **Officer dashboard** (`dashboard.officer`):
  - shows queue-oriented tiles (deposits, scrutiny, assessment, objections, approvals, arrears),
  - shows recent district work and caseload by status,
  - emphasizes statutory SLA clocks (60-day assessment and 1-month approval).

- **Executive dashboard** (`dashboard.executive`):
  - performance/aggregate reporting view for senior leadership.

Selection logic is in `DashboardController`.

---

## Role Matrix (Operational)

- **Applicant / Occupant**
  - files own application, uploads evidence, records fee details, tracks case status.

- **Dealing Assistant**
  - data entry for walk-ins, basic filing support, record management.

- **Accounts Officer**
  - verifies Rs. 5,000 deposit and posts arrears receipts.
  - separation of duties: cannot fix rent.

- **District Officer**
  - scrutiny, assessment inputs, notices, objections/hearings, rent fixation,
  - arrears and installment handling, completion documents.

- **Administrator**
  - statutory approval stage (with reasons), remand authority.

- **Chairman**
  - higher oversight powers, remission approvals, broader report visibility.

- **Legal Officer**
  - litigation/sub-judice tracking and legal status maintenance.

- **Auditor**
  - read-only cross-system visibility including audit logs.

- **System Admin**
  - technical/admin setup (non-statutory office role).

Role definitions and permission boundaries are seeded in `RolePermissionSeeder`.

---

## Client Requirement Coverage Audit

Status legend:
- **Complete**: implemented in DB + backend + UI flow.
- **Partial**: present but needs enhancement/clarification.
- **Pending**: not yet fully represented in working screens/workflow.

### 1) Applicant Information and Property Intake

- Applicant identity fields (name, parentage, CNIC, contact, address): **Complete**
- Property number/sub-unit and geography (mouza/city/tehsil/district/province): **Complete**
- Area conversion to sqft using Pakistani units: **Complete**
- Possession date cutoff (pre-01-01-2010): **Complete**

### 2) Evidence of Possession (Certified Copy List)

- Document upload/verification framework and mandatory document handling: **Complete**
- Named evidence categories (Jamabandi, Mutation, Khasra, GEO tag, utility bills, affidavit, court order, etc.): **Complete** at framework level (via document types/master data), final categorization should be verified in seeded masters.

### 3) Rent Assessment and Clause 10 Due Process

- Rate inputs (FBR/DC/valuator/market comparables): **Complete**
- Public notices, objections, hearings with deadlines: **Complete**
- Reasoned DO determination and rent fixation: **Complete**
- Year-wise rent schedule and milestone reporting years: **Complete**

### 4) Occupant Offers + Litigation (Head 4)

- Illegal occupant offer capture tabularly: **Complete**
- Court pending/restraining order/direction case tracking: **Complete**
- Workflow lock for sub-judice constraints: **Complete**

### 5) Rs. 5,000 Deposit and Processing Gate

- Deposit details (instrument/bank/branch/date/depositor CNIC/contact): **Complete**
- Payment status gate (cannot process while pending): **Complete**
- Accounts verification flow: **Complete**

### 6) Arrears, Installments, Remission

- Arrears ledger generation: **Complete**
- Receipt posting and balance tracking: **Complete**
- Installments (max 24) and approvals: **Complete**
- Remission model (Clause 12 grounds): **Complete**

### 7) Approval, Nominee, Agreement, Regularization Order

- Administrator approval with reasons and SLA: **Complete**
- Nomination record and heirs: **Complete**
- Tenancy agreement + regularization order records: **Complete**

### 8) Reporting and Executive Visibility

- Glimpse, executive, deep case reports, and registers: **Complete**
- Export options and report snapshots: **Complete**

### 9) Enforcement / Chapter VII Support

- Penalties and ejectment proceeding tables/workflow base: **Partial**
  - schema exists, but day-to-day operational screens are less prominent than core regularization flow.

### 10) Legal/Policy Decisions Still Business-Dependent

- 8% enhancement interpretation (simple vs compound): **Configurable but policy-dependent**
- Marla standard (272.25 vs 225 sqft): **Configurable but district-policy-dependent**

---

## Frontend and Responsiveness (Current + Updated)

Current behavior:
- mobile sidebar drawer,
- responsive grids,
- horizontal table scrolling,
- touch-friendly controls.

Recent UI direction for client demo:
- fixed **light theme** for consistent presentation,
- green sidebar + white/light content emphasis (Pakistan flag style),
- improved small-device stacking for card actions/buttons.

---

## Recommended Demo Script (Client Meeting)

1. Login as **Applicant** and create one application quickly.
2. Show required steps: applicant -> property -> possession -> evidence -> occupants/court -> deposit.
3. Login as **Accounts Officer** and verify deposit.
4. Login as **District Officer**:
   - show scrutiny queue,
   - open assessment with rates/comparables,
   - issue notice, show objections/hearing path,
   - fix rent and generate arrears.
5. Login as **Administrator** and approve with reasons.
6. Show completion records (nominee/agreement/order).
7. Login as **Executive/Chairman** and show consolidated dashboard/reports.

---

## “Do Not Break Logic” Safety Notes

- Dashboard/UI changes should stay in views/CSS only.
- Workflow rules remain in services and should not be bypassed.
- Keep payment gate, sub-judice checks, and approval-reasons checks intact.
- Keep role permissions as seeded unless policy change is approved.

---

## Final Confidence Summary

- **Core regularization lifecycle**: strong and largely complete.
- **Frontend/Backend/DB alignment**: good, with legal mapping explicitly embedded.
- **For client demo**: system is demo-ready with role-based walkthrough.
- **For production go-live**: validate final policy toggles and enforcement-screen depth.

---

## Latest Operation Verification

Verification run completed from application code:

- Laravel route inventory confirms active modules are wired (**95 routes**).
- End-to-end backend regression test suite is green (**78 passed**).
- Dashboard shell updated to remove flag visuals and keep a clean light interface.
- Additional small-screen behavior updates applied for easier use on phones.
