# ETPB Regularization of Possession Portal  
## Complete End-to-End Platform Flow Guide

**Document type:** Full operational & demonstration guide  
**Organisation:** Evacuee Trust Property Board (ETPB)  
**Scheme:** Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977  
**Primary clause:** Clause 3(ii) — Regularization of Possession  
**Portal URL (local):** http://127.0.0.1:8000 or http://localhost:8080  
**Last updated:** 22 September 2026  

This document explains **everything** a senior officer, facilitator, or new user needs to know:

1. What the platform is  
2. How a member of the public files an application (every screen, every click)  
3. Where that application then appears in **each role’s dashboard**  
4. What **each role** can do with that application, step by step, until final disposal  
5. How reports work for each account  
6. How all accounts share the **same live database**  

Related shorter files: `START_HERE.md` (clone setup), `ACCOUNTS.md` (passwords), `docs/DEMO_FLOW_GUIDE.md` (demo script). **This file is the detailed master.**

---

# PART A — PROJECT OVERVIEW

## A1. What problem does this platform solve?

Urban evacuee trust properties are held by the Board in trust. Many are occupied by people who have lived there for years but whose possession was never regularized. Under **Clause 3(ii)** of the 1977 Scheme, an existing occupant may be treated as a **tenant** if they meet the statutory conditions.

The portal digitizes that entire lifecycle:

- citizen applies online (or a Dealing Assistant files for a walk-in);  
- Accounts confirms the Rs. 5,000 processing fee;  
- District Officer scrutinises, assesses rent, issues notice, hears objections;  
- arrears are computed and cleared;  
- Administrator approves within one month with reasons;  
- tenancy agreement and regularization order are issued;  
- Chairman / Secretary / Auditor see live performance and can export government-standard reports (PDF, Word, Excel).

## A2. Important legal clarification (say this in meetings)

**This portal does not allot freehold ownership of the plot.**  

The successful end state is **REGULARIZED**:

- the occupant is recorded as a **tenant**;  
- rent is fixed under Clause 10;  
- a **tenancy agreement** is executed;  
- a **regularization order** is issued;  
- the Board continues to hold the property in trust.

If officers ask “when is the plot allotted?”, answer: *possession is regularized as tenancy under Clause 3(ii), not transferred as freehold title through this workflow.*

## A3. Statutory conditions (Clause 3(ii))

| Condition | Rule | Clause |
|---|---|---|
| Possession cut-off | Actual physical possession **prior to 1 January 2010** | 3(ii)(a) |
| Arrears | Clear all arrears assessed by the District Officer from **1 July 2000**, or date of occupation, or judicial verdict — **whichever is earlier** | 3(ii)(b) |
| Evidence | Documentary evidence or court order | 3(ii)(c) |
| Approval | Administrator approves **within one month**, recording reasons | 3(ii)(d) |
| Rent | Fixed under **paragraph 10** | 3(ii)(b), 10 |
| Enhancement | **8% per annum** | 11(ii) |
| Objection window | **15 days** from service of notice | 10(i)(c) |
| Assessment SLA | **60 days** from first notice (extendable by Chairman) | 10(i)(e) |
| Instalments | Maximum **24** | 13 |
| Processing fee | **Rs. 5,000** pay order / DD / banker’s cheque in favour of **Chairman ETPB** | Board requirement |

## A4. One database — how accounts “sync”

There is **one MySQL database**. There is **no separate mock dataset per role**.

| When this happens… | These people see it immediately… |
|---|---|
| Applicant submits application | District offices (after fee path), Dealing Assistant lists |
| Accounts marks fee **PAID** | DO scrutiny unlocks; applicant progress bar advances; Chairman counts update |
| DO fixes rent & generates arrears | Applicant sees arrears due; Accounts can post receipts; Admin later sees clearance |
| Administrator approves | Applicant progress advances; Completion section unlocks for agreement/order |
| Legal marks sub judice | Assessment path blocked; executive litigation counts update |

**Visibility rules**

| Role | Scope |
|---|---|
| Applicant | Only applications linked to their user account |
| DO / Accounts / Legal / DA | Their **district** (demo seed: Lahore) |
| Administrator / Chairman / Secretary / Auditor / System Admin | **All districts** |

## A5. Login accounts for the live system

**Officer password (all officers):** `Etpb@2026#Change`

| Email | Role |
|---|---|
| `chairman@etpb.gov.pk` | Chairman, ETPB |
| `secretary@etpb.gov.pk` | Secretary to the Board |
| `admin.lhr@etpb.gov.pk` | Administrator (Lahore) |
| `do.lhr@etpb.gov.pk` | District Officer (Lahore) |
| `accounts.lhr@etpb.gov.pk` | Accounts Officer |
| `da.lhr@etpb.gov.pk` | Dealing Assistant |
| `legal.lhr@etpb.gov.pk` | Legal Officer |
| `audit@etpb.gov.pk` | Auditor (read-only) |
| `admin@etpb.gov.pk` | System Administrator |

**Public applicants**

| Email | Password | Purpose |
|---|---|---|
| `imran.shabbir@example.com` | `Imran@Portal2026` | File a **new** application from zero |
| `demo.applicant@example.com` | `Demo#Portal2026` | See a finished **REGULARIZED** case |
| `sohan.lal@example.com` | `Sohan#Portal2026` | See **draft** applications in progress |

Anyone can also create an account at `/register`.

---

# PART B — HOW AN APPLICANT FILES (EVERY CLICK)

Use account: `imran.shabbir@example.com` / `Imran@Portal2026`  
Or register a new citizen at **Sign in → Register**.

## B1. What the applicant needs before starting

1. CNIC (13 digits)  
2. Property number / address / district  
3. Approximate area (Kanal / Marla / Sarsai or sqft)  
4. Papers of possession (Jamabandi, mutation, etc. — list in Part B4)  
5. Rs. **5,000** instrument in favour of **Chairman ETPB**  
6. Optional: geo-coordinates (phone can fill “Use my location”)

## B2. Sign in and open Apply

1. Open the portal.  
2. Click **Sign in**.  
3. Enter email and password → dashboard **Home**.  
4. Left sidebar (applicant):  
   - **Home**  
   - **Apply**  
   - **My applications**  
5. Click **Apply**.

Landing page title: **Apply to have your possession regularized**

You will see cards: *Before you start*, *You can apply if*, *What you will need*, *How long it takes*.

Click **Start the application**.

## B3. The six-step wizard (exact screens)

The top of the wizard shows steps:

1. About you  
2. The property  
3. Your possession  
4. Evidence  
5. Others and courts  
6. Rs. 5,000 deposit  

### STEP 1 — About you  
**URL:** `/apply/about-you`  
**Button:** **Continue**

| Field on screen | What to enter |
|---|---|
| Your full name | Applicant’s name |
| You are the | *son or daughter of* / *wife of* |
| Their name | Father / husband name |
| CNIC | 13 digits |
| Mobile number | Contact |
| Email | Optional |
| Your postal address | Postal address |
| District you live in | Optional dropdown |
| Indigent / Widow / Orphan | Checkboxes if applicable |

System saves particulars to the applicant profile (offered back on later visits).

---

### STEP 2 — The property  
**URL:** `/apply/property`  
**Button:** **Continue**

| Field on screen | What to enter |
|---|---|
| Property number | Official property no. |
| Sub-unit number | Optional |
| What kind of property? | House / Shop / Building / Plot / Agri land / Other |
| How is it used? | Residential / Commercial / Mixed / Other |
| Full address | Address of the property |
| District | **Required** (jurisdiction) |
| Tehsil / Mouza / City | As known |
| Khewat / Khatooni / Khasra | Revenue identifiers if known |
| Latitude / Longitude | Optional; button **Use my location** |
| Measurement standard | Revenue (272.25 sqft Marla) or Urban (225) |
| How to enter area | Single unit **or** compound Kanal/Marla/Sarsai |
| Covered area (sqft) | Optional |

**Live conversion:** as area is entered, the system shows square feet with a frozen conversion trace (so rent later is defensible).

---

### STEP 3 — Your possession  
**URL:** `/apply/possession`  
**Button:** **Save and continue**

| Field on screen | What to enter |
|---|---|
| When did your possession begin? | Date ≤ today |
| How did you come to hold it? | Self / Inherited / Purchased / Allotted / Other |
| Tell us briefly how it happened | Optional narrative |
| Date of judgment / Court & case | If relying on court order |
| Declaration checkbox | **Must be accepted** |

**Critical demo moment:** enter a date in **2011** → system refuses (Clause 3(ii)(a) cut-off). Enter a date before **1 Jan 2010** → accepted.

On success the system:

- creates the **Application** record (status **DRAFT**);  
- assigns application number pattern `ETPB/{DISTRICT}/ROP/{YEAR}/{SEQ}`;  
- clears the temporary wizard session;  
- opens **Evidence**.

---

### STEP 4 — Evidence (documents)  
**URL:** `/apply/{application}/evidence`  
**Upload button** then **Continue**

Upload form fields:

- Which document (type)  
- Choose the file (PDF / JPEG / PNG / TIFF)  
- Certified copy?  
- Reference number / Date on document / Who issued it  

#### Mandatory document types (must be on file before submit)

| Code | Document |
|---|---|
| JAMABANDI | Jamabandi (Record of Rights) |
| MUTATION | Mutation (Intiqal) |
| KHASRA_GIRDAWARI | Khasra Girdawari |
| GEO_TAG | GEO Tagging (coordinates) |
| LOCATION_PLAN | Location / Site Plan |
| BILL_ELECTRICITY | Electricity Bill |
| AFFIDAVIT_POSSESSION | Affidavit (possession & nominee) |
| CNIC_COPY | CNIC Copy |
| NOMINATION_FORM | Nomination Form |
| FEE_INSTRUMENT | Processing Fee Instrument (Rs. 5,000) |

Also available (not all mandatory): Building Plan, Satellite Imagery, Gas/WASA bills, Court Order, Other.

Buttons:

- **Save and finish later** — leave as DRAFT  
- **Continue** — go to occupants/courts  

---

### STEP 5 — Others and courts  
**URL:** `/apply/{application}/occupants`  
**Button:** **Continue**

1. **Other occupants on the property?**  
   - No, only me  
   - Yes → name, CNIC, portion, rent offered  
2. **Any court case?**  
   - No  
   - Yes → court name, case no., type, stay / direction flags  

If a court case with restraining order is recorded, the application can later be treated as **sub judice** (Legal / DO handle).

---

### STEP 6 — Rs. 5,000 deposit  
**URL:** `/apply/{application}/deposit`

1. Fill instrument details:  
   - Type: Pay Order / Demand Draft / Banker’s Cheque  
   - Instrument no. & date  
   - Bank, branch, branch code  
   - Amount **5000**  
   - Payee: Chairman ETPB  
   - Depositor name / CNIC / contact  
2. Click **Record my deposit** (fee status **PENDING** — not yet bank-confirmed).  
3. Click **Submit my application**.

**Submit guards (system will refuse if missing):**

- fee instrument recorded;  
- all **mandatory** documents uploaded.

On success → status **SUBMITTED**, `submitted_at` stamped → **Done** page with application number.

Click **My applications** anytime from the sidebar to resume drafts or track status.

## B4. What the applicant’s Home dashboard shows after filing

- Alerts if something waits on **them** (finish draft, deficiency, unpaid deposit, arrears due)  
- Each case with a simple progress strip:  
  **Filed → Deposit confirmed → Examined → Rent → Approval → Regularized**  
- They **cannot** see officer rate workings or internal reasons  
- They **can** see status, payment status, and arrears they must clear  

---

# PART C — STATUS LIFECYCLE (WHERE THE CASE GOES)

## C1. Happy path (normal successful case)

```
DRAFT
  → SUBMITTED
  → FEE_VERIFICATION
  → SCRUTINY
  → SITE_INSPECTION
  → ASSESSMENT_PROPOSED
  → NOTICE_ISSUED
  → OBJECTION_WINDOW
  → (optional) HEARING
  → RENT_FIXED
  → ARREARS_COMPUTED
  → PENDING_ADMIN_APPROVAL
  → APPROVED
  → AGREEMENT_EXECUTION
  → REGULARIZED   ← FINAL SUCCESS
```

## C2. Branch / exception paths

| Situation | Status path |
|---|---|
| Papers incomplete | → **RETURNED_DEFICIENT** → applicant fixes → back to **SUBMITTED** |
| Not eligible under cut-off | → **REJECTED_INELIGIBLE** (terminal) |
| Court stay / pending suit | → **SUB_JUDICE** ↔ back to **SITE_INSPECTION** when cleared |
| Administrator sends back | → **REMANDED** → **ASSESSMENT_PROPOSED** |
| Administrator rejects | → **REJECTED** (terminal) |
| After approval, agreement & order | → **AGREEMENT_EXECUTION** → **REGULARIZED** |

## C3. Hard system locks (loophole prevention)

| Lock | Meaning |
|---|---|
| Payment not **PAID** | Almost no processing transition is allowed |
| Open objections undecided | Rent cannot be fixed |
| Sub judice | Assessment / approval path blocked |
| Arrears not satisfied | Cannot move to Administrator approval |
| Approval without reasons | Approve action refused |
| No nominee on record | Cannot move to agreement execution |

---

# PART D — ROLE-BY-ROLE: WHAT EACH DASHBOARD DOES WITH THE APPLICATION

Below is the same application, followed through every desk.

---

## D1. Dealing Assistant — `da.lhr@etpb.gov.pk`

### Dashboard / menu they see

- **Dashboard** (work home)  
- **All applications** (district)  
- **File for a walk-in**  
- Queues limited to what their permissions allow  
- **Registers** (operational lists)  

### What they do with a new / walk-in case

1. Citizen comes to the counter without using the portal.  
2. DA clicks **File for a walk-in**.  
3. Completes the **same six steps** as the public wizard on the citizen’s behalf.  
4. Application enters the system as DRAFT / SUBMITTED like any other.  

### What they do **not** do

- Do not confirm the bank fee (Accounts).  
- Do not fix rent (District Officer).  
- Do not approve regularization (Administrator).  

---

## D2. Accounts Officer — `accounts.lhr@etpb.gov.pk`

### Dashboard

Home tiles include **Deposits to confirm**.  
Sidebar: **Deposits to confirm**, **Arrears**, **Registers**.

### Exact steps on a newly submitted application

1. Sign in.  
2. Click **Deposits to confirm** (or open case → **Fee**).  
3. Find the application (status SUBMITTED / FEE_VERIFICATION, payment PENDING).  
4. Open **Fee** section.  
5. Review pay order / DD / banker’s cheque details.  
6. Confirm with bank → mark instrument **VERIFIED** (or bounced/rejected).  
7. When verified amount ≥ Rs. 5,000 → application **payment_status = PAID**.  

### Effect on other dashboards (sync)

- District Officer’s scrutiny queue can now process the case.  
- Applicant Home shows deposit confirmed.  
- Chairman fee-collection figures update.  

### Later in the life of the same case

- Open **Arrears** / case **Ledger**.  
- Post receipts against assessed arrears.  
- Cannot fix rent (separation of duties).  

---

## D3. District Officer — `do.lhr@etpb.gov.pk`  
*(the busiest statutory desk — Clause 10)*

### Dashboard

Work tiles such as:

- Waiting for scrutiny  
- Assessments in hand (with overdue count if past 60 days)  
- Objections to decide  
- Arrears outstanding  

Chart: doughnut of **applications by stage** (district-scoped).  
Sidebar: Scrutiny, Rent assessment, Objections, Arrears, Litigation, Registers, Deep report from case.

### Case file — buttons under “Open a section”

On any application show page the DO opens modules:

- **Evidence**  
- **Fee** (view)  
- **Rent assessment**  
- **Notices & objections**  
- **Occupants & litigation**  
- **Arrears**  
- **Completion** (after approval)  
- **Deep report**  
- **What can happen next** (Move to …)

### Step-by-step on the application (after PAID)

#### Stage 1 — Scrutiny

1. Sidebar **Scrutiny**.  
2. Open the paid case.  
3. Open **Evidence** — verify / mark deficient / waive where allowed.  
4. On case page **What can happen next**:  
   - **Move to Site Inspection**, or  
   - **Move to Returned (Deficient)** (applicant must fix), or  
   - **Move to Rejected (Ineligible)** if Clause 3(ii)(a) fails.  

#### Stage 2 — Site inspection → Assessment proposed

1. After site work, move toward assessment.  
2. Open **Rent assessment**.  
3. Open / create assessment round.  
4. Enter rate inputs: FBR, DC rate, NESPAK/valuator, market adjoining, etc.  
5. Enter comparable nearby properties.  
6. Enter **proposed** monthly rent (system can preview schedule).  
7. Propose → status can become **ASSESSMENT_PROPOSED**.  

#### Stage 3 — Notice & objection window

1. Open **Notices & objections**.  
2. Issue **public / tenant notice**.  
3. System sets:  
   - objection deadline = +15 days;  
   - assessment due date = +60 days from first notice;  
   - status path **NOTICE_ISSUED** → **OBJECTION_WINDOW**.  

#### Stage 4 — Objections / hearing (if any)

1. Sidebar **Objections** or case Notices module.  
2. Record objection (objector name, CNIC, plea, filed on).  
3. Optionally schedule **Hearing**.  
4. Decide each objection with reasons (Accepted / Rejected / Partial).  
5. **Rent cannot be fixed while objections are undecided.**  

#### Stage 5 — Fix rent & compute arrears

1. Back on **Rent assessment** → **Determine / fix rent** with **written reasons**.  
2. System builds:  
   - year-by-year rent schedule (8% enhancement);  
   - full arrears ledger.  
3. Status moves **RENT_FIXED** → **ARREARS_COMPUTED** when guards pass.  

#### Stage 6 — Clearance toward Administrator

1. Ensure arrears are paid / on lawful instalments (≤24) / remitted where allowed.  
2. Confirm not sub judice.  
3. Move case to **PENDING_ADMIN_APPROVAL** (one-month clock starts).  

#### Stage 7 — After Administrator approves

1. Open **Completion**.  
2. Ensure **Nomination form** is on record.  
3. Execute **Tenancy agreement**.  
4. Issue **Regularization order**.  
5. Status becomes **REGULARIZED**.  

---

## D4. Legal Officer — `legal.lhr@etpb.gov.pk`

### Dashboard / menu

- **Litigation** queue  
- Case → **Occupants & litigation**  
- Registers (especially Sub judice register)

### What they do with the application

1. If applicant declared a court case, or a stay appears later, open **Occupants & litigation**.  
2. Record / update: court, case no., type, pending flag, restraining order text/date, next hearing, direction case.  
3. System may move case to **SUB_JUDICE**.  
4. While sub judice, DO cannot push assessment/approval path.  
5. When stay lifts / case disposed, clear flags → case can return to **SITE_INSPECTION**.  

Sync: Chairman/Secretary litigation tiles and registers show the same cases.

---

## D5. Administrator — `admin.lhr@etpb.gov.pk`

### Dashboard

Lands on **officer work dashboard** (approval-first), not the Chairman overview.  
Tile: **Awaiting your approval** (shows overdue past one month).  
Sidebar: **Pending approval**, full Reports suite, All applications.

### Exact steps

1. Sign in → see approval tile.  
2. Click **Pending approval**.  
3. Open the dossier (assessment, arrears clearance, objections history visible).  
4. Decide:  
   - **Approve** — must record **reasons** (Clause 3(ii)(d)) → **APPROVED**;  
   - **Reject** → **REJECTED**;  
   - **Remand** → **REMANDED** (back to assessment).  
5. One-month SLA: overdue cases appear on their tile **and** on Chairman consolidated “deadline breaches”.  

Also can open **At a glance / Consolidated / Registers / Deep report** for Board packs.

---

## D6. Chairman — `chairman@etpb.gov.pk`

### Dashboard (executive)

Charts and tiles (live data):

- Applications / Regularized / Rent secured / Arrears recovery %  
- Line chart: intake vs regularisation (12 months)  
- Doughnut: caseload by stage  
- Bars: district caseload; arrears ageing  
- SLA bars: assessment 60-day / approval 1-month  
- Needs attention: pending deposits, sub judice, open objections  
- Litigation & stays summary  

### What they do with an individual application

- Usually oversight, not day-to-day processing.  
- Open **All applications** → case → **Deep report**.  
- Statutory powers in system design: extend assessment SLA, remission (Clause 12), cancel fraudulent tenancy (Clause 23) where permissioned.  

### Reports (main interest)

See Part E.

---

## D7. Secretary to the Board — `secretary@etpb.gov.pk`

### Dashboard

Same **executive** home as Chairman (charts + performance).  
Read-only oversight for Minister / Board Secretariat — **not** a Clause 3(ii) deciding office.

### What they do

- Review **At a glance** and **Consolidated** reports.  
- Export PDF / Word / Excel for upper authorities.  
- Open Deep report on any sensitive case.  
- Do **not** approve regularization or fix rent.

---

## D8. Auditor — `audit@etpb.gov.pk`

- Executive dashboard + all reports + **Audit log**.  
- Read-only: cannot mutate fee, rent, or approvals.  
- Used to verify that figures in reports match the live ledger.

---

## D9. System Administrator — `admin@etpb.gov.pk`

Not a statutory office under the Scheme. Maintains:

- **Users & roles**  
- **Reference data** (geography, document types, rate sources)  
- **Statutory settings** (cut-off date, fee amount, SLA days, enhancement method)  
- **Audit log**  

Does not replace Administrator approval or DO assessment.

---

# PART E — REPORTS (WHO GENERATES WHAT)

Every report screen offers **PDF**, **MS Word**, **Excel**, and **Print**, in official Board correspondence style.

## E1. Report types

| Report | Menu path | Contents |
|---|---|---|
| **At a glance** | Reports → At a glance | One-page performance: volumes, recovery, SLA %, districts, monthly intake |
| **Consolidated / master** | Reports → Consolidated report | Full pack: headlines, **named SLA breaches**, district league, stages, objections, arrears ageing, fee by instrument, litigation, intake & disposal |
| **Deep (single case)** | Case file → Deep report | Full dossier: applicant, property, area trace, evidence list, rates, schedule, ledger, objections, litigation, fee, approvals, history |
| **Registers** | Reports → Registers | Operational lists |

### Register list

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

## E2. Permission matrix

| Role | At a glance | Consolidated | Deep case | Registers | PDF/Word/Excel |
|---|---|---|---|---|---|
| Chairman | Yes | Yes | Yes | Yes | Yes |
| Secretary | Yes | Yes | Yes | Yes | Yes |
| Administrator | Yes | Yes | Yes | Yes | Yes |
| Auditor | Yes | Yes | Yes | Yes | Yes |
| District Officer | — | — | Yes | Yes | Yes |
| Accounts | — | — | — | Yes | Yes |
| Legal | — | — | — | Yes | Yes |
| Dealing Assistant | — | — | — | Yes | Limited |
| Applicant | — | — | — | — | — |

## E3. How to generate a report (any eligible account)

1. Open the report from the sidebar (or Deep report from a case).  
2. Optionally filter by **District** on executive reports.  
3. Click **PDF** / **MS Word** / **Excel** / **Print**.  
4. File or forward to higher authorities — distribution list is already on the document.

---

# PART F — END-TO-END STORY OF ONE APPLICATION (NARRATIVE)

Use this as a spoken walkthrough.

1. **Citizen** (`imran.shabbir@…`) opens **Apply**, completes six steps, records Rs. 5,000, clicks **Submit my application**. Status: **SUBMITTED**, payment **PENDING**.  
2. **Accounts** opens **Deposits to confirm**, verifies the instrument. Payment: **PAID**.  
3. **District Officer** opens **Scrutiny**, checks **Evidence**, moves to site inspection / assessment.  
4. DO opens **Rent assessment**, enters rates & comparables, **proposes** rent.  
5. DO opens **Notices & objections**, issues notice → 15-day window + 60-day clock.  
6. If an objection arrives, DO records and **decides** it (hearing if needed).  
7. DO **fixes rent with reasons** → schedule + arrears ledger appear.  
8. **Accounts** (and/or applicant) clear arrears via receipts / instalments.  
9. DO moves case to **Pending Administrator Approval**.  
10. **Administrator** opens **Pending approval**, **Approves with reasons**.  
11. DO opens **Completion**, records nominee if needed, executes **tenancy agreement**, issues **regularization order**. Status: **REGULARIZED**.  
12. **Applicant** Home now shows all six progress stages complete.  
13. **Chairman / Secretary** open **At a glance** / **Consolidated**, export PDF/Excel — the same case is counted in live totals.  
14. **Legal** only intervenes if a stay appears; then the case parks on **Sub Judice** until cleared.  
15. **Auditor** can open the Deep report and Audit log to verify integrity.

---

# PART G — SIDEBAR CHEAT SHEET BY ROLE

### Applicant
Home · Apply · My applications · Help widget (“Ask about the scheme”)

### Accounts
Dashboard · All applications · Deposits to confirm · Arrears · Registers

### Dealing Assistant
Dashboard · All applications · File for a walk-in · Registers

### District Officer
Dashboard · All applications · Scrutiny · Rent assessment · Objections · Arrears · Litigation · Registers (+ Deep report inside case)

### Legal
Dashboard · All applications · Litigation · Registers

### Administrator
Dashboard (approval work) · All applications · Pending approval · Objections (as permitted) · At a glance · Consolidated · Registers

### Chairman / Secretary / Auditor
Dashboard (executive charts) · All applications · At a glance · Consolidated · Registers · (Auditor also Audit log)

### System Admin
Everything above that permissions allow · Users & roles · Reference data · Statutory settings · Audit log

---

# PART H — FINAL STATES (DISPOSAL)

| Final status | Meaning |
|---|---|
| **REGULARIZED** | Success — tenant on record; agreement + order issued |
| **REJECTED** | Refused after process (with reasons) |
| **REJECTED_INELIGIBLE** | Failed Clause 3(ii)(a) or eligibility |
| **SUB_JUDICE** | Parked for court — not a final disposal until cleared or rejected |

---

# PART I — QUICK REFERENCE: BUTTONS OFFICERS CLICK MOST

| Intent | Where to click |
|---|---|
| Confirm Rs. 5,000 | Accounts → Deposits to confirm → Fee → Verify |
| Examine papers | DO → Scrutiny → Evidence |
| Return for deficiency | Case → What can happen next → Returned (Deficient) |
| Propose / fix rent | Case → Rent assessment |
| Issue notice | Case → Notices & objections |
| Decide objection | Objections queue or Notices module |
| Post arrears payment | Case → Arrears / Ledger |
| Approve regularization | Admin → Pending approval → Approve (with reasons) |
| Execute tenancy | Case → Completion → Agreement |
| Issue order | Case → Completion → Regularization order |
| Board performance PDF | Chairman/Secretary → At a glance → PDF |
| Full Board pack Excel | Chairman/Secretary → Consolidated → Excel |
| Single case for file | Case → Deep report → PDF/Word |

---

# PART J — RUNNING THE PORTAL (FACILITATOR)

```bash
cd back-end
php artisan serve --port=8000
```

Fresh data reload:

```bash
php artisan migrate:fresh --seed --force
php tools/verify-seed.php
```

Clone on another laptop: see **`START_HERE.md`** and run **`setup-laptop.bat`**.

---

## Closing statement for the Board

> From a citizen’s first click on **Apply**, through Accounts confirmation of the Rs. 5,000 fee, District Officer assessment under Clause 10, public notice and objection, Administrator approval under Clause 3(ii)(d), and finally the tenancy agreement and regularization order — every desk works on the **same live case**. Chairman and Secretary do not see a separate report database; their dashboards and PDF/Word/Excel exports are calculated from the same records the field officers update.

---

*End of document.*  
*Passwords: `ACCOUNTS.md` · Clone setup: `START_HERE.md` · Short demo script: `docs/DEMO_FLOW_GUIDE.md` · Design rationale: `MASTER_PLAN.md`*
