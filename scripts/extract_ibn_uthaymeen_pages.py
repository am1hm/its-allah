from __future__ import annotations

import json
import re
from pathlib import Path


ROOT = Path("/Users/yousef/Desktop/projects/islamic-project")
SOURCE = ROOT / "Attributes_of_Allah_by_Ibn_Uthaymeen.md"
OUT_DIR = ROOT / "data" / "extraction" / "ibn_uthaymeen"
RAW_OUT = OUT_DIR / "raw_pages.json"
CLEAN_OUT = OUT_DIR / "clean_pages.json"
LOG_OUT = OUT_DIR / "extraction_log.md"


PAGE_RE = re.compile(r"<!--\s*page\s+(\d+)\s*-->", re.IGNORECASE)
IMG_RE = re.compile(r"!\[[^\]]*\]\([^)]+\)")


def quality_score(text: str) -> str:
    arabic_chars = len(re.findall(r"[\u0600-\u06FF]", text))
    total_chars = len(text.strip())
    if total_chars == 0:
        return "low"
    ratio = arabic_chars / max(total_chars, 1)
    if ratio > 0.5:
        return "medium"
    if ratio > 0.25:
        return "low"
    return "low"


def clean_text(raw: str) -> str:
    lines = []
    for line in raw.splitlines():
        if PAGE_RE.search(line):
            continue
        if IMG_RE.search(line.strip()):
            continue
        stripped = line.rstrip()
        if stripped:
            lines.append(stripped)
    cleaned = "\n".join(lines)
    cleaned = re.sub(r"\n{3,}", "\n\n", cleaned).strip()
    return cleaned


def split_pages(content: str) -> list[tuple[int, str, str]]:
    matches = list(PAGE_RE.finditer(content))
    pages: list[tuple[int, str, str]] = []
    for idx, m in enumerate(matches):
        page_no = int(m.group(1))
        start = m.start()
        end = matches[idx + 1].start() if idx + 1 < len(matches) else len(content)
        block = content[start:end].strip()
        marker = m.group(0)
        pages.append((page_no, marker, block))
    return pages


def build_records(pages: list[tuple[int, str, str]]) -> tuple[list[dict], list[dict]]:
    raw_records = []
    clean_records = []
    for page_no, marker, block in pages:
        cleaned = clean_text(block)
        q = quality_score(cleaned)
        base = {
            "book_id": "qawaid_muthla_ibn_uthaymeen",
            "book_title": "القواعد المثلى في صفات الله وأسمائه الحسنى",
            "author": "محمد بن صالح العثيمين",
            "page_number": page_no,
            "source_file": SOURCE.name,
            "source_page_marker": marker,
            "ocr_quality": q,
            "needs_review": True,
            "verification_status": "pending",
            "reviewer_notes": "",
        }
        raw_records.append(
            {
                **base,
                "raw_text": block,
                "clean_text": None,
            }
        )
        clean_records.append(
            {
                **base,
                "raw_text_ref": f"raw_pages.json::page_number={page_no}",
                "clean_text": cleaned,
            }
        )
    return raw_records, clean_records


def update_log(page_count: int, clean_count: int) -> None:
    content = LOG_OUT.read_text(encoding="utf-8") if LOG_OUT.exists() else ""
    content = re.sub(r"- Total detected pages: `.*`", f"- Total detected pages: `{page_count}`", content)
    content = re.sub(r"- Extracted to raw JSON: `.*`", f"- Extracted to raw JSON: `{page_count}`", content)
    content = re.sub(r"- Cleaned pages completed: `.*`", f"- Cleaned pages completed: `{clean_count}`", content)
    content = re.sub(r"- Pending review pages: `.*`", f"- Pending review pages: `{clean_count}`", content)
    if not content:
        content = (
            "# Extraction Log — Ibn Uthaymeen Book\n\n"
            f"- Total detected pages: `{page_count}`\n"
            f"- Extracted to raw JSON: `{page_count}`\n"
            f"- Cleaned pages completed: `{clean_count}`\n"
            f"- Pending review pages: `{clean_count}`\n"
        )
    LOG_OUT.write_text(content, encoding="utf-8")


def main() -> None:
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    source_text = SOURCE.read_text(encoding="utf-8")
    pages = split_pages(source_text)
    raw_records, clean_records = build_records(pages)
    raw_records.sort(key=lambda r: r["page_number"])
    clean_records.sort(key=lambda r: r["page_number"])
    RAW_OUT.write_text(json.dumps(raw_records, ensure_ascii=False, indent=2), encoding="utf-8")
    CLEAN_OUT.write_text(json.dumps(clean_records, ensure_ascii=False, indent=2), encoding="utf-8")
    update_log(len(raw_records), len(clean_records))
    print(f"Extracted pages: {len(raw_records)}")
    print(f"Raw JSON: {RAW_OUT}")
    print(f"Clean JSON: {CLEAN_OUT}")


if __name__ == "__main__":
    main()
