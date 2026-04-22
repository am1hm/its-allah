# `seed_fatihah.py` Explanation

This file explains what `innahu_allah/etl/seed_fatihah.py` does and why it exists.

## Purpose

`seed_fatihah.py` is the **initial ETL seed script** for the project.

It loads a small, controlled dataset (Al-Fatihah vertical slice) into the database so the full flow can be tested:

1. ingest data,
2. store provenance,
3. apply review states,
4. render in web app,
5. export approved content.

This matches the plan’s “start with Al-Fatihah first” strategy.

## Why this script was used

Before integrating all external live sources, we needed a deterministic dataset to validate:

- schema correctness,
- relationships between tables,
- admin review workflow behavior,
- approved-only export logic.

So this script is intentionally a **bootstrap ETL**, not the final live ingestion pipeline.

## What it inserts

Inside `seed_fatihah()` the script inserts into these tables:

- `allah_names`
- `quran_verses` (Surah Al-Fatihah verses)
- `name_verse_links`
- `tafsir_entries`
- `hadiths`
- `name_hadith_links`
- `scholarly_commentary`
- `father_narrative`

This provides enough linked data to test all major UI sections and export output.

## Important implementation details

### 1) Idempotent behavior

The script uses `INSERT ... ON CONFLICT` for key tables.  
That means re-running the seed does not blindly duplicate canonical rows (especially names/verses/links).

### 2) Provenance metadata

Each inserted record carries traceability fields such as:

- `source_ref` (e.g., `fatihah_seed`)
- `source_url` (e.g., `internal_seed`)
- `parser_version` (e.g., `v1`)
- `source_hash` (SHA-256 hash)
- `ingestion_timestamp`

This supports auditability and aligns with project governance.

### 3) Hashing strategy

`_hash_text()` computes a SHA-256 digest for record signatures.  
This helps identify data identity/version and is used in uniqueness/provenance workflows.

### 4) UTC timestamps

`_now()` writes timezone-aware UTC ISO timestamps, making logs consistent across environments.

### 5) Review-state defaults

Most ingested entries start as `pending` by schema default, fitting the review-first workflow.

## Data content currently included

The seed currently includes:

- 5 core names relevant to Al-Fatihah context (`الله`, `الرحمن`, `الرحيم`, `الرب`, `المالك`)
- 7 verses of Surah Al-Fatihah
- name-to-verse links with contextual notes (explicit vs derived)
- sample tafsir rows for multiple scholars
- one core hadith (Tirmidhi 3507) with grade metadata
- sample scholarly commentary and father narrative entries

## How it is used

Run via CLI:

- `uv run python -m innahu_allah.cli init-db`
- `uv run python -m innahu_allah.cli seed-fatihah`

After this, you can:

- open the private beta pages,
- review/approve content in admin,
- export approved content.

## Final note

`seed_fatihah.py` is a **foundation ETL module** for system validation.
It should later be complemented by live-source ETL modules (Quran APIs, tafsir APIs, hadith APIs, and manual scholarly ingestion) while keeping the same review and provenance standards.
