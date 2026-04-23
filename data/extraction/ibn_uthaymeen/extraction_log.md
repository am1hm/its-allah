# Extraction Log — Ibn Uthaymeen Book

## Source
- Source file: `Attributes_of_Allah_by_Ibn_Uthaymeen.md`
- Extraction target folder: `data/extraction/ibn_uthaymeen/`
- Working files:
  - `raw_pages.json`
  - `clean_pages.json`
  - `review_log.md`

## Metadata
- Book ID: `qawaid_muthla_ibn_uthaymeen`
- Book title: `القواعد المثلى في صفات الله وأسمائه الحسنى`
- Author: `محمد بن صالح العثيمين`

## Extraction Rules
1. Use `<!-- page X -->` markers as the only page boundaries.
2. `raw_pages.json` stores verbatim page text.
3. `clean_pages.json` stores cleaned text only (remove image lines and obvious OCR noise).
4. Keep all records in `pending` status until review.
5. Never overwrite source markers.

## Quality Labels
- `high`: readable and mostly clean
- `medium`: readable with manageable OCR noise
- `low`: heavy OCR issues, likely requires manual rewrite

## Progress Tracker
- Total detected pages: `113`
- Extracted to raw JSON: `113`
- Cleaned pages completed: `113`
- Pending review pages: `113`
- Approved pages: `0`
- Rejected pages: `0`

## Validation Checklist
- [ ] Page count in JSON matches marker count
- [ ] No duplicate page numbers
- [ ] No missing page numbers in sequence
- [ ] Every record has required fields
- [ ] Every record starts as `verification_status: pending`

## Notes
- Use this file to record each extraction batch and issues found.
