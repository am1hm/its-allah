from __future__ import annotations

from fastapi import FastAPI, Form, HTTPException, Request
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates

from innahu_allah.db import get_conn


app = FastAPI(title="Innahu Allah Private Beta")
app.mount("/static", StaticFiles(directory="innahu_allah/web/static"), name="static")
templates = Jinja2Templates(directory="innahu_allah/web/templates")


def _fetch_one_name(name_id: int):
    with get_conn() as conn:
        name = conn.execute("SELECT * FROM allah_names WHERE id = ?", (name_id,)).fetchone()
        if not name:
            return None
        verses = conn.execute(
            """
            SELECT qv.*, nvl.context_note
            FROM quran_verses qv
            JOIN name_verse_links nvl ON nvl.verse_id = qv.id
            WHERE nvl.name_id = ?
            ORDER BY qv.surah_number, qv.ayah_number
            """,
            (name_id,),
        ).fetchall()
        return name, verses


@app.get("/", response_class=HTMLResponse)
def home(request: Request, lang: str = "ar"):
    with get_conn() as conn:
        names = conn.execute("SELECT * FROM allah_names ORDER BY id").fetchall()
        verses = conn.execute(
            "SELECT * FROM quran_verses WHERE surah_number = 1 ORDER BY ayah_number"
        ).fetchall()
    return templates.TemplateResponse(
        request,
        "home.html",
        {
            "names": names,
            "verses": verses,
            "lang": lang,
            "title_ar": "إنه الله",
            "title_en": "Innahu Allah",
        },
    )


@app.get("/names/{name_id}", response_class=HTMLResponse)
def name_page(request: Request, name_id: int, lang: str = "ar"):
    row = _fetch_one_name(name_id)
    if not row:
        raise HTTPException(404, "Name not found")
    name, verses = row
    with get_conn() as conn:
        verse_ids = [v["id"] for v in verses]
        tafsir = conn.execute(
            f"SELECT * FROM tafsir_entries WHERE verse_id IN ({','.join('?' * len(verse_ids))}) AND verification_status = 'approved'"
            if verse_ids
            else "SELECT * FROM tafsir_entries WHERE 1=0",
            tuple(verse_ids),
        ).fetchall()
        hadith = conn.execute(
            """
            SELECT h.*
            FROM hadiths h
            JOIN name_hadith_links nhl ON nhl.hadith_id = h.id
            WHERE nhl.name_id = ? AND h.verification_status = 'approved'
            ORDER BY h.id
            """,
            (name_id,),
        ).fetchall()
        commentary = conn.execute(
            """
            SELECT * FROM scholarly_commentary
            WHERE name_id = ? AND verification_status = 'approved'
            ORDER BY id
            """,
            (name_id,),
        ).fetchall()
        narratives = conn.execute(
            """
            SELECT * FROM father_narrative
            WHERE name_id = ? AND status = 'final'
            ORDER BY id
            """,
            (name_id,),
        ).fetchall()
    return templates.TemplateResponse(
        request,
        "name.html",
        {
            "name": name,
            "verses": verses,
            "tafsir": tafsir,
            "hadith": hadith,
            "commentary": commentary,
            "narratives": narratives,
            "lang": lang,
        },
    )


@app.get("/mushaf", response_class=HTMLResponse)
def mushaf_page(request: Request, lang: str = "ar"):
    with get_conn() as conn:
        verses = conn.execute(
            """
            SELECT DISTINCT qv.*
            FROM quran_verses qv
            JOIN name_verse_links nvl ON nvl.verse_id = qv.id
            ORDER BY qv.surah_number, qv.ayah_number
            """
        ).fetchall()
    return templates.TemplateResponse(request, "mushaf.html", {"verses": verses, "lang": lang})


@app.get("/admin", response_class=HTMLResponse)
def admin_dashboard(request: Request):
    with get_conn() as conn:
        pending_tafsir = conn.execute(
            "SELECT COUNT(*) AS c FROM tafsir_entries WHERE verification_status = 'pending'"
        ).fetchone()["c"]
        pending_hadith = conn.execute(
            "SELECT COUNT(*) AS c FROM hadiths WHERE verification_status = 'pending'"
        ).fetchone()["c"]
        pending_commentary = conn.execute(
            "SELECT COUNT(*) AS c FROM scholarly_commentary WHERE verification_status = 'pending'"
        ).fetchone()["c"]
        reviewed_names = conn.execute(
            "SELECT COUNT(*) AS c FROM allah_names WHERE verification_status IN ('approved', 'approved_with_edit')"
        ).fetchone()["c"]
        total_names = conn.execute("SELECT COUNT(*) AS c FROM allah_names").fetchone()["c"]

        queue = conn.execute(
            """
            SELECT 'tafsir_entries' AS content_type, id, scholar_name AS title, tafsir_text AS text, verification_status
            FROM tafsir_entries WHERE verification_status = 'pending'
            UNION ALL
            SELECT 'hadiths' AS content_type, id, collection || ' ' || hadith_number AS title, arabic_text AS text, verification_status
            FROM hadiths WHERE verification_status = 'pending'
            UNION ALL
            SELECT 'scholarly_commentary' AS content_type, id, scholar_name AS title, commentary_text AS text, verification_status
            FROM scholarly_commentary WHERE verification_status = 'pending'
            LIMIT 50
            """
        ).fetchall()

    return templates.TemplateResponse(
        request,
        "admin.html",
        {
            "stats": {
                "pending_tafsir": pending_tafsir,
                "pending_hadith": pending_hadith,
                "pending_commentary": pending_commentary,
                "reviewed_names": reviewed_names,
                "total_names": total_names,
            },
            "queue": queue,
        },
    )


@app.post("/admin/review")
def review_item(
    content_type: str = Form(...),
    content_id: int = Form(...),
    action: str = Form(...),
    edited_text: str = Form(default=""),
    notes: str = Form(default=""),
):
    if content_type not in {"tafsir_entries", "hadiths", "scholarly_commentary"}:
        raise HTTPException(400, "Invalid content type")
    if action not in {"approve", "approve_with_edit", "reject"}:
        raise HTTPException(400, "Invalid action")

    new_status = {
        "approve": "approved",
        "approve_with_edit": "approved_with_edit",
        "reject": "rejected",
    }[action]

    text_col = {
        "tafsir_entries": "tafsir_text",
        "hadiths": "arabic_text",
        "scholarly_commentary": "commentary_text",
    }[content_type]

    with get_conn() as conn:
        old = conn.execute(
            f"SELECT verification_status FROM {content_type} WHERE id = ?",
            (content_id,),
        ).fetchone()
        if not old:
            raise HTTPException(404, "Content item not found")

        if action == "approve_with_edit" and edited_text.strip():
            conn.execute(
                f"UPDATE {content_type} SET {text_col} = ?, verification_status = ?, reviewer_notes = ? WHERE id = ?",
                (edited_text.strip(), new_status, notes, content_id),
            )
        else:
            conn.execute(
                f"UPDATE {content_type} SET verification_status = ?, reviewer_notes = ? WHERE id = ?",
                (new_status, notes, content_id),
            )

        conn.execute(
            """
            INSERT INTO review_audit_log (content_type, content_id, old_status, new_status, reviewer, notes)
            VALUES (?, ?, ?, ?, 'father', ?)
            """,
            (content_type, content_id, old["verification_status"], new_status, notes),
        )
    return RedirectResponse(url="/admin", status_code=303)


@app.post("/admin/narrative")
def add_narrative(
    name_id: int = Form(...),
    verse_id: int = Form(default=None),
    narrative_text: str = Form(...),
    status: str = Form(default="draft"),
):
    if status not in {"draft", "final"}:
        raise HTTPException(400, "Invalid narrative status")
    with get_conn() as conn:
        conn.execute(
            """
            INSERT INTO father_narrative (name_id, verse_id, narrative_text, status)
            VALUES (?, ?, ?, ?)
            """,
            (name_id, verse_id, narrative_text.strip(), status),
        )
    return RedirectResponse(url="/admin", status_code=303)
