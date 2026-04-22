# Innahu Allah

End-to-end implementation of the "Innahu Allah" plan:

- Source governance and trust hierarchy
- Canonical SQLite schema with provenance and review states
- Idempotent ETL pipeline
- Private beta website with bilingual UI (Arabic + English)
- Reviewer workflow (approve / approve_with_edit / reject)
- Al-Fatihah vertical slice using approved-only publication filtering
- Book export pipeline (Markdown and JSON)

## Quick Start

1. Install dependencies with `uv`:
   - `uv sync`
2. Initialize database and seed data:
   - `uv run python -m innahu_allah.cli init-db`
   - `uv run python -m innahu_allah.cli seed-fatihah`
3. Run the private beta web app:
   - `uv run uvicorn innahu_allah.web.app:app --reload`
4. Open:
   - `http://127.0.0.1:8000` (public private-beta view)
   - `http://127.0.0.1:8000/admin` (review workflow)

## Project Structure

- `data/source_registry.yaml`: trust hierarchy and source matrix
- `db/schema.sql`: canonical schema with review and provenance fields
- `innahu_allah/etl/`: idempotent ETL jobs
- `innahu_allah/web/`: FastAPI + Jinja web app
- `innahu_allah/exporters/`: approved-content export pipeline
- `innahu_allah/cli.py`: operational commands

## Notes

- Arabic UI font is set to `Tajawal`.
- Publication pages enforce approved-only content for tafsir/hadith/commentary.
- All imported records default to `pending`, then move through review states.
