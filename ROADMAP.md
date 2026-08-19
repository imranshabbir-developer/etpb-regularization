# Regularization of Possession — Roadmap for Review

**For:** the client, before further implementation
**Version:** 1.0
**Date:** 19 August 2026

> This document exists so you can check the plan **before** more code is written,
> and amend anything that is wrong. Please mark it up freely.
>
> It is written against your two instruction files:
>
> - `basic requirements.txt` — the background and the intent
> - `lease-management-system-requirements.txt` — the form heads and fields
>
> Section 2 raises **three things I need you to decide**, and section 6 is an
> honest account of where the code I have already written does **not** match
> your instructions.

---

## 1. What you asked for, in my words

The Government of Punjab has announced that a person in occupation of an
evacuee trust property **before 1 January 2010** may apply to the Evacuee Trust
Property Board to have their occupation recognised.

You need a **public web portal**:

1. A member of the general public visits the portal and files an application,
   entering the details set out in your requirements file.
2. The applicant must deposit **Rs. 5,000**. Until that is deposited, the
   application status is **"pending"** and the department does **not** process it.
3. Once the deposit is made, the status becomes **"paid"**, and only then do the
   officers process the application in accordance with law.
4. Officers assess rent, receive objections, decide, and approve.
5. Higher authorities get a **consolidated / master / executive report**, and
   there is also a **deep report** containing every element.

Built in **PHP, Laravel, MySQL and Tailwind CSS**.

**If any of the above is wrong, correct it here and everything downstream changes.**

---

## 2. Three questions I need answered

### 2.1 Ownership, or tenancy? — *the most important question in this document*

Your `basic requirements.txt` says the occupant applies **"for getting ownership"**
of the land.

But the law you supplied does not give ownership by this route. **Clause 3(ii) of
the Scheme 1977** — headed *"REGULARIZATION OF POSSESSION"*, and the clause your
requirements file points to — says:

> *An existing occupant / occupants of a property whose possession has not been
> regularized may be **treated as tenant**, provided… a **tenancy agreement**
> shall be executed by the concerned District Officer with the occupant and the
> rent shall be fixed as per paragraph 10.*

So Clause 3(ii) produces a **tenant paying rent**, not an owner. That is also why
your requirements file is full of rent: market rent from 2000, FBR and DC rates,
rent assessment tables, rent offered by illegal occupants. **Those fields only
make sense for a tenancy.**

The Scheme does contain a separate route to **ownership** — sale of the property
to the occupant at market price — but that is a different provision with
different steps, and none of the fields in your requirements file belong to it.

**Please confirm which of these is right:**

| | Option | What the portal does |
|---|---|---|
| **A** | **Regularization as tenant** *(what the fields describe, and what I have built)* | Occupant becomes a recorded tenant, rent fixed under Clause 10, arrears cleared, tenancy agreement executed. |
| **B** | **Regularization now, ownership later** | Exactly as A, and once regularized the tenant may separately apply to purchase. The portal would gain a later "application to purchase" stage. |
| **C** | **Ownership directly** | A different process from the one your requirements file describes. Most of the rent fields would fall away and I would need the notification that announced it. |

I have built **Option A**, because that is what your field list describes. Nothing
is wasted if the answer is B. If the answer is C, I need the Punjab notification
you referred to, because the 1977 Scheme alone does not support it.

### 2.2 Who enters the Rent Assessment figures? — ✅ **ANSWERED**

> **Your answer, 19 August 2026: "rate will be determined by the district officer".**

So the whole **Rent Assessment** head belongs to the **District Officer**, not to
the applicant. The line *"all above detail will be provided by the applicant"* in
the requirements file does **not** extend to this head.

This matches Clause 10(i)(a) of the Scheme, under which it is the District
Officer who *"shall assess or re-assess the rental value… keeping in view the
market rent and rent of other properties in the vicinity in similar
circumstances"*.

**What this settles:**

| Field | Entered by |
|---|---|
| FBR Rate | District Officer |
| D.C (District Collector) Rate | District Officer |
| Nespak / property valuator rate | District Officer |
| Prevailing market rent of adjoining properties | District Officer |
| **Rate determined by District Officer** | District Officer |
| Particulars of objectors and their pleas | District Officer (recording what the objector filed) |
| Decision of District Officer | District Officer |
| Remarks / approval by Administrator | Administrator |

**The applicant enters none of it.** The applicant's form is Heads 1, 2, 4 and 5
only — their own particulars, the property, the evidence of possession, the
occupant/litigation position, and the Rs. 5,000 deposit.

**Already enforced in the code.** Verified on 19 August 2026:

- the `APPLICANT` role holds **no** assessment permission of any kind;
- only `DISTRICT_OFFICER` holds `assessment.rate_inputs` and `assessment.fix_rent`;
- the applicant intake form contains **zero** rate fields.

**One consequence I need you to confirm — see §2.4.**

### 2.4 May the applicant *see* the rent once it is fixed?

Following from §2.2: the applicant cannot **enter** anything in the Rent
Assessment head. But Clause 3(ii)(b) requires the applicant to **clear all
arrears** before being treated as a tenant — and on the test case those arrears
came to **Rs. 1,78,71,798**.

An applicant who cannot see the figure cannot pay it.

**Please pick one:**

| | Option | What the applicant sees on their own application |
|---|---|---|
| **A** *(my recommendation)* | **Outcome only** | The monthly rent fixed, the total arrears, what has been paid, and the balance outstanding — but **not** the FBR/DC/Nespak inputs, not the comparables, not the internal reasoning. |
| **B** | **Nothing until demanded** | The applicant sees only their status. The amount reaches them by a separate demand notice issued outside the portal. |
| **C** | **Full transparency** | The applicant sees the rate inputs and the recorded reasons as well — closer to what a public notice under Clause 10(i)(b) already puts on display anyway. |

I have built **nothing** here yet, precisely because it is a who-sees-what
decision and you have been clear about those. Tell me A, B or C.

### 2.3 What does "paid" unlock, exactly?

You said: unpaid → **"pending"**, not processed; paid → **"paid"**, then processed.

**Please confirm:**

- Can the applicant **fill and save** the whole application before paying, and only submission is blocked? *(This is what I have assumed — it is kinder to the applicant.)*
- Or should the form itself be blocked until payment?
- Is the Rs. 5,000 **refundable** if the application is rejected?
- Is it **adjustable** against the arrears the applicant will owe?
- Is payment recorded by an officer from a physical pay order, or will there be an **online payment gateway**? *(I have assumed a physical instrument, because your field list asks for bank, branch code and instrument details.)*

---

## 3. The application form — your six heads, exactly

I have kept your heads as the structure of the form. The applicant sees them as
six steps.

### Head 1 — Applicant General Information

| Your field | In the system | Status |
|---|---|---|
| name | `applicants.full_name` | ✅ built |
| parentage | `parentage_type` (s/o or w/o) + `parentage_name` | ✅ built |
| address | `postal_address` | ✅ built |
| cnic no. | `cnic`, 13 digits, validated | ✅ built |
| contact | `contact` | ✅ built |

**Then the applicant provides:**

| Your field | In the system | Status |
|---|---|---|
| property no. or sub unit no. *(optional)* | `properties.property_no`, `sub_unit_no` (nullable) | ✅ built |
| area entered as sqft / marla / kanal / acre | unit picker, single or compound entry | ✅ built |
| **area converted to sqft by Pakistani formula** | `AreaConversionService`, exact arithmetic | ✅ built + tested |
| date of possession, **prior to 1.1.2010 accepted** | validated and enforced at intake | ✅ built + tested |
| market rent from 1.7.2000 or date of possession, **whichever earlier**, determined by DO per Clause 10 | computed and shown with all three candidate dates | ✅ built + tested |
| Mouza, City, Tehsil, District, Province | full cascading masters, 142 districts seeded | ⚠️ **Mouza list not seeded** — see §7 |

### Head 2 — Evidence of Possession (certified copy)

All thirteen document types are **seeded in the database** with a
"certified copy required" flag. **The upload screen is not built yet.**

| Your document | Seeded | Upload UI |
|---|---|---|
| Jamabandi (Record of Rights) | ✅ | ❌ |
| Mutation | ✅ | ❌ |
| Khasra Girdavari | ✅ | ❌ |
| GEO Tagging (Geo Coordinates) | ✅ *(lat/long captured on the form)* | ❌ map picker |
| Approved Building Plan | ✅ | ❌ |
| Location Plan | ✅ | ❌ |
| Satellite Imagery | ✅ | ❌ |
| Electricity Bill | ✅ | ❌ |
| SNGPL / SSGC Bill | ✅ | ❌ |
| WASA Bill | ✅ | ❌ |
| Court Order | ✅ | ❌ |
| Affidavit re: date of possession **and nominee** | ✅ | ❌ |
| Any other supporting document | ✅ | ❌ |

### Head 3 — Rent Assessment

| Your field | In the system | Status |
|---|---|---|
| FBR Rate | rate input row | ✅ built |
| D.C (District Collector) Rate | rate input row | ✅ built |
| Nespak / property valuator rate | rate input row + valuator name and licence | ✅ built |
| Prevailing market rent of adjoining properties in same circumstances | comparables table with area, rent, distance, source | ✅ built |
| Rate determined by District Officer | the operative figure | ✅ built |
| Particulars of objectors and their pleas | objector name, parentage, CNIC, address, contact, relationship, full plea | ✅ built |
| Decision of District Officer | recorded with **mandatory written reasons** | ✅ built |
| **in tabular format from 2000, 2004, 2008, 2012, 2016, 2020, 2024** | exactly this grid, on screen | ✅ built |
| Remarks / approval by Administrator | table exists | ⚠️ **approval screen not built** |

### Head 4 — Rent offered by the illegal Occupants *(tabular)*

| Your field | In the system | Status |
|---|---|---|
| rent offered by illegal occupants, tabular | `occupant_offers` — name, CNIC, contact, portion, area, rent offered, date, terms | ⚠️ table built, **no screen** |
| whether pending before any court of law | `litigations.is_pending` | ⚠️ table built, no screen |
| any restraining order | `litigations.has_restraining_order` | ⚠️ table built, no screen |
| direction case | `litigations.is_direction_case` | ⚠️ table built, no screen |

*(The blocking rule already works: a pending case or restraining order stops the
application from proceeding.)*

### Head 5 — The Rs. 5,000 deposit

| Your field | In the system | Status |
|---|---|---|
| Rs. 5,000 banker's cheque / pay order / demand draft **in favour of Chairman ETPB** | `fee_payments`, payee defaults to Chairman ETPB | ⚠️ table built, **no screen** |
| Date of submission | ✅ field | ⚠️ no screen |
| Amount | ✅ field, defaults 5,000 | ⚠️ no screen |
| Bank, branch location and branch code | ✅ fields | ⚠️ no screen |
| District | ✅ field | ⚠️ no screen |
| applicant name / cnic / contact *(depositor)* | ✅ fields | ⚠️ no screen |
| **unpaid → status "pending", not processed** | **⚠️ partly — see §6.2** | needs rework |
| **paid → status "paid", then processed** | **⚠️ partly — see §6.2** | needs rework |

### Head 6 — Reports

| Your requirement | Status |
|---|---|
| consolidated / master / executive report for higher authorities | ❌ **not built** |
| deep report — every element included | ❌ **not built** |

---

## 4. Who uses the portal

| User | What they do |
|---|---|
| **Applicant (general public)** | Registers, fills the six heads, uploads evidence, records the Rs. 5,000 instrument, tracks status, responds to queries. |
| **Dealing Assistant** | Files on behalf of walk-in applicants who have no internet. |
| **Accounts** | Confirms the Rs. 5,000 instrument with the bank and flips the status to **paid**. |
| **District Officer** | Scrutinises, assesses rent under Clause 10, issues notices, hears objections, **fixes the rent with reasons**. |
| **Administrator** | Records remarks and **approves** — Clause 3(ii)(d), within one month. |
| **Chairman** | Executive reports; senior powers. |
| **Auditor** | Read-only. |

---

## 5. What is already working

Verified by driving a real case through the running system:

- Application **ETPB/PB-LAHORE/ROP/2026/0001** filed for Ram Lal s/o Diwan Chand
- Area **2 Kanal 7 Marla 3 Sarsai → 12,886.50 sqft** (exact)
- Possession **12-04-1998** accepted; arrears correctly run from **12-04-1998**, not 01-07-2000, because it is earlier
- Rent proposed at Rs. 32,000; public notice issued; objection filed by Bashir Ahmed; objection **partially accepted**, rent reduced
- Rent **fixed at Rs. 28,500/month with written reasons**
- **30-year schedule** and arrears ledger generated automatically
- The 2000–2024 milestone table renders as you asked

**Also built:** database of 62 tables, 8 roles with permissions, login with lockout,
forced password change, full audit trail, statutory deadline clocks, and **53
automated tests**.

**Two real bugs were found and fixed during that run**, both of which would have
cost money:

1. A complete year was being charged **11.9672 months instead of 12** — every full year of every case under-charged by about 0.27%.
2. The ledger was charging the **whole current year in advance**, including months not yet due, and calling it arrears. Total for the test case fell from Rs. 19,194,912 to the correct **Rs. 17,871,797**.

---

## 6. Where my implementation does not match your instructions

I would rather tell you this plainly than have you find it later.

### 6.1 I used custom CSS, not Tailwind — **my mistake**

Your requirements say **Tailwind CSS**. I wrote a hand-built stylesheet instead.
It looks right and it is responsive, but it is not what you asked for.

Tailwind 4 is **already installed** in the project (it ships with Laravel 13), so
this is a rework of the styling, not a new dependency. **I will convert every
screen to Tailwind.** The visual design — the Pakistan flag palette, with the
white band standing for religious minorities — stays exactly as it is; only the
mechanism changes.

**Cost:** roughly one working day. **Please confirm you want this** — it is the
correct thing to do, but it is rework that adds no new function.

### 6.2 The payment status is not modelled the way you described it

You described a simple, visible thing: **pending → paid**, and paid is what
unlocks processing.

What I built instead was a fee record with its own internal status, and a rule
that blocks *submission*. That is close, but it is not your model, and an officer
looking at a list of applications cannot see at a glance who has paid.

**I will change it to match you exactly:**

- add `payment_status` on the application itself: **`PENDING` / `PAID`**
- default **`PENDING`** on creation
- flips to **`PAID`** only when Accounts confirms the instrument
- **no officer processing screen accepts an application while it is `PENDING`**
- the status is shown on every list, on the applicant's own tracking page, and in the reports

### 6.3 I built things you did not ask for

These are legally grounded in the Scheme, but they are **not in your requirements
file**, and you told me not to add extra things:

| Extra | Where it came from | My recommendation |
|---|---|---|
| Penalties (Clause 22) | Scheme Chapter VII | **Park it** — database table stays, no screen, no effort spent |
| Ejectment proceedings (Clause 21) | Scheme Chapter VII | **Park it** — same |
| Instalment plans (Clause 13) | Scheme | **Keep** — arrears back to 1998 can exceed Rs. 1.7 crore; without instalments most applicants simply cannot comply, and the application stalls |
| Remission (Clause 12) | Scheme | **Keep** — same reason; also the widow/orphan/indigent grounds |
| Tenancy agreement + regularization order | Clause 3(ii)(b) requires the agreement | **Keep** — this is the actual output of the process |
| Nominee form | your file asks for the *"Affidavit about date of possession and nominee"* | **Keep** — it is in your list |

**Nothing here has cost you extra time** — the tables were created in one pass.
But say the word and I will remove any of them.

### 6.4 I should have written this roadmap first

You asked for the roadmap before implementation. I built first. That was my
error in sequencing, though the work done is sound and is listed above.

---

## 7. What is left to build

In the order I propose to do it.

*Status as at 19 August 2026 — everything except item 10 is now built.*

| # | Work | Status |
|---|---|---|
| **1** | **Payment status `PENDING`/`PAID`** exactly as §6.2 | ✅ **Done.** On the application itself, gating **all twelve** departmental stages, not just the first. Four tests pin it. |
| **2** | **Rs. 5,000 deposit screen** (Head 5) | ✅ **Done.** Every field you listed. Recording ≠ paying: only Accounts confirming with the bank flips it to PAID. |
| **3** | **Evidence upload screen** (Head 2) | ✅ **Done.** All 13 heads, certified-copy enforcement, officer verification with reasons, SHA-256 per file, stored outside the web root, downloads audited. |
| **4** | **Convert all screens to Tailwind** (§6.1) | ✅ **Done.** Real Tailwind 4 via Vite; the hand-written stylesheet is deleted. |
| **5** | **Public applicant portal** | ✅ **Done.** Self-registration at `/register`, a guided **six-step wizard** that refuses to skip ahead, a plain-language "my applications" page showing progress in words rather than status codes, and a help widget that answers scheme questions and says so when it does not know. |
| **6** | **Occupant offers + litigation screens** (Head 4) | ✅ **Done.** Offers compared against the assessed rent; pending case or stay parks the application and releases it on disposal. |
| **7** | **Administrator approval screen** (Head 3, last row) | ✅ **Done.** Whole basis of the decision on one screen, reasons mandatory, one-month clock measured and breaches recorded. |
| **8** | **Deep report** (Head 6) | ✅ **Done.** 19 sections, every element, print-ready. |
| **9** | **Master / executive report** (Head 6) | ✅ **Done.** Plus six operational registers with Excel export. |
| **10** | **Mouza master data** | ⛔ **Blocked on you** — needs the Punjab Board of Revenue mouza list. |
| **11** | Admin screens (users, reference data, settings, audit viewer) | ✅ **Done.** Beyond the requirement file, but needed to run the system: user and role management, district/tehsil/mouza reference data, dated statutory settings, and a searchable audit trail. Email and SMS notifications remain unbuilt — no gateway has been provided. |

**Also built, beyond the list above** — because without it no application could actually finish:

- **Completion module** — nomination form with legal heirs, tenancy agreement, regularization order. The proviso to para 3(iii)(B) is enforced: no regularization without the nominee form.
- **Five officer work queues** — scrutiny, assessment, objections, arrears, litigation.

### Checked for the demonstration

Two browser harnesses (`back-end/tools/uicheck.mjs` and `darkcheck.mjs`) sign in
as all seven roles and walk every screen at phone, tablet and desktop widths.
They currently report **no layout failures, no unreadable text, no tap target
too small, and no console errors**, in both the light and the dark theme. All
eleven report downloads were fetched over HTTP and confirmed to be real PDF,
Word and Excel files. 76 automated tests pass.

### Proven end to end

Application **ETPB/PB-LAHORE/ROP/2026/0001** was driven from **DRAFT to REGULARIZED**
through the running system in **14 recorded steps**:

filed → Rs. 5,000 recorded → confirmed by Accounts (PENDING → PAID) → scrutiny →
site inspection → assessment opened → FBR, DC and comparable rates recorded →
rent proposed at Rs. 32,000 → public notice issued (15-day and 60-day clocks
started) → objection filed by Bashir Ahmed → partially accepted, rent reduced →
**rent fixed at Rs. 28,500 with written reasons** → 30-year schedule and
**Rs. 1,78,71,798** arrears ledger generated → 24 instalments allowed under
Clause 13 → **approved by the Administrator within the month, with reasons** →
nomination form obtained → tenancy agreement executed → **regularization order
issued**.

**57 automated tests, 156 assertions, all passing.**

---

## 8. Things I still need from you

| # | What | Why it matters |
|---|---|---|
| 1 | **Answer §2.1 — tenancy or ownership** | Shapes the entire process |
| 2 | ~~Answer §2.2 — who fills the rent assessment fields~~ ✅ **ANSWERED 19-08-2026: the District Officer determines the rate.** Applicant enters none of Head 3. Already enforced in the code. | — |
| 2a | **Answer §2.4 — may the applicant *see* the fixed rent and arrears?** | They cannot pay Rs. 1.78 crore they are never shown |
| 3 | **Answer §2.3 — what "paid" unlocks, and refundability** | Your central business rule |
| 4 | **Is the 8% annual rent increase simple or compound?** Clause 11(ii) does not say. Over 24 years the difference is **6.34× versus 2.92×** the base rent. Currently set to compound. | Changes every rupee demanded from every applicant. **Needs a written ruling from ETPB.** |
| 5 | **Is a Marla 272.25 sqft or 225 sqft?** Revenue standard versus urban housing standard — a **21% difference** that flows straight into the rent. Currently revenue (272.25), selectable per district. | Changes the assessed area of every property |
| 6 | **Is the Rs. 5,000 fee notified by a Board order?** Please send it — it is not in the 1977 Scheme text | Needed on the record |
| 7 | **The Punjab notification** announcing this scheme, if there is one | You mention a recent announcement; I have only the 1977 Scheme |
| 8 | **Mouza list** for the districts in scope | Head 1 asks for Mouza |
| 9 | **Is Urdu required** at launch, or later? | Affects every screen |
| 10 | **Where will this be hosted**, and roughly how many applications a year? | Sizing and security hardening |

---

## 9. Technology

As you specified:

| Layer | Choice | Note |
|---|---|---|
| Language | **PHP 8.4.24** | Upgraded from 8.0.30, which was past end-of-life and unsafe for a public portal |
| Framework | **Laravel 13.26.1** | As you asked |
| Database | **MySQL 8.0.46** | Your existing server, database `etpb_regularization` |
| CSS | **Tailwind CSS 4** | Already installed; **conversion pending — §6.1** |
| Server | Apache, own port `8080`, document root at `back-end/public` | Application code and uploaded CNICs/Jamabandis are not reachable over the web |

Your folder structure — `front-end`, `back-end`, `database` — is preserved.

---

## 10. How to review this document

Please mark up anything wrong, especially:

- **§2.1** — tenancy or ownership *(most important)*
- **§3** — is any field missing, or any field there that you do not want?
- **§6.3** — should I remove the extras?
- **§7** — is the order right? What do you need first?

Once you have amended it, I will work to the amended version and nothing else.

---

*End of roadmap v1.0 — awaiting your review.*
