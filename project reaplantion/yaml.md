# `source_registry.yaml` Explanation

This document explains what was implemented in `data/source_registry.yaml` and why.

## What this file is

`source_registry.yaml` is the project’s **data governance contract**.  
It defines:

- which sources are trusted,
- their priority order,
- review/publishing rules,
- and the minimum metadata every ingested record must carry.

The goal is to make Islamic content ingestion **traceable, reviewable, and safe to publish**.

## What was implemented and why

### 1) Top-level identity

- `version: 1`
- `project: innahu_allah`

**Why:** Adds basic versioning and project identity so the registry can evolve safely over time.

### 2) Governance block

```yaml
governance:
  language_support:
    arabic_font_primary: Tajawal
    english_support: true
  publication_rule: approved_only
  methodology: ahl_al_sunnah_wal_jamaah
  doctrinal_reference:
    - title: "Al-Qawa'id al-Muthla"
      author: "Muhammad ibn Salih al-Uthaymin"
```

**Why this exists:**

- **Bilingual policy** was added to match your requirement for Arabic + English.
- **`Tajawal`** is pinned as primary Arabic UI/body font for consistency.
- **`approved_only`** enforces your core safety rule: nothing appears publicly before review.
- **Methodology + doctrinal reference** anchors interpretation to your chosen creed framework and primary reference.

### 3) Source priority matrix (`source_priority`)

This section defines trust hierarchy per content type.

#### Quran text
- `kfgqpc` as `primary`
- `tanzil` as `secondary_cross_check`
- `kfgqpc_unicode` as `display_support`

**Why:** preserves a canonical Quran source while still enabling validation and rendering support.

#### Allah names
- `kabdeveloper_99` as `seed_dataset`
- `ibn_uthaymin_verified_list` as `final_reconciliation`

**Why:** seed data can bootstrap quickly, but final list must be scholar-validated.

#### Tafsir
- API-first (`quran_foundation_api`, `alquran_cloud`)
- fallback (`tafsir_api_spa5k`)
- manual/reference layer (`shamela`, `altafsir`)

**Why:** balances automation speed with classical-source verification.

#### Hadith
- `sunnah_api` as primary pull source
- `dorar` for grading verification
- fallback curated datasets for resilience

**Why:** preserves grading quality and prevents single-source dependency.

#### Scholarly commentary
- `shamela` primary
- `openiti` secondary
- `waqfeya` for print validation

**Why:** supports both digital extraction and printed-edition cross-checking.

### 4) Policy enforcement (`policies`)

```yaml
policies:
  ingestion:
    default_verification_status: pending
    require_source_metadata: true
    require_ingestion_timestamp: true
  review:
    states: [pending, approved, rejected, approved_with_edit]
  weak_hadith:
    default_action: include_with_warning
    must_be_reviewed: true
```

**Why this was chosen:**

- Every ingest starts at **`pending`** to enforce your father-review workflow.
- Requiring source metadata + timestamp guarantees provenance/auditability.
- Review states match admin actions implemented in the app.
- Weak hadith are not silently published; they require explicit review and warning handling.

## Practical result

Because of this file, the rest of the system (DB schema, ETL, admin review, exports) can follow one consistent governance standard:

- ingest with provenance,
- hold as pending,
- review and approve,
- publish approved-only.
