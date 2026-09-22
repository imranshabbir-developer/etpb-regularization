# ETPB Regularization of Possession — Complete Demo & Operations Guide

**Portal:** Regularization of Possession (Urban Evacuee Trust Properties)  
**Organisation:** Evacuee Trust Property Board (ETPB)  
**Legal basis:** Clause 3(ii) of the *Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977*  
**Audience:** Chairman, Secretary to the Board, Administrators, District Officers, Accounts, Legal, and demonstration facilitators  
**Date prepared:** 22 September 2026  

This document is the single walkthrough for tomorrow’s demo and for explaining the platform to senior officers. It covers what the system is, how a member of the public files from zero, what each officer does next, how accounts share the **same live database**, and what reports each role can generate (PDF / MS Word / Excel).

---

## 1. Project overview — what this is

### 1.1 Purpose

The portal lets an **existing occupant** of an urban evacuee trust property apply to be treated as a **tenant** (regularization of possession), subject to:

- possession **prior to 1 January 2010** (Clause 3(ii)(a));
- clearance of **arrears** assessed by the District Officer (Clause 3(ii)(b));
- **documentary evidence** or a court order (Clause 3(ii)(c));
- **Administrator approval within one month**, with reasons (Clause 3(ii)(d));
- rent fixed under **Clause 10**, with 8% annual enhancement under **Clause 11**.

**Important clarification for the room:** this is **regularization of possession as tenancy under the Scheme**, not freehold “allotment of ownership” of the plot. After successful completion the occupant is recorded as a **tenant**, a **tenancy agreement** is executed, and a **regularization order** is issued. The Board continues to hold the property in trust.

### 1.2 What “one system” means

There is **one MySQL database**. Every login sees the **same case**, scoped by role:

| Who | What they see of a case |
|---|---|
| Applicant | Only their own applications |
| District Officer / Accounts / Legal / Dealing Assistant | Their district (Lahore in the demo seed) |
| Administrator / Chairman / Secretary / Auditor / System Admin | All districts |

When Accounts confirms the Rs. 5,000 deposit, the District Officer immediately sees the case as paid. When the DO fixes rent, arrears appear for Accounts and for the applicant. When the Administrator approves, the applicant’s progress bar advances. **There is no separate “mock” data per role.**

### 1.3 How to open the portal for the demo

```bash
cd back-end
php artisan serve --port=8000
```

Open **http://127.0.0.1:8000**  
(Or Apache on **http://localhost:8080** if configured.)

Fresh laptop / clean reload:

```bash
php artisan migrate:fresh --seed --force
npm run build
```

---

## 2. Login accounts (commissioning)

**Officer password (all officers below):** `Etpb@2026#Change`

| Email | Role | Primary interest in the demo |
|---|---|---|
| `chairman@etpb.gov.pk` | Chairman, ETPB | Executive dashboard, SLA, **At a glance** + consolidated reports |
| `secretary@etpb.gov.pk` | Secretary to the Board | Same reporting suite (read-only oversight) |
| `admin.lhr@etpb.gov.pk` | Administrator (Lahore) | **Pending approval** queue + reports |
| `do.lhr@etpb.gov.pk` | District Officer (Lahore) | Scrutiny, assessment, notices, objections, rent fixation |
| `accounts.lhr@etpb.gov.pk` | Accounts Officer | Confirm Rs. 5,000; post arrears receipts |
| `da.lhr@etpb.gov.pk` | Dealing Assistant | File walk-in applications |
| `legal.lhr@etpb.gov.pk` | Legal Officer | Litigation / stay orders |
| `audit@etpb.gov.pk` | Auditor | Read-only everything + audit |
| `admin@etpb.gov.pk` | System Administrator | Users, masters, statutory settings |

**Public applicants**

| Email | Password | What you will see |
|---|---|---|
| `imran.shabbir@example.com` | `Imran@Portal2026` | Fresh applicant — start filing from zero |
| `demo.applicant@example.com` | `Demo#Portal2026` | Already **regularized** case (end state) |
| `sohan.lal@example.com` | `Sohan#Portal2026` | Two **drafts** in progress |

Full list also lives in `ACCOUNTS.md`.

---

## 3. End-to-end journey — from zero to regularization

This is the complete path of **one fresh application**. Use it to narrate the demo.

```
REGISTER / SIGN IN (Applicant)
        │
        ▼
STEP 1  About you (particulars)
        │
        ▼
STEP 2  Property & area (Kanal / Marla → sqft)
        │
        ▼
STEP 3  Date & nature of possession  ──► if after cut-off → refused (ineligible)
        │
        ▼
STEP 4  Documentary evidence (mandatory papers)
        │
        ▼
STEP 5  Other occupants / court cases (if any)
        │
        ▼
STEP 6  Rs. 5,000 deposit details  →  Draft / Submitted (payment PENDING)
        │
        ▼
ACCOUNTS verifies instrument with bank  →  payment PAID
        │
        ▼
DISTRICT OFFICER — scrutiny / site inspection
        │
        ├─ deficient ──────────► return to applicant ──► re-submit
        ├─ ineligible ─────────► Rejected (Ineligible)  [END]
        └─ OK
                │
                ▼
        Assessment proposed (FBR / DC / valuator / comparables)
                │
                ▼
        Public + tenant notice issued
                │   (15-day objection window + 60-day assessment clock start)
                ▼
        Objections (if any) → hearing → decide
                │
                ▼
        Rent FIXED (written reasons) → year-by-year schedule + arrears ledger
                │
                ▼
        Arrears cleared / instalments (≤24) / remission (Chairman, Cl. 12)
                │
                ▼
        PENDING ADMINISTRATOR APPROVAL  (one-month SLA)
                │
                ├─ Remand ──► back to assessment
                ├─ Reject ──► Rejected [END]
                └─ Approve (with reasons)
                        │
                        ▼
                Nominee form (if required) → Tenancy agreement executed
                        │
                        ▼
                Regularization order issued → status REGULARIZED  [END]
```

Sub-judice path: if court stay / pending suit is recorded, the case can move to **Sub Judice**; processing is constrained until Legal clears the flag.

---

## 4. Applicant — filing an application (complete steps)

**Demo account for “from zero”:** `imran.shabbir@example.com` / `Imran@Portal2026`  
Or register a new citizen at `/register`.

### 4.1 Before they start — what they need

1. CNIC  
2. Papers of possession / revenue record (e.g. Jamabandi / mutation / court order)  
3. Property location details  
4. **Rs. 5,000** pay order / banker’s cheque / demand draft in favour of **Chairman ETPB**

### 4.2 Step-by-step on screen

| Step | Screen | What the applicant enters | System behaviour |
|---|---|---|---|
| 0 | Register / Sign in | Name, email, password, CNIC | Creates public account (`APPLICANT` role) |
| 1 | **About you** | Full name, parentage, CNIC, contact, postal address | Saved to `applicants`; offered back on later visits |
| 2 | **Property** | Property no., type (house/shop), address, district, area in Kanal/Marla/Sarsai | Live conversion to **square feet** with frozen conversion trace |
| 3 | **Possession** | Date of possession, nature (self / inherited / purchased) | Eligibility test vs cut-off **31-12-2009**; computes arrears-from date |
| 4 | **Evidence** | Upload mandatory document types | Files stored outside web root; verification status starts PENDING |
| 5 | **Occupants / court** | Other occupants’ offers; litigation / stay if any | Can flag sub-judice later for Legal |
| 6 | **Deposit** | Instrument type, number, bank, branch, amount Rs. 5,000 | Fee row created; application stays **payment PENDING** |
| Done | Confirmation | Application number shown | Case is visible to district officers once submitted |

**Narration tip:** On step 3, try a possession date in **2011** — the system refuses it, citing Clause 3(ii)(a). That single moment usually earns appreciation from the Board.

### 4.3 What the applicant sees after filing

- Home dashboard: **actions waiting on them** (finish draft, fix deficiency, unpaid deposit, arrears due)  
- Progress strip in plain language (Filed → Deposit → Examined → Rent → Approval → Regularized)  
- They **cannot** see officer rate inputs or internal reasoning  
- They **can** see status, fee status, and arrears they must clear  

---

## 5. What happens after filing — role by role

One shared case number (example pattern): `ETPB/PB-LAHORE/ROP/2026/00xx`

### 5.1 Accounts Officer — `accounts.lhr@etpb.gov.pk`

**Job:** Confirm the Rs. 5,000 with the bank; later post arrears receipts.

1. Sign in → Dashboard tile **Deposits to confirm**  
2. Open the application → **Fee**  
3. Verify instrument → status becomes **PAID**  
4. **Until this happens, no processing step is allowed.** The portal blocks scrutiny/assessment and explains why.

**Sync:** The District Officer’s queues update immediately; the applicant’s progress bar advances.

### 5.2 Dealing Assistant — `da.lhr@etpb.gov.pk`

**Job:** File for walk-in citizens who cannot use the portal.

- Uses the **same six steps** under “File for a walk-in”  
- Creates applications in the district  
- Does **not** confirm fee (Accounts) and does **not** fix rent (DO)

### 5.3 District Officer — `do.lhr@etpb.gov.pk`

**Job:** The statutory desk under Clause 10 — scrutiny, assessment, notices, objections, rent fixation, arrears, agreement.

Typical sequence on a **PAID** case:

1. **Scrutiny** queue → examine papers  
   - Return deficient → applicant must fix and re-submit  
   - Reject ineligible → closed  
   - Proceed → site inspection / assessment path  
2. **Rent assessment** → record FBR / DC / valuator rates + nearby comparables → propose rent (live projection)  
3. **Notices & objections** → issue public notice → **15-day objection window** and **60-day assessment SLA** start  
4. Record / decide objections (hearing if needed)  
5. **Fix rent with written reasons** → system builds:  
   - year-by-year rent schedule (8% enhancement)  
   - arrears ledger from the statutory start date  
6. Ensure arrears path is clear (full payment / instalments ≤ 24)  
7. Move toward **Pending Administrator Approval**  
8. After approval: execute **tenancy agreement** and issue **regularization order**

**Dashboard charts:** doughnut of caseload by stage (live counts for their district).

### 5.4 Legal Officer — `legal.lhr@etpb.gov.pk`

**Job:** Litigation register, restraining orders, direction cases.

- Marks / maintains **sub judice**  
- Appears on executive dashboards and the litigation register  
- Coordinates with DO when a stay blocks processing  

### 5.5 Administrator — `admin.lhr@etpb.gov.pk`

**Job:** Clause 3(ii)(d) — approve within **one month**, with reasons; may remand.

1. Lands on **officer work dashboard** (approval-first)  
2. Open **Pending approval**  
3. Approve / Reject / Remand  
4. Overdue cases show on their tile and on Chairman/Secretary breach lists  

Also has full **executive reports** (At a glance, Consolidated, Registers, Deep).

### 5.6 Chairman — `chairman@etpb.gov.pk`

**Job:** Scheme-wide oversight; extend assessment SLA; remission under Clause 12; cancel fraudulent tenancy (Cl. 23); consume performance reports.

- Executive dashboard with **charts**: intake vs regularisation, stage mix, district bars, arrears ageing  
- Reports → At a glance / Consolidated / Registers / Deep case  

### 5.7 Secretary to the Board — `secretary@etpb.gov.pk`

**Job:** Board Secretariat reporting for Minister / Chairman / senior government.

- Same **executive dashboard and report suite** as Chairman (read-only oversight; not a deciding office under Clause 3(ii))  
- Ideal login when the Secretary is present in the meeting  

### 5.8 Auditor — `audit@etpb.gov.pk`

Read-only across cases + audit log. Same reporting visibility as senior exec for verification.

### 5.9 System Administrator — `admin@etpb.gov.pk`

Users & roles, reference/geography masters, statutory settings (`settings` table), audit. Not a statutory deciding office.

---

## 6. How roles relate to each other (same case)

```
                    ┌─────────────┐
                    │  Applicant  │  files & tracks own case
                    └──────┬──────┘
                           │ creates application (PENDING pay)
                           ▼
                    ┌─────────────┐
                    │  Accounts   │  confirms Rs. 5,000 → PAID
                    └──────┬──────┘
                           │ unlocks processing
                           ▼
              ┌────────────────────────┐
              │   District Officer     │  scrutiny → assess → notice → rent → arrears
              └───────────┬────────────┘
                    ┌─────┴─────┐
                    ▼           ▼
             ┌──────────┐  ┌─────────┐
             │  Legal   │  │Accounts │  stays / litigation     arrears receipts
             └──────────┘  └─────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │Administrator │  approve / remand / reject (1 month)
                    └──────┬───────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ DO again     │  agreement + regularization order
                    └──────┬───────┘
                           │
                           ▼
                    REGULARIZED  (tenant on record)
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
         Chairman     Secretary      Auditor
         (oversight & reports — live aggregates of the same cases)
```

**Separation of duties enforced in permissions:**

- Fee verifier ≠ rent fixer  
- Assessor proposes; Administrator approves  
- No hard deletes — soft delete + history  

---

## 7. Reports — what exists and who can generate them

Every report screen offers **PDF**, **MS Word**, and **Excel**, laid out as official Board correspondence (letterhead, reference, SUBJECT, signature block, distribution list, page numbers).

### 7.1 Report catalogue

| Report | Route | Contents | Best for |
|---|---|---|---|
| **At a glance** | Reports → At a glance | One-page performance: volumes, recovery, SLA on-time %, district summary, monthly intake | Chairman / Secretary briefing |
| **Consolidated / master** | Reports → Consolidated report | Full picture: headlines, **named SLA breaches**, district league, stages, objections, **arrears ageing**, fee by instrument, litigation, intake & disposal trends | Higher authorities / Board pack |
| **Deep (single case)** | Open any case → Deep report | Applicant, property, area trace, geo, evidence list, rates, schedule, ledger, objections, litigation, fee, approvals, history | Case file for Administrator / DO / court |
| **Registers** | Reports → Registers | Operational lists (filterable) | Day-to-day offices |

**Register types**

1. Application register  
2. Fee register  
3. Arrears outstanding statement  
4. Objection register  
5. Sub judice / litigation register  
6. Regularization register  
7. Rent assessment register  
8. Notice & service register  
9. Hearing cause list  
10. Tenancy agreement register  

### 7.2 Who can create which reports

| Role | At a glance | Consolidated | Deep case | Registers | Export PDF/Word/Excel |
|---|---|---|---|---|---|
| Chairman | Yes | Yes | Yes | Yes | Yes |
| Secretary | Yes | Yes | Yes | Yes | Yes |
| Administrator | Yes | Yes | Yes | Yes | Yes |
| Auditor | Yes | Yes | Yes | Yes | Yes |
| District Officer | — | — | Yes | Yes | Yes |
| Accounts | — | — | — | Yes | Yes |
| Legal | — | — | — | Yes | Yes |
| Dealing Assistant | — | — | — | Yes | — |
| Applicant | — | — | — | — | — |
| System Admin | Yes (via full perms) | Yes | Yes | Yes | Yes |

**How to generate:** open the report → choose district (optional on executive reports) → click **PDF** / **MS Word** / **Excel** / **Print**.

---

## 8. Dashboards — what each home screen shows

| Login | Home screen | Charts / visuals | Purpose |
|---|---|---|---|
| Applicant | Own cases + action alerts + progress steps | Progress strip (not statistical charts) | “What do I do next?” |
| DO / Accounts / Legal / DA / Administrator | Work-queue tiles + recent cases | Doughnut: applications by stage | “What work is waiting?” |
| Chairman / Secretary / Auditor | Executive overview | Line: intake vs regularisation; Doughnut: stages; Bars: districts & arrears ageing; SLA bars | “How is the scheme performing?” |

All figures are **computed from live tables** (`applications`, `fee_payments`, `arrears_*`, `objections`, `litigations`, etc.), not hardcoded mock numbers.

---

## 9. Suggested live demo script (≈ 10–12 minutes)

1. **Imran (applicant)** — start application; show area conversion; show 2011 possession refusal; record deposit → PENDING.  
2. **Accounts** — confirm deposit → PAID; show the same case now unlocked.  
3. **District Officer** — open case; show assessment / notice concept; open a seeded regularized or objection case for richness.  
4. **Administrator** — Pending approval tile (including any overdue).  
5. **Chairman or Secretary** — executive charts; **At a glance → PDF**; **Consolidated → Excel**; open **Deep report** on a regularized case.  
6. **demo.applicant** — citizen view of a finished regularized case.  

Seeded highlights already in the database for the meeting:

- Cases across multiple districts and workflow stages  
- At least one **assessment SLA breach** and one **approval SLA breach** (named on consolidated report)  
- Sub-judice example  
- Regularized cases with rent, arrears cleared, agreement & order  

---

## 10. Codal / statutory clocks (say these aloud)

| Clock | Rule | Clause |
|---|---|---|
| Possession cut-off | Prior to 1 Jan 2010 | 3(ii)(a) |
| Arrears from | 1 Jul 2000, or occupation, or judicial verdict — whichever earlier | 3(ii)(b) |
| Objection window | 15 days from service | 10(i)(c) |
| Assessment completion | 60 days from first notice (extendable by Chairman) | 10(i)(e) |
| Administrator decision | One month, with reasons | 3(ii)(d) |
| Enhancement | 8% per annum | 11(ii) |
| Instalments | Maximum 24 | 13 |
| Processing fee | Rs. 5,000 in favour of Chairman ETPB | Board requirement |

---

## 11. What “allotment” means in this portal

When officers ask “when is the plot allotted?” answer carefully:

1. The Scheme regularizes **possession as tenancy**, it does not convey freehold title through this workflow.  
2. Successful end state = **REGULARIZED**:  
   - rent fixed;  
   - arrears cleared (or lawfully remitted / on instalments as allowed);  
   - Administrator approval;  
   - **tenancy agreement** executed;  
   - **regularization order** issued.  
3. Disposal without regularization = **Rejected** or **Rejected (Ineligible)** (with recorded reasons).  
4. Stayed matters remain **Sub Judice** until Legal updates the record.

---

## 12. Technical notes for facilitators

- Stack: Laravel (PHP) + Blade UI + MySQL + Vite/Tailwind + Chart.js (bundled, works offline after `npm run build`)  
- Views: `front-end/views` · App: `back-end` · Schema: migrations · Demo data: seeders  
- One command reload: `php artisan migrate:fresh --seed --force`  
- Accounts & passwords: `ACCOUNTS.md`  
- Design rationale: `MASTER_PLAN.md`  

---

## 13. Closing line for the Board

> This portal takes an occupant from a simple online application through fee verification, District Officer assessment under Clause 10, public notice and objection, Administrator approval under Clause 3(ii)(d), and finally to a tenancy agreement and regularization order — with every statutory clock, rupee figure and report drawn from the same live database the officers themselves use.

---

*End of guide. For login credentials see `ACCOUNTS.md`. For running the stack see `README.md` / `RUNNING.md`.*
