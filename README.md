# Regularization of Possession — ETPB

Web application for processing applications by existing occupants of urban
evacuee trust properties to be treated as tenants under **Clause 3(ii) of the
Scheme for the Management and Disposal of Urban Evacuee Trust Properties, 1977**.

The full design rationale, schema reasoning, statutory mapping and roadmap are in
[`MASTER_PLAN.md`](MASTER_PLAN.md). This file covers how to run it.

---

## Running it

The application is served on **its own port**, not under `htdocs`:

```
http://localhost:8080
```

Start Apache and MySQL from the XAMPP Control Panel. Apache picks up
`deploy/apache-etpb.conf`, which is included from `httpd.conf`.

If you prefer the built-in server instead of Apache:

```bash
cd back-end
php artisan serve --port=8000
```

### Commissioning accounts

Every account below is seeded with the same password and is forced to change it
at first sign-in.

| Email | Role |
|---|---|
| `admin@etpb.gov.pk` | System Administrator |
| `chairman@etpb.gov.pk` | Chairman, ETPB |
| `admin.lhr@etpb.gov.pk` | Administrator |
| `do.lhr@etpb.gov.pk` | District Officer |
| `da.lhr@etpb.gov.pk` | Dealing Assistant |
| `accounts.lhr@etpb.gov.pk` | Accounts Officer |
| `legal.lhr@etpb.gov.pk` | Legal Officer |
| `audit@etpb.gov.pk` | Auditor (read-only) |

**Password: `Etpb@2026#Change`**

These are commissioning credentials, not production ones. Delete or disable the
unused accounts before the system carries real cases.

---

## Environment

Installed and configured as part of this build:

| Component | Version | Note |
|---|---|---|
| PHP | **8.4.24** (TS, VS17, x64) | Upgraded from 8.0.30, which is past end of life. The previous install is preserved at `C:\xampp\php80-backup`. |
| Laravel | **13.26.1** | |
| Apache | 2.4.58 (VS17) | `php8apache2_4.dll` from the new build; `LoadModule` line unchanged. |
| MySQL | 8.0.46 | `etpb_regularization` on `127.0.0.1:3306` |
| Composer | 2.10.2 | `C:\xampp\php\composer.phar`, wrapper at `composer.bat` |

PHP extensions enabled: `bcmath` (built in), `gd`, `intl`, `zip`, `sodium`,
`curl`, `exif`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `opcache`.

> **Rolling back the PHP upgrade**, if ever needed: stop Apache, rename
> `C:\xampp\php` aside, rename `C:\xampp\php80-backup` back to `C:\xampp\php`,
> restart. The Apache `LoadModule` path does not change.

---

## Layout

The three folders that existed at the start are all used:

```
proj_regular/
├── MASTER_PLAN.md          design, statutory mapping, roadmap, open questions
├── README.md               this file
├── deploy/
│   └── apache-etpb.conf    vhost on :8080, included from httpd.conf
├── database/               reference copies of schema and seeds
├── back-end/               Laravel application
│   ├── app/
│   │   ├── Services/       the domain rules (see below)
│   │   ├── Models/         43 Eloquent models
│   │   ├── Http/
│   │   └── Providers/
│   ├── database/
│   │   ├── migrations/     15 migrations, 62 tables
│   │   └── seeders/        reference data, geography, RBAC, users
│   ├── public/             DOCUMENT ROOT
│   │   └── assets/         junction -> ../../front-end/assets
│   ├── storage/            uploads, logs — outside the document root
│   └── tests/              45 tests
└── front-end/
    ├── assets/css/etpb.css design system
    └── views/              Blade templates (configured in config/view.php)
```

`front-end/views` is registered as Laravel's first view path and
`back-end/public/assets` is a directory junction to `front-end/assets`, so both
folders are edited in place and served without a build step.

---

## Where the rules live

Four services carry the statutory logic. They are the parts worth reading first.

| Service | What it decides | Clause |
|---|---|---|
| `AreaConversionService` | Kanal / Marla / Sarsai / Acre → square feet, with a frozen trace of the factors used | Pakistani revenue measurement |
| `EligibilityService` | Whether possession beats the cut-off, and which of three dates arrears run from | 3(ii)(a), 3(ii)(b) |
| `RentAssessmentService` | The year-by-year rent schedule and the 8% per annum enhancement | 10, 11(i), 11(ii) |
| `ArrearsService` | The ledger, receipts, instalments (≤24) and whether approval may proceed | 3(ii)(b), 12, 13 |
| `WorkflowService` | Every state transition and the guard the Scheme imposes on it | throughout |

No statutory number is hard-coded. All of them live in the `settings` table with
effective-from dating, so an amending SRO is absorbed by inserting a row.

### Running the tests

```bash
cd back-end
php artisan test
```

45 tests, 106 assertions. They run against a separate MySQL database
(`etpb_regularization_test`) rather than SQLite, so they exercise the engine the
application actually ships on.

---

## Two decisions that need a written ETPB ruling

Both are implemented as configurable policy and both change money.

1. **Is the 8% per annum enhancement simple or compound?** Clause 11(ii) does not
   say. Over 24 years compound gives about **6.34×** the base rent and simple
   about **2.92×**. Currently set to `COMPOUND`; the method used is stamped onto
   every schedule generated.

2. **Is a Marla 272.25 sqft or 225 sqft?** The revenue standard and the urban
   housing-scheme standard differ by 21%, and that difference lands directly in
   the assessed rent. Both profiles are seeded, selectable per district, and the
   profile used is frozen onto each application.

The remaining open questions are in [`MASTER_PLAN.md`](MASTER_PLAN.md) §14 —
including the fact that `basic requirements.txt` is still an empty file.

---

## Before this carries real cases

- Replace the self-signed certificate and force HTTPS.
- Set `APP_DEBUG=false` and `APP_ENV=production` in `back-end/.env`.
- Set a MySQL root password other than the development one, and give the
  application its own least-privilege database user.
- Bind or remove phpMyAdmin.
- Delete the unused commissioning accounts.
- Rehearse a backup restore, not just a backup.
