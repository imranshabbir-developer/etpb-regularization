# Application Complete Cycle — Who Acts Next

**Scheme:** Regularization of Possession · Clause 3(ii) · Scheme 1977  
**One line:** *Applicant files → Accounts unlocks → District Officer decides rent → Administrator approves → Agreement & order → Regularized.*

---

## Happy path (filing → regularized)

| # | Stage in system | Who entertains | What they do | Next officer |
|---|-----------------|----------------|--------------|--------------|
| 1 | **Draft → Submitted** | **Applicant** *(or Dealing Assistant / one-window for a walk-in)* | Basic intake (~8–10 fields); optional evidence; **mandatory biometric**; record Rs. 5,000 (ETPB payment API later) / attach receipt; submit | **Accounts Officer** |
| 2 | **Fee Verification** | **Accounts Officer** | Confirm deposit → payment **PAID** *(nothing moves until this)* | **District Officer** |
| 3 | **Scrutiny** | **District Officer** | Check eligibility, documents, possession cut-off (before 1 Jan 2010) | DO continues *(or returns / rejects)* |
| 4 | **Site Inspection** | **District Officer** | Record site visit / findings | DO continues |
| 5 | **Assessment Proposed** | **District Officer** | Propose monthly rent (FBR / DC / comparables) — Clause 10 | DO continues |
| 6 | **Notice Issued → Objection Window** | **District Officer** | Issue notice; **15-day** objection window; **60-day** assessment clock | Objector / DO *(if hearing)* |
| 7 | **Hearing** *(if needed)* | **District Officer** | Decide objections on record | DO continues |
| 8 | **Rent Fixed → Arrears Computed** | **District Officer** | Fix rent with reasons; generate rent schedule & arrears ledger | **Accounts** *(receipts)* + Applicant |
| 9 | Arrears clearance | **Applicant** pays · **Accounts** posts receipts / instalments | Clear outstanding arrears | **District Officer** |
| 10 | **Pending Administrator Approval** | **District Officer** forwards | Case ready for Administrator | **Administrator** |
| 11 | **Approved** | **Administrator** | Approve **within one month**, with reasons — Clause 3(ii)(d) | **District Officer** |
| 12 | **Agreement Execution** | **District Officer** | Nominee (if any), execute **tenancy agreement**, issue **regularization order** | — |
| 13 | **Regularized** | System end-state | Occupant recorded as **tenant** (not freehold allotment) | **Chairman / Secretary** *(reports only)* |

---

## Side paths (who intervenes)

| Situation | Who | Effect |
|-----------|-----|--------|
| Papers incomplete | **Administrator / District Officer / one-window** may upload missing docs from the case file; or DO → **Returned (Deficient)** | Applicant or staff completes, then DO again |
| Wrong entry / correction | **Chairman only** (`records.correct`) | Secretary is view-only and cannot alter entries |
| Ineligible (e.g. possession after cut-off) | **District Officer** → **Rejected (Ineligible)** | Terminal |
| Court stay / litigation | **Legal Officer** → **Sub Judice** | Parked until stay lifted; then **District Officer** resumes |
| Administrator sends back | **Administrator** → **Remanded** | Back to **District Officer** (re-assess) |
| Administrator refuses | **Administrator** → **Rejected** | Terminal |
| Fraud / policy oversight | **Chairman** / **Auditor** | Oversight & reports — **not** the Clause 3(ii) deciding desk |

---

## Chain at a glance

```
Applicant (or DA)
       │  submit + Rs. 5,000 instrument
       ▼
Accounts Officer  ── confirms fee (PAID) ──►  unlocks processing
       │
       ▼
District Officer  ── scrutiny → inspection → assess → notice →
                     objections/hearing → fix rent → arrears ──►
       │
       ▼
Administrator     ── approve with reasons (≤ 1 month)
       │
       ▼
District Officer  ── agreement + regularization order
       │
       ▼
REGULARIZED       ── Chairman / Secretary see it in live reports
```

---

## Demo logins (Lahore seed)

| Role | Email | Password |
|------|-------|----------|
| Applicant (fresh file) | `imran.shabbir@example.com` | `Imran@Portal2026` |
| Accounts | `accounts.lhr@etpb.gov.pk` | `Etpb@2026#Change` |
| District Officer | `do.lhr@etpb.gov.pk` | `Etpb@2026#Change` |
| Administrator | `admin.lhr@etpb.gov.pk` | `Etpb@2026#Change` |
| Dealing Assistant (walk-in) | `da.lhr@etpb.gov.pk` | `Etpb@2026#Change` |
| Legal (stays only) | `legal.lhr@etpb.gov.pk` | `Etpb@2026#Change` |

*Detailed click-by-click: `docs/PLATFORM_END_TO_END_FLOW.md` · Short demo script: `docs/DEMO_FLOW_GUIDE.md`*
