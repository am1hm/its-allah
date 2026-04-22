# Innahu Allah — Full Progress So Far

This document explains **everything completed so far**, what was implemented, why it was done, and the concrete results.

---

## 1) Initial project bootstrap (from empty workspace)

At the start, the workspace was effectively empty for this project, so a full base implementation was created.

### What was added

- Python prototype structure:
  - `innahu_allah/` package
  - `db/schema.sql`
  - `data/source_registry.yaml`
  - ETL and exporter modules
  - FastAPI web/admin prototype
- Project docs:
  - `README.md`
  - explanation docs in `project reaplantion/`

### Why

- To implement the approved plan end-to-end quickly.
- To validate flow before production hardening:
  - ingest -> review -> approved-only publish/export.

---

## 2) Governance and source hierarchy implemented

### File

- `data/source_registry.yaml`

### What was implemented

- Source trust hierarchy by domain:
  - Quran text (KFGQPC primary, Tanzil cross-check)
  - Names
  - Tafsir
  - Hadith
  - Scholarly commentary
- Governance rules:
  - `publication_rule: approved_only`
  - review states
  - ingestion provenance requirements
- Language policy:
  - bilingual Arabic/English
  - Arabic primary font `Tajawal`

### Why

- To make ingestion auditable and avoid mixing trusted/untrusted data.
- To enforce scholarly review before publication.

---

## 3) Canonical schema + ETL prototype

### Files

- `db/schema.sql`
- `innahu_allah/db.py`
- `innahu_allah/etl/seed_fatihah.py`

### What was implemented

- Full relational schema for planned entities:
  - `allah_names`, `quran_verses`, `name_verse_links`
  - `tafsir_entries`, `hadiths`, `name_hadith_links`
  - `scholarly_commentary`, `father_narrative`
  - `review_audit_log`
- Provenance fields added to content tables:
  - source refs, timestamps, parser version, hash
- Idempotent seed ETL for Al-Fatihah slice.

### Why

- To test all relationships and workflows with deterministic seed data first.
- To ensure reruns are safe (no uncontrolled duplication).

---

## 4) Export pipeline prototype

### Files

- `innahu_allah/exporters/approved_export.py`
- `innahu_allah/cli.py`

### What was implemented

- JSON exporter:
  - outputs approved-safe content structure
- Markdown exporter:
  - manuscript-style output for book workflow
- CLI commands for:
  - init DB
  - seed
  - demo approvals
  - export approved outputs

### Why

- Your plan requires one data source feeding both website and book.

---

## 5) Private review web prototype

### Files

- `innahu_allah/web/app.py`
- `innahu_allah/web/templates/*`
- `innahu_allah/web/static/styles.css`

### What was implemented

- Public reading pages (home, name page, mushaf order)
- Admin review panel:
  - approve / approve-with-edit / reject
  - reviewer notes
  - queue and progress stats
- Father narrative insertion
- Approved-only filtering on publication views
- Bilingual direction and `Tajawal` Arabic styling

### Why

- To implement the core scholarly gate before any public release.

---

## 6) Switched from requirements.txt to uv

### Files

- Added: `pyproject.toml`
- Removed: `requirements.txt`
- Updated: `README.md`

### What was done

- Converted dependency management to `uv`
- Synced environment via `uv sync`

### Why

- Per your explicit instruction: use `uv`, not `requirements.txt`.

---

## 7) Added project explanation docs

### Files in `project reaplantion/`

- `yaml.md`
- `approved_content.md`
- `seed_fatihah.md`
- `approved_export.md`

### What was done

- Documented exactly what each core file/module does and why.

### Why

- You requested clear explanation artifacts in Markdown.

---

## 8) Laravel migration for production-style backend

After your request to make backend/admin in Laravel best-practice style, a Laravel implementation was added.

### Laravel app path

- `laravel-app/`

### Major backend implementation

- Auth:
  - login/logout, session auth
- Role-based admin:
  - `users.role`
  - admin-only middleware
- Full content schema migrations for project domain
- Admin dashboard and review workflow
- Public pages and routes
- Export service + artisan command
- Seed artisan command for bootstrapping

### Key files

- `laravel-app/routes/web.php`
- `laravel-app/app/Http/Controllers/AuthController.php`
- `laravel-app/app/Http/Controllers/AdminController.php`
- `laravel-app/app/Http/Controllers/PublicController.php`
- `laravel-app/app/Http/Middleware/AdminOnly.php`
- `laravel-app/app/Services/ApprovedExportService.php`
- `laravel-app/app/Console/Commands/SeedFatihahCommand.php`
- `laravel-app/app/Console/Commands/ExportApprovedCommand.php`
- Blade views under `laravel-app/resources/views/`

### Why

- You requested PHP/Laravel + best-practice direction for backend/admin.

---

## 9) GitHub upload completed

### What was done

- Initialized git in project
- Linked remote:
  - `https://github.com/am1hm/its-allah.git`
- Merged remote history
- Pushed full project to `main`

### Result

- Upload succeeded.

---

## 10) Step 1 production implementation (real Quran ingestion phase)

You requested moving from prototype to real-source ingestion and enforcing **Hafs 'an Asim only**.

### Planning + runbook docs

- `project reaplantion/step1_quran_ingestion_plan.md`
- `project reaplantion/step1_runbook.md`

### Hard canonical rule adopted

- Project canonical constraint:
  - `qiraah = asim`
  - `riwayah = hafs`

### Step 1 DB changes

- `laravel-app/database/migrations/2026_04_22_210000_add_qiraah_riwayah_to_quran_verses.php`
- `laravel-app/database/migrations/2026_04_22_210100_create_quran_ingestion_runs_table.php`
- `laravel-app/database/migrations/2026_04_22_210200_create_quran_verse_diffs_table.php`

### Step 1 services/commands

- `laravel-app/app/Services/QuranTextNormalizer.php`
- `laravel-app/app/Console/Commands/QuranImportPrimaryCommand.php`
- `laravel-app/app/Console/Commands/QuranCompareTanzilCommand.php`

### Real-source capability added

- Primary import supports real URL:
  - KFGQPC mirror JSON
- Tanzil compare supports:
  - live download mode (`--download-live`)
  - txt parsing with aya numbers

---

## 11) Real-source datasets used

### KFGQPC mirror (Hafs)

- `https://raw.githubusercontent.com/thetruetruth/quran-data-kfgqpc/main/hafs/data/hafsData_v18.json`

### Tanzil live endpoint (v1.0 form download)

- `https://tanzil.net/pub/download/v1.0/download.php`

---

## 12) Live execution results

## 12.1 Primary real import result

Command run:
- `php artisan innahu:quran-import-primary --source-url=... --source-format=json`

Result:
- `processed=6236`
- `inserted=6229`
- `updated=7`
- `skipped=0`

Interpretation:
- Full Quran verse corpus imported from real source pipeline.

## 12.2 First live compare result (before normalization v2)

Result:
- `compared=6236`
- `exact=0`
- `normalized=1036`
- `mismatch=5200`

Interpretation:
- Massive orthographic/encoding variance remained between source encodings.

## 12.3 Normalization v2 implemented + rerun

Enhancements:
- stronger Uthmani mark handling
- ayah number/decorative mark removal
- additional variant normalization
- normalized values added to report entries

Final compare result:
- `compared=6236`
- `exact=0`
- `normalized=5832`
- `mismatch=404`
- `missing_canonical=0`

Report file:
- `exports/quran_diff_report.json`

Interpretation:
- Large improvement.
- Remaining 404 require further classification (likely recurring orthographic patterns + smaller subset of true anomalies).

---

## 13) Current status summary

### Completed

- Full prototype pipeline
- Laravel backend/admin migration
- GitHub upload
- Real-source Step 1 infrastructure
- Real live import
- Real live compare with improved normalization

### Not completed yet

- Full mismatch pattern clustering for remaining 404
- Tafsir real-source ingestion phase
- Hadith real-source ingestion phase
- Post-Step1 editorial QA loop on full diff taxonomy

---

## 14) Recommended immediate next task

Implement **Mismatch Clustering v1** on the 404 remainder:

- auto-group by normalized difference signature
- output grouped report
- label buckets:
  - expected orthographic variant
  - uncertain (needs manual review)
  - probable true text divergence

This will complete Step 1 review quality and allow formal sign-off before Step 2 (Tafsir ingestion).
