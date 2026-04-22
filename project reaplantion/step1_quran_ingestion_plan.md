# Step 1 Plan — Real Quran Ingestion + Tanzil Cross-Check (Laravel)

This is the exact implementation plan for Step 1 of the production phase.

## Goal

Load the **real canonical Quran text** into Laravel DB from the primary source, then run a **secondary comparison check** (Tanzil) and generate a mismatch report for review.

## Scope of Step 1

Included:
- Real ingestion command for Quran verses (primary source)
- Normalization and deterministic upsert logic
- Provenance metadata capture
- Tanzil cross-check command
- Diff report output (file + DB summary)

Not included in this step:
- Tafsir ingestion
- Hadith ingestion
- UI changes beyond minimal visibility of validation results

## Source Policy (applied exactly)

- Primary text source: **KFGQPC / King Fahd Complex**
- Secondary comparison: **Tanzil**
- Display-support source remains separate (not used as canonical ingest)
- Canonical recitation constraint: **Riwayah Hafs 'an Asim only**

### Canonical recitation enforcement (hard rule)

- Every imported canonical verse row is stamped with:
  - `qiraah = asim`
  - `riwayah = hafs`
- Import command accepts only records tagged/mapped to Hafs 'an Asim.
- Any non-Hafs input is skipped and counted in run diagnostics.
- Comparison reports include recitation metadata to avoid mixing inputs.

## Exact tasks

## 1) Add ingestion tracking table

Create migration for `quran_ingestion_runs`:
- `id`
- `source_id` (`kfgqpc`, `tanzil`)
- `run_type` (`import`, `compare`)
- `started_at`, `completed_at`
- `status` (`running`, `completed`, `failed`)
- `rows_processed`
- `rows_inserted`
- `rows_updated`
- `mismatch_count`
- `output_report_path`
- `error_message`
- timestamps

Purpose:
- audit trail for operational runs
- reproducible import history

## 2) Add verse comparison table

Create migration for `quran_verse_diffs`:
- `id`
- `surah_number`
- `ayah_number`
- `kfgqpc_text`
- `tanzil_text`
- `normalized_kfgqpc`
- `normalized_tanzil`
- `diff_type` (`exact_match`, `normalized_match`, `mismatch`)
- `notes`
- timestamps

Purpose:
- persistent diff records for scholar/technical review

## 3) Implement Quran text normalizer utility

Create service: `app/Services/QuranTextNormalizer.php`

Normalization rules (for comparison only):
- trim surrounding whitespace
- collapse repeated internal spaces
- normalize Arabic punctuation spacing
- normalize optional tatweel
- keep original text unchanged in stored canonical verse fields

Important:
- We **do not overwrite canonical source text** with normalized text.
- Normalized values are only used to classify comparison outcome.

## 4) Implement primary import command

Create command: `innahu:quran-import-primary`

Behavior:
- read input dataset (JSON/CSV adapter)
- validate required fields per row:
  - `surah_number`, `ayah_number`, `ayah_text`
- upsert into `quran_verses` on unique (`surah_number`, `ayah_number`)
- set/update provenance fields:
  - `source_ref = kfgqpc`
  - `source_url`
  - `ingestion_timestamp`
  - `parser_version`
  - `source_hash`
- collect counts for run summary
- write ingestion run record in `quran_ingestion_runs`

Idempotency:
- rerunning import must not duplicate verses
- only changed rows are updated

## 5) Implement Tanzil comparison command

Create command: `innahu:quran-compare-tanzil`

Behavior:
- load Tanzil verse dataset by (`surah_number`, `ayah_number`)
- fetch existing canonical verses from DB
- for each verse:
  - compare exact text
  - compare normalized text
  - classify as:
    - `exact_match`
    - `normalized_match`
    - `mismatch`
- write row to `quran_verse_diffs`
- generate machine-readable report:
  - `exports/quran_diff_report.json`
- store run summary in `quran_ingestion_runs`

## 6) Add safety checks

In comparison command:
- fail run if canonical verse missing for reference key
- report counts:
  - total compared
  - exact matches
  - normalized matches
  - mismatches
- non-zero exit code only when system error occurs
  - mismatches alone should not crash command; they should be reported

## 7) Add minimal operator documentation

Create doc:
- `project reaplantion/step1_runbook.md`

Contents:
- expected input format
- required command order
- command examples
- where reports are saved
- how to interpret mismatch classes

## 8) Execute and verify

Run order:
1. `php artisan migrate`
2. `php artisan innahu:quran-import-primary --source=<path-or-url>`
3. `php artisan innahu:quran-compare-tanzil --source=<path-or-url>`

Verify:
- `quran_verses` populated from real source
- `quran_ingestion_runs` has completed records
- `quran_verse_diffs` has comparison records
- `exports/quran_diff_report.json` exists and is readable

## Definition of done

Step 1 is complete when all are true:
- real canonical Quran dataset is ingested into DB
- import is idempotent (rerun safe)
- Tanzil comparison runs successfully
- mismatch report is generated and persisted
- provenance fields are filled on imported verses
- operator runbook exists and matches actual commands

## Deliverables from Step 1

- New migrations:
  - ingestion runs
  - verse diffs
- New services:
  - Quran normalizer
- New artisan commands:
  - `innahu:quran-import-primary`
  - `innahu:quran-compare-tanzil`
- New report:
  - `exports/quran_diff_report.json`
- New documentation:
  - `project reaplantion/step1_runbook.md`
