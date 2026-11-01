# ETPB Implementation Plan — November 2026 Meeting Changes

**Goal:** Deliver every board/secretary request without breaking workflow, reports, or role scoping.  
**Method:** Six phases. Each phase ends with a verify step before the next starts.  
**Theme source:** `global-trade-hub` brand blues / orange accent (replace Pakistan-green UI palette).

---

## Phase 1 — Remove Indigent / Widow / Orphan (everywhere)

| Layer | Action |
|-------|--------|
| Migration | Drop `applicants.is_indigent`, `is_widow`, `is_orphan` |
| Models / controllers | Stop reading/writing those fields |
| Apply form | Remove Clause-12 tick box block |
| Case file, arrears, deep report, print | Remove badges / remission copy that depends on the three flags |
| Seeders | Stop seeding those flags |
| Remission UI | Keep Clause 12 remission itself (Chairman power) — only remove the three applicant flags as eligibility tickboxes |

**Verify:** Apply applicant step has no tickboxes; case show / deep report / PDF have no Indigent/Widow/Orphan badges; migrate fresh seeds cleanly.

---

## Phase 2 — Property & possession data model + forms

| Change | Detail |
|--------|--------|
| Property number | Optional (`nullable`) |
| Property type dropdown | `OPEN_PLOT`, `RESIDENTIAL`, `COMMERCIAL`, `RESIDENTIAL_CUM_COMMERCIAL`, `OTHER` |
| Usage dropdown | Same five labels/codes |
| Label | “How big is it?” → “Area of the Property / Land” |
| Unit profile | Add **GHUNTA** factor = **1089 sqft**; default profile when unspecified → **URBAN (225 sqft/Marla)** |
| New area fields | `total_area_sqft`, `open_area_sqft` (covered already exists); compute open = total − covered when possible |
| Floors | `number_of_floors` + JSON/table `floor_wise_covered_area` |
| Khasra / Survey No. | Surface on form (column exists) |
| Geo coordinates | Mandatory on submit of property step (lat/lng) |
| Court block | Add `court_name`, `judgment_gist` (max ~100 words); keep judgment date + case no. |
| Clause 3(ii)(b) | Confirm arrears base date remains **1 July 2000** in settings/seed |

**Verify:** Property step saves optional property_no; new enums persist; Ghunta converts correctly; geo required; court fields store and show on case/deep report.

---

## Phase 3 — Evidence, staff fill-in, biometric, payment hooks

| Change | Detail |
|--------|--------|
| Document types | All `is_mandatory = false`; add **Possession Certificate (Revenue Officer / Tehsildar / Mukhtiarkar)** |
| Staff upload | Administrator + District Officer (+ DA one-window) can upload missing docs from case Documents tab |
| Biometric | Mandatory applicant thumb / biometric capture before final submit (use existing `thumb_impression_path` + gate) |
| Payment | Keep Rs. 5,000 path; stub “ETPB payment API later”; allow **payment receipt** attachment |
| Assessment | Allow upload of assessment sheet on rent-assessment screen |
| One-window | Staff can file/complete applicant data on behalf of citizen (existing DA path + widen permissions) |

**Verify:** Submit without docs still works; officer can attach docs; biometric blocks submit until captured; receipt doc type available.

---

## Phase 4 — RBAC: Chairman vs Secretary

| Role | Rule |
|------|------|
| **Chairman** | Sole account for corrections / wrong-entry fixes / modifications (new permission `records.correct`) |
| **Secretary** | View-only everywhere (`reports.*` + read apps); strip mutate permissions |
| Quotations | Support **more than 3** comparables; omit separate “monthly charges” line where required; tax included in amount |

**Verify:** Secretary cannot POST mutate routes (403); Chairman correction routes work; assessment accepts >3 comparables.

---

## Phase 5 — Theme + login + slim dashboards

| Change | Detail |
|--------|--------|
| Colors | Map `--pk-*` greens → global-trade-hub `--brand` blues + orange accent across CSS, Blade badges, charts, print letterhead accents (print may keep institutional green only if legally required — UI uses blue) |
| Login | Centered card, atmospheric background, “wow” motion; **bottom-right floating role rail** autofills email/password |
| Dashboards | Chairman / Secretary / Admin: fewer tiles, one job per section, drop clutter charts/noise |

**Verify:** No green primary in app shell; login autofill works per role; executive dashboards load lighter.

---

## Phase 6 — Illiterate-friendly first intake (8–10 fields)

Public applicant **first screen** only asks essentials, e.g.:

1. Full name  
2. Parentage  
3. CNIC  
4. Contact  
5. District / address (short)  
6. Property location (district + short address)  
7. Approximate area (optional)  
8. Date of possession  
9. Geo pin (mandatory)  
10. Biometric / thumb  

All other fields remain in the schema and are filled later by **one-window staff** sitting with the applicant.

**Verify:** Fresh applicant can create + submit draft path with minimal fields; staff can complete remaining sections.

---

## Cross-cutting sync checklist (every phase)

- [ ] Migrations + seeders  
- [ ] Apply wizard + ApplicationController / create forms  
- [ ] Case file (`applications/show`)  
- [ ] Officer queues / documents / assessment  
- [ ] Deep + print + Excel sheets  
- [ ] DashboardController payloads  
- [ ] Tests touched by guards / area conversion  
- [ ] `docs/APPLICATION_COMPLETE_CYCLE.md` if flow wording changes  

---

## Execution order (this session)

1. Write this plan ✅  
2. Phase 1 implement + verify  
3. Phase 2 migration + forms + AreaConversionService Ghunta  
4. Phase 3 docs/biometric/staff upload  
5. Phase 4 RBAC  
6. Phase 5 theme + login + dashboard slim  
7. Phase 6 minimal intake  

Payment API: **stub only** until ETPB supplies credentials.
