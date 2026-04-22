from __future__ import annotations

import json
from pathlib import Path

from innahu_allah.db import get_conn


def export_approved_json(output_path: Path) -> Path:
    with get_conn() as conn:
        names = conn.execute(
            "SELECT * FROM allah_names WHERE verification_status = 'approved' OR id IN (SELECT DISTINCT name_id FROM name_verse_links)"
        ).fetchall()
        verses = conn.execute(
            """
            SELECT qv.* FROM quran_verses qv
            JOIN name_verse_links nvl ON nvl.verse_id = qv.id
            GROUP BY qv.id
            ORDER BY qv.surah_number, qv.ayah_number
            """
        ).fetchall()
        tafsir = conn.execute(
            "SELECT * FROM tafsir_entries WHERE verification_status = 'approved'"
        ).fetchall()
        hadiths = conn.execute(
            "SELECT * FROM hadiths WHERE verification_status = 'approved'"
        ).fetchall()
        commentary = conn.execute(
            "SELECT * FROM scholarly_commentary WHERE verification_status = 'approved'"
        ).fetchall()
        narrative = conn.execute(
            "SELECT * FROM father_narrative WHERE status = 'final'"
        ).fetchall()

    payload = {
        "names": [dict(r) for r in names],
        "verses": [dict(r) for r in verses],
        "tafsir_entries": [dict(r) for r in tafsir],
        "hadiths": [dict(r) for r in hadiths],
        "scholarly_commentary": [dict(r) for r in commentary],
        "father_narrative": [dict(r) for r in narrative],
    }
    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    return output_path


def export_approved_markdown(output_path: Path) -> Path:
    with get_conn() as conn:
        verses = conn.execute(
            """
            SELECT DISTINCT qv.*
            FROM quran_verses qv
            JOIN name_verse_links nvl ON nvl.verse_id = qv.id
            ORDER BY qv.surah_number, qv.ayah_number
            """
        ).fetchall()

        lines = ["# Innahu Allah - Approved Manuscript", ""]
        for verse in verses:
            lines.append(f"## {verse['surah_name']} {verse['ayah_number']}")
            lines.append(verse["ayah_text"])
            lines.append("")

            tafsir_rows = conn.execute(
                "SELECT scholar_name, tafsir_text FROM tafsir_entries WHERE verse_id = ? AND verification_status = 'approved'",
                (verse["id"],),
            ).fetchall()
            if tafsir_rows:
                lines.append("### Tafsir")
                for row in tafsir_rows:
                    lines.append(f"- **{row['scholar_name']}**: {row['tafsir_text']}")
                lines.append("")

            comments = conn.execute(
                "SELECT scholar_name, commentary_text FROM scholarly_commentary WHERE verse_id = ? AND verification_status = 'approved'",
                (verse["id"],),
            ).fetchall()
            if comments:
                lines.append("### Scholarly Commentary")
                for row in comments:
                    lines.append(f"- **{row['scholar_name']}**: {row['commentary_text']}")
                lines.append("")

            narratives = conn.execute(
                "SELECT narrative_text FROM father_narrative WHERE verse_id = ? AND status = 'final'",
                (verse["id"],),
            ).fetchall()
            if narratives:
                lines.append("### Father Narrative")
                for row in narratives:
                    lines.append(f"- {row['narrative_text']}")
                lines.append("")

    output_path.parent.mkdir(parents=True, exist_ok=True)
    output_path.write_text("\n".join(lines), encoding="utf-8")
    return output_path
