# Step 1 Runbook — Quran Import + Tanzil Compare

This runbook executes Step 1 with the enforced constraint:

- `qiraah = asim`
- `riwayah = hafs`

Only Hafs 'an Asim data is accepted.

## Input format

JSON or CSV records must contain at minimum:

- `surah_number`
- `ayah_number`
- `ayah_text`

Recommended fields:

- `surah_name`
- `ayah_text_simple`
- `qiraah` (must be `asim`)
- `riwayah` (must be `hafs`)

## Command order

From project root:

1. Run migrations:
   - `cd laravel-app && php artisan migrate`

2. Import canonical primary source (KFGQPC):
   - `php artisan innahu:quran-import-primary --source=../data/samples/kfgqpc_hafs_sample.json --source-url=https://qurancomplex.gov.sa/techquran/dev`

3. Run Tanzil comparison:
   - `php artisan innahu:quran-compare-tanzil --source=../data/samples/tanzil_hafs_sample.json --source-url=https://tanzil.net/download`

## Real-source run (live)

Use these commands for real ingestion:

1. Import primary KFGQPC mirror (Hafs v18 JSON):
   - `php artisan innahu:quran-import-primary --source-url=https://raw.githubusercontent.com/thetruetruth/quran-data-kfgqpc/main/hafs/data/hafsData_v18.json --source-format=json`

2. Compare against live Tanzil Uthmani with aya numbers:
   - `php artisan innahu:quran-compare-tanzil --download-live --source-format=txt`

## Expected outputs

- DB table `quran_verses` populated/updated with canonical text
- DB table `quran_ingestion_runs` has run logs
- DB table `quran_verse_diffs` has comparison classifications
- File report:
  - `exports/quran_diff_report.json`

## Mismatch classes

- `exact_match`: exact string match
- `normalized_match`: exact after normalizer (spacing/tatweel/punctuation)
- `mismatch`: still different after normalization, or canonical verse missing

## Notes

- Mismatches do not crash comparison command; they are reported.
- System-level errors fail command with non-zero exit code.
- Non-Hafs/non-Asim rows are skipped by import and not treated as canonical.
