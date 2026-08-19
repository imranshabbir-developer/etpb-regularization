# How to run the project

## It is already running

Open your browser at:

```
http://localhost:8080
```

Apache and MySQL are both up on this machine right now. If the page loads, skip
to **[Signing in](#signing-in)**.

---

## Starting it from cold

If you have restarted the machine, or the page does not load:

### 1. Start MySQL and Apache

Open the **XAMPP Control Panel** and press **Start** next to:

- **Apache**
- **MySQL** — *only if your MySQL 8 service is not already running as a Windows service; it usually is*

That is all. Apache picks up the site automatically, because
`deploy/apache-etpb.conf` is included from `httpd.conf`.

> **If Apache refuses to start**, something else is using port 8080. Change the
> two places that say `8080` in `deploy/apache-etpb.conf` to a free port, e.g.
> `8081`, and start it again.

### 2. Open the portal

```
http://localhost:8080
```

---

## Signing in

Every account below uses the **same password**.

**Password for all accounts: `Etpb@2026#Change`**

> **Before a demonstration.** On this machine the forced password change has
> been switched off, so every account signs straight in and nothing interrupts
> the meeting. A fresh installation still forces the change on first sign-in —
> that is set by the seeder and has not been weakened. To put it back on this
> machine:
>
> ```bash
> cd back-end
> php artisan tinker --execute="DB::table('users')->whereNotNull('district_id')->orWhereIn('email',['admin@etpb.gov.pk','chairman@etpb.gov.pk','audit@etpb.gov.pk'])->update(['force_password_change'=>true]);"
> ```

| Email | Role | What they can do |
|---|---|---|
| `admin@etpb.gov.pk` | System Administrator | Everything |
| `chairman@etpb.gov.pk` | Chairman, ETPB | Executive reports, remission under Clause 12 |
| `admin.lhr@etpb.gov.pk` | Administrator | Approves regularization |
| `do.lhr@etpb.gov.pk` | **District Officer** | **Assesses and fixes the rent** |
| `da.lhr@etpb.gov.pk` | Dealing Assistant | Files applications for walk-in applicants |
| `accounts.lhr@etpb.gov.pk` | **Accounts Officer** | **Confirms the Rs. 5,000 and flips the status to PAID** |
| `legal.lhr@etpb.gov.pk` | Legal Officer | Court cases, stay orders |
| `audit@etpb.gov.pk` | Auditor | Read-only |

There is also a public applicant account for showing the citizen-facing side:

| Email | Password |
|---|---|
| `demo.applicant@example.com` | `Demo#Portal2026` |

---

## A five-minute tour

The database carries **27 applications across 7 districts**, deliberately spread
over every stage of the workflow so that no screen is empty during a
demonstration — including a case past its assessment deadline, one past the
Administrator's one-month limit, one stayed by a court, and one still waiting on
its deposit. Headline figures: **Rs. 268,900** monthly rent secured,
**Rs. 167,195,185** arrears assessed, **33%** recovered.

Cases worth opening:

| Case | District | Why |
|---|---|---|
| `ETPB/PB-LAHORE/ROP/2026/0001` | Lahore | Regularized end to end |
| `ETPB/PB-LAHORE/ROP/2026/0008` | Lahore | Objection window open, assessment clock running |
| `ETPB/PB-RAWALPIN/ROP/2026/0001` | Rawalpindi | Sitting with the Administrator for approval |
| `ETPB/PB-LAHORE/ROP/2026/0012` | Lahore | Sub judice — shows the workflow guard |
| `ETPB/PB-LAHORE/ROP/2026/0010` | Lahore | Deposit still `PENDING` — nothing proceeds |

### As a member of the public — filing an application

1. Go to **http://localhost:8080/register** and create an applicant account
2. **Apply** — six short steps, one per screen
3. On step 2, enter the area as **1 Kanal 4 Marla** in compound mode. The
   square-foot figure appears live underneath with the conversion worked out
   (6,534 sqft), so nobody has to trust a black box
4. On step 3, try a date of possession in **2011** — it is refused, citing
   Clause 3(ii)(a)
5. Steps 4 to 6 attach documents, declare any other occupant or court case,
   and record the Rs. 5,000 deposit
6. The application is created as a **draft**, marked **payment PENDING**

A Dealing Assistant (`da.lhr@etpb.gov.pk`) uses the same six steps to file on
behalf of a walk-in applicant who cannot use the portal themselves.

### As the Accounts Officer — the Rs. 5,000

1. Sign in as `accounts.lhr@etpb.gov.pk`
2. Open the application → **Fee**
3. Record the pay order / banker's cheque / demand draft, then confirm it
4. The application flips from **PENDING** to **PAID**

**Until this happens, nothing else can be done with the application.** Every
processing step refuses it and tells you why.

### As the District Officer — assessing the rent

1. Sign in as `do.lhr@etpb.gov.pk`
2. Open the case file → **Rent assessment**
3. Record the FBR rate, DC rate, valuator rate, and nearby comparable rents
4. Enter a proposed rent — a live projection shows what it means across the
   whole arrears period
5. Go to **Notices & objections**, issue the public notice — the 15-day objection
   window and the 60-day assessment clock both start
6. Record any objection, then decide it with reasons
7. Back on **Rent assessment**, fix the rent with written reasons

At that moment the system builds the whole year-by-year rent schedule back to
the date of possession and the arrears ledger with it.

### Look at the ledger

**Arrears** on the case file shows every year from 1998 to today, the 8% annual
enhancement, what is owed, and the instalment and remission options.

---

## What each role sees

The portal shows a different home screen depending on who signs in, because a
member of the public filing one application and a Chairman reviewing the whole
scheme want opposite things.

| Signed in as | Home screen | Navigation |
|---|---|---|
| **Applicant** (public) | Their own applications, a progress bar in plain words, and anything waiting on them | Home, Apply, My applications |
| **Dealing Assistant** | Work queues | File for a walk-in, all applications |
| **Accounts Officer** | Deposits waiting to be confirmed | Deposits to confirm, arrears |
| **District Officer** | Scrutiny, assessment, objections — each tile links to that work | All five work queues |
| **Administrator** | Approvals due, with the one-month clock | Approvals, reports |
| **Chairman** | Performance at a glance | Reports only |
| **System Administrator** | Everything | Plus users, reference data, settings, audit |

## Reports

Four reports, each downloadable as **PDF, MS Word or Excel**:

| Report | For | Where |
|---|---|---|
| **At a glance** | Chairman and above — one page, performance only, no case detail | Reports → At a glance |
| **Consolidated report** | Higher authorities — the full master report | Reports → Consolidated report |
| **Deep report** | One application, every element, 19 sections | Any case file → Deep report |
| **Registers** | Routine operational lists — applications, fee, arrears, objections, litigation, regularized, assessment | Reports → Registers |

Every report screen has PDF / MS Word / Excel / Print buttons.

### Official format

The PDF and Word versions are laid out as official correspondence, not as a
screen printout:

- centred letterhead — **Government of the Punjab**, the Board, the Scheme and
  the office address
- the flag rule beneath it: Pakistan green with the white hoist band that stands
  for the country's religious minorities, whose properties the Board holds
- **No.** reference and **Dated:** on one line
- **SUBJECT:—** underlined
- numbered sections, serif throughout, tables carrying a **Sr.** column and
  repeating their headings across pages
- a signature block naming the officer who generated it and their designation
- **Copy forwarded for information and necessary action to:—** with a
  distribution list appropriate to the report
- **Page n of m** and the reference along the foot of every page

The distribution list differs by report — a performance glimpse goes to the
Minister-in-charge and the Secretary, a case file goes to the Administrator,
the District Officer and the applicant.

Excel exports open natively with one sheet per section; a CNIC is written as
text so Excel does not turn it into scientific notation.

## Light and dark

The moon or sun button in the top bar switches between light and dark, and the
choice is remembered on that device. Left alone, the portal follows the
operating system setting.

## The help widget

**Ask about the scheme** at the bottom-right answers questions about eligibility,
the fee, documents, rent, arrears, instalments and what happens next — citing the
clause each answer comes from.

It answers from a curated knowledge base rather than a generative model, on
purpose. The questions people ask here are legal ones, and a confident wrong
answer would cost a member of the public money. When it cannot match a question
it says so and points to the district office rather than inventing something.

## Checking the interface

Two harnesses drive a real browser against the running portal. They are the
evidence behind the claims in this file, and they can be re-run at any time.

```bash
cd back-end
node tools/uicheck.mjs            # layout, tap targets, text size, console errors
node tools/uicheck.mjs --shots    # the same, and writes screenshots
node tools/darkcheck.mjs          # colour contrast in dark mode
THEME=light node tools/darkcheck.mjs   # and in light mode
```

`uicheck` signs in as all seven roles and visits every screen at phone (390px),
tablet (820px) and desktop (1440px), reporting any page that scrolls sideways,
any text under 11px, any tap target under 32px, and any console error.
`darkcheck` measures every piece of text against its own background and reports
anything below a 4.5:1 contrast ratio. Screenshots land in
`back-end/storage/uicheck/`.

Both currently report **zero findings**.

## On a phone

Everything works down to a 360px screen. The navigation collapses to a drawer,
tables scroll rather than squash, the wizard runs one step per screen, and the
help widget becomes a full-height sheet. The **Use my current location** button
on the property step fills in the geo coordinates from the device.

## If something goes wrong

| Symptom | Fix |
|---|---|
| Page will not load | Is Apache started in the XAMPP panel? |
| "could not connect to database" | Start MySQL in the XAMPP panel |
| Styling looks broken | Run `npm run build` inside `back-end` |
| A change to a page does not appear | Run `php artisan view:clear` inside `back-end` |
| You forgot a password | See **Resetting a password** below |

**Where to look for errors:** `back-end/storage/logs/laravel.log`

### Resetting a password

Open a terminal in the `back-end` folder and run:

```bash
php artisan tinker
```

then paste:

```php
$u = App\Models\User::where('email','do.lhr@etpb.gov.pk')->first();
$u->forceFill(['password' => Hash::make('Etpb@2026#Change'), 'force_password_change' => true])->save();
```

### Starting over with clean data

This **wipes everything** and rebuilds from scratch:

```bash
cd back-end
php artisan migrate:fresh --seed
```

---

## For development

If you are editing the styling and want changes to appear instantly:

```bash
cd back-end
npm run dev
```

Leave that running, and use `http://localhost:8000` after starting:

```bash
php artisan serve
```

Otherwise, after any CSS change:

```bash
npm run build
```

### Running the tests

```bash
cd back-end
php artisan test
```

These run against a separate database (`etpb_regularization_test`) and will not
touch your real data.

---

## What lives where

```
proj_regular/
├── ROADMAP.md        ← the plan, and the questions I still need answered
├── README.md         ← technical overview
├── RUNNING.md        ← this file
├── front-end/        ← the screens (Blade) and Tailwind assets
├── back-end/         ← Laravel application
│   └── storage/logs/ ← error log
└── database/         ← schema and seed data reference copies
```
