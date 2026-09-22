# START HERE — Clone on another laptop (ready for demo)

Follow these steps **in order**. Do not skip MySQL.

## Prerequisites (install once)

1. **XAMPP** with PHP 8.3+ and MySQL  
2. **Composer**  
3. **Node.js 20+**  
4. Start **MySQL** in the XAMPP Control Panel  

## Setup (one command)

```bash
git clone https://github.com/imranshabbir-developer/etpb-regularization.git
cd etpb-regularization
```

Double-click **`setup-laptop.bat`**  
(or run: `powershell -ExecutionPolicy Bypass -File .\setup-laptop.ps1`)

That script will:

- copy `.env.example` → `back-end/.env`
- create MySQL database `etpb_regularization`
- `composer install` + `npm install`
- `php artisan key:generate`
- **`php artisan migrate:fresh --seed`** ← full schema + all accounts + all demo cases
- `npm run build` (CSS + Chart.js dashboards)
- verify seed counts
- print login details

If MySQL root has a password, put it in `back-end/.env` as `DB_PASSWORD=...` and re-run the script.

## Run

```bash
cd back-end
php artisan serve --port=8000
```

Open: **http://127.0.0.1:8000**

## Login (demo)

**All officers — password:** `Etpb@2026#Change`

| Email | Role |
|---|---|
| `chairman@etpb.gov.pk` | Chairman (charts + reports) |
| `secretary@etpb.gov.pk` | Secretary (charts + reports) |
| `admin.lhr@etpb.gov.pk` | Administrator (approvals) |
| `do.lhr@etpb.gov.pk` | District Officer |
| `accounts.lhr@etpb.gov.pk` | Accounts (Rs. 5,000) |

| Applicant | Password |
|---|---|
| `demo.applicant@example.com` | `Demo#Portal2026` |
| `imran.shabbir@example.com` | `Imran@Portal2026` |

Full list: [`ACCOUNTS.md`](ACCOUNTS.md)  
Officer walkthrough: [`docs/DEMO_FLOW_GUIDE.md`](docs/DEMO_FLOW_GUIDE.md)

## Reload everything later

```bash
cd back-end
php artisan migrate:fresh --seed --force
php tools/verify-seed.php
```

## What is already included in seed

- 16 migrations → full schema  
- 9 officer accounts (incl. Secretary) + 3 public applicants  
- ~26 applications across workflow stages  
- SLA breach examples for Chairman reports  
- Charts load after `npm run build` (done by setup script)

You should **not** need to invent data by hand before the demo.
