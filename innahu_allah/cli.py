from __future__ import annotations

import argparse
from pathlib import Path

from innahu_allah.db import get_conn, init_db
from innahu_allah.etl.seed_fatihah import seed_fatihah
from innahu_allah.exporters.approved_export import export_approved_json, export_approved_markdown


def main() -> None:
    parser = argparse.ArgumentParser(description="Innahu Allah operations CLI")
    sub = parser.add_subparsers(dest="cmd", required=True)

    sub.add_parser("init-db", help="Initialize SQLite schema")
    sub.add_parser("seed-fatihah", help="Seed Al-Fatihah vertical slice data")
    sub.add_parser("export-approved", help="Export approved-only publication outputs")
    sub.add_parser("approve-demo", help="Approve a minimal demo publication slice")

    args = parser.parse_args()

    if args.cmd == "init-db":
        init_db()
        print("Database initialized.")
    elif args.cmd == "seed-fatihah":
        seed_fatihah()
        print("Al-Fatihah seed data loaded.")
    elif args.cmd == "export-approved":
        out_dir = Path("exports")
        j = export_approved_json(out_dir / "approved_content.json")
        m = export_approved_markdown(out_dir / "approved_book.md")
        print(f"Exported JSON: {j}")
        print(f"Exported Markdown: {m}")
    elif args.cmd == "approve-demo":
        with get_conn() as conn:
            conn.execute(
                "UPDATE tafsir_entries SET verification_status = 'approved' WHERE id IN (SELECT id FROM tafsir_entries LIMIT 3)"
            )
            conn.execute(
                "UPDATE hadiths SET verification_status = 'approved' WHERE id IN (SELECT id FROM hadiths LIMIT 1)"
            )
            conn.execute(
                "UPDATE scholarly_commentary SET verification_status = 'approved' WHERE id IN (SELECT id FROM scholarly_commentary LIMIT 2)"
            )
            conn.execute(
                "UPDATE father_narrative SET status = 'final' WHERE id IN (SELECT id FROM father_narrative LIMIT 1)"
            )
            conn.execute(
                "UPDATE allah_names SET verification_status = 'approved' WHERE id IN (1,2,3,4,5)"
            )
        print("Demo records approved.")


if __name__ == "__main__":
    main()
