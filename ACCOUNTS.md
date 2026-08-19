# Login accounts

Every account that can sign in to the portal, what it is for, and what you will
see when you use it.

**Portal:** <http://localhost:8080>

Each password below was checked by actually signing in and loading the
dashboard, not copied from a seeder. If one stops working, re-run the seeders
(see [Recreating them](#recreating-them)).

> **These are commissioning credentials, not production ones.** They are written
> down here, and therefore live in the repository, precisely because they are
> meant to be replaced. Before the system carries a single real case, change
> every password and delete or disable the accounts nobody needs.

---

## Officers

All eight use the **same password**.

**`Etpb@2026#Change`**

| Email | Role | District | What they do |
|---|---|---|---|
| `admin@etpb.gov.pk` | System Administrator | — | Everything. Users, reference data, statutory settings, audit trail |
| `chairman@etpb.gov.pk` | Chairman, ETPB | — | Executive reports, remission of rent under Clause 12 |
| `admin.lhr@etpb.gov.pk` | Administrator | Lahore | **Approves regularization**, within one month and with reasons — Clause 3(ii)(d) |
| `do.lhr@etpb.gov.pk` | District Officer | Lahore | **Assesses and fixes the rent** — Clause 10. The busiest desk in the system |
| `da.lhr@etpb.gov.pk` | Dealing Assistant | Lahore | Files applications for people who come to the counter |
| `accounts.lhr@etpb.gov.pk` | Accounts Officer / Cashier | Lahore | **Confirms the Rs. 5,000 deposit** and flips the status to `PAID` |
| `legal.lhr@etpb.gov.pk` | Legal Officer | Lahore | Court cases and stay orders |
| `audit@etpb.gov.pk` | Auditor | — | Read-only, across everything |

A District Officer sees only their own district. The Chairman, the System
Administrator and the Auditor see every district.

---

## Members of the public

Applicants, not officers. Use these to show the citizen-facing side without
registering an account first.

| Email | Password | Who | What you will see |
|---|---|---|---|
| `imran.shabbir@example.com` | `Imran@Portal2026` | **Imran Shabbir s/o Shabbir Hussain**, Awan Town, Lahore | Has not applied yet — the onboarding a first-time visitor meets, and the six-step wizard from the beginning |
| `demo.applicant@example.com` | `Demo#Portal2026` | Demo Applicant, Model Town, Lahore | A **regularized** case: rent fixed, arrears cleared, all six stages complete |
| `sohan.lal@example.com` | `Sohan#Portal2026` | Sohan Lal s/o Kishan Chand, House 22, Sant Nagar, Lahore | **Two drafts in progress** — the "continue your application" state |

Their particulars are recorded, so starting an application offers back their
name, parentage, CNIC and postal address rather than asking for them again.

Anyone can also create their own account at
[`/register`](http://localhost:8080/register).

---

## Signing in

Officer accounts on this machine go straight to their dashboard. On a **fresh
installation** they are forced to set a new password at first sign-in — that is
`UserSeeder`'s doing and has not been weakened. It is switched off here only so
that a demonstration is never interrupted. To put it back:

```bash
cd back-end
php artisan tinker --execute="DB::table('users')->whereIn('email',['admin@etpb.gov.pk','chairman@etpb.gov.pk','admin.lhr@etpb.gov.pk','do.lhr@etpb.gov.pk','da.lhr@etpb.gov.pk','accounts.lhr@etpb.gov.pk','legal.lhr@etpb.gov.pk','audit@etpb.gov.pk'])->update(['force_password_change'=>true]);"
```

Applicants are never forced to change, because a member of the public chooses
their own password when they register.

**The login route is rate limited to ten attempts a minute.** If you are trying
several accounts in quick succession and the form stops responding, that is the
portal defending itself — wait a minute and carry on.

---

## A five-minute route through the system

Sign in as each of these in turn to see one case travel end to end:

1. **`imran.shabbir@example.com`** — file an application. Six steps, then record the Rs. 5,000 pay order.
2. **`accounts.lhr@etpb.gov.pk`** — confirm the deposit. Nothing moves until this is done; that is the whole point of the `PENDING` / `PAID` distinction.
3. **`do.lhr@etpb.gov.pk`** — scrutiny, site inspection, then assess the rent from the FBR, DC and comparable rates. Issue the notice; the fifteen-day objection window and the sixty-day assessment clock both start here.
4. **`admin.lhr@etpb.gov.pk`** — approve, with reasons, inside the month.
5. **`chairman@etpb.gov.pk`** — *Reports → At a glance* for the one-page position, in PDF, Word or Excel.

---

## Recreating them

All accounts are seeded, so a fresh clone has them:

```bash
cd back-end
php artisan migrate --seed
```

- `UserSeeder` — the eight officer accounts
- `ApplicantAccountSeeder` — the public accounts, with their particulars
- `DemoDataSeeder` *(optional)* — 27 applications across 7 districts, spread over every stage of the workflow

`ApplicantAccountSeeder` is safe to run again at any time. It matches on email
and CNIC, updates rather than duplicating, never resets a password someone has
since changed, and never touches an applicant record that an application already
points at — those particulars belong to a case on the file, not to a seeder.

`sohan.lal@example.com` predates that seeder and is not recreated by it. On a
fresh installation his two drafts will not exist.

---

*Accounts verified against the running portal on 19 August 2026.*
