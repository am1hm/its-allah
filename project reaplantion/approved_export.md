# `approved_export.py` Explanation

This file explains what `innahu_allah/exporters/approved_export.py` does and why it is important.

## Purpose

`approved_export.py` is the **publication/export layer** of the project.

It converts reviewed database content into portable files used by:

- the website publication pipeline,
- editorial/book preparation workflow,
- external integrations that need clean approved data.

## Why this module exists

The project requires a strict rule: **only approved content can be published**.

The database can contain mixed states (`pending`, `approved`, `rejected`, `approved_with_edit`), so this module acts as a filter boundary to produce safe outputs.

## Functions in this file

There are two exporter functions:

1. `export_approved_json(output_path)`
2. `export_approved_markdown(output_path)`

Both return the final written output path.

---

## 1) `export_approved_json(output_path)`

### What it does

It builds a JSON payload with structured content buckets:

- `names`
- `verses`
- `tafsir_entries`
- `hadiths`
- `scholarly_commentary`
- `father_narrative`

Then writes the file in UTF-8 with pretty indentation.

### Key filtering behavior

- `tafsir_entries`: only rows where `verification_status = 'approved'`
- `hadiths`: only rows where `verification_status = 'approved'`
- `scholarly_commentary`: only rows where `verification_status = 'approved'`
- `father_narrative`: only rows where `status = 'final'`

### Why this matters

It enforces publication safety and keeps review boundaries clear.

## 2) `export_approved_markdown(output_path)`

### What it does

It produces a manuscript-style Markdown file:

- title header,
- verses ordered by surah/ayah,
- approved tafsir under each verse,
- approved scholarly commentary under each verse,
- final father narrative under each verse.

### Why this matters

It creates a print/editorial-friendly intermediate format aligned with your “book from same DB” requirement.

## Query strategy used

The exporter first selects verses linked through `name_verse_links`, then enriches each verse with related approved records.

This ensures:

- deterministic ordering (`surah_number`, `ayah_number`),
- coherent grouping by verse,
- selective inclusion based on review status.

## Encoding and file handling details

- Uses `ensure_ascii=False` for JSON so Arabic text stays readable.
- Ensures output folders exist with `mkdir(parents=True, exist_ok=True)`.
- Writes in UTF-8 for Arabic/English compatibility.

## Current scope note

This module is production-patterned, but current content comes from the seed dataset (Al-Fatihah demo slice).  
When real-source ETL is connected, this same exporter will produce real approved publication outputs without architectural changes.

## Final role in the system

`approved_export.py` is the **trusted output gateway** between internal reviewed DB data and external publishable artifacts (JSON + Markdown).
