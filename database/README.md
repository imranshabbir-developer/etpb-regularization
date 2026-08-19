# database/

Reference copies of the schema and its seed data. These are **generated
artefacts**, not the source of truth.

| File | Contents |
|---|---|
| `schema.sql` | Full DDL for all 62 tables, dumped from `etpb_regularization`. |
| `seed-reference-data.sql` | Geography, RBAC, statutory settings, document types, rate sources and unit conversion factors. |

## The source of truth is the migrations

Schema changes are made in `back-end/database/migrations/`, never by editing
`schema.sql`. To rebuild a database from nothing:

```bash
cd back-end
php artisan migrate:fresh --seed
```

To refresh the exports in this folder after a schema change:

```bash
mysqldump -h 127.0.0.1 -u root -p --no-data --skip-comments --routines \
  --set-gtid-purged=OFF etpb_regularization > database/schema.sql
```

## Migration order

| # | Migration | Adds |
|---|---|---|
| 1 | `create_rbac_tables` | roles, permissions, user columns, login attempts |
| 2 | `create_geography_tables` | province → division → district → tehsil → mouza, offices |
| 3 | `create_reference_tables` | settings, unit conversion, document types, rate sources |
| 4 | `create_applicant_property_tables` | applicants, properties, areas, geo tags |
| 5 | `create_application_tables` | applications, possession details, status history |
| 6 | `create_document_fee_tables` | evidence documents, fee payments, attachments |
| 7 | `create_assessment_tables` | assessment rounds, rate inputs, comparables, decisions, rent schedules |
| 8 | `create_notice_objection_tables` | notices, objections, hearings, objection decisions |
| 9 | `create_arrears_tables` | ledger, receipts, instalment plans, remissions |
| 10 | `create_occupant_litigation_tables` | competing occupant offers, litigation register |
| 11 | `create_approval_outcome_tables` | approvals, nominees, tenancy agreements, orders |
| 12 | `create_enforcement_system_tables` | penalties, ejectment, audit log, notifications, report snapshots |

Laravel's own `users`, `cache` and `jobs` migrations run first.

## Conventions

- Money is `DECIMAL(15,2)`, area is `DECIMAL(18,4)`. Never `FLOAT` — an area
  feeds a rent figure and `272.25 × 3` has to be exactly `816.75`.
- Foreign keys are `RESTRICT`; records are soft-deleted, never removed. These are
  land records.
- `audit_logs` is append-only. Nothing in the application updates or deletes it.
- The possession cut-off is deliberately **not** a `CHECK` constraint. Clause
  3(ii)(a) lets the Board notify a later date, so it is an application rule read
  from `settings`.
