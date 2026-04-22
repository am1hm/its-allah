from __future__ import annotations

import hashlib
from datetime import datetime, timezone

from innahu_allah.db import get_conn


PARSER_VERSION = "v1"
SOURCE_REF = "fatihah_seed"
SOURCE_URL = "internal_seed"


def _hash_text(text: str) -> str:
    return hashlib.sha256(text.encode("utf-8")).hexdigest()


def _now() -> str:
    return datetime.now(timezone.utc).isoformat()


def seed_fatihah() -> None:
    with get_conn() as conn:
        cur = conn.cursor()
        names = [
            (1, "الله", "Allah", "The One True God", "perfection", "both"),
            (2, "الرحمن", "Ar-Rahman", "The Entirely Merciful", "beauty", "quran"),
            (3, "الرحيم", "Ar-Rahim", "The Especially Merciful", "beauty", "quran"),
            (4, "الرب", "Ar-Rabb", "The Lord and Sustainer", "majesty", "quran"),
            (5, "المالك", "Al-Malik", "The Sovereign Owner", "majesty", "quran"),
        ]
        for row in names:
            source_hash = _hash_text("|".join(str(x) for x in row))
            cur.execute(
                """
                INSERT INTO allah_names
                (id, name_arabic, name_transliteration, meaning, category, source_type, source_ref, source_url, parser_version, source_hash, ingestion_timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(id) DO UPDATE SET
                  name_arabic=excluded.name_arabic,
                  name_transliteration=excluded.name_transliteration,
                  meaning=excluded.meaning,
                  category=excluded.category,
                  source_type=excluded.source_type,
                  source_ref=excluded.source_ref,
                  source_url=excluded.source_url,
                  parser_version=excluded.parser_version,
                  source_hash=excluded.source_hash
                """,
                (*row, SOURCE_REF, SOURCE_URL, PARSER_VERSION, source_hash, _now()),
            )

        verses = [
            (1, "الفاتحة", 1, "بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ", "بسم الله الرحمن الرحيم"),
            (1, "الفاتحة", 2, "الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ", "الحمد لله رب العالمين"),
            (1, "الفاتحة", 3, "الرَّحْمَٰنِ الرَّحِيمِ", "الرحمن الرحيم"),
            (1, "الفاتحة", 4, "مَالِكِ يَوْمِ الدِّينِ", "مالك يوم الدين"),
            (1, "الفاتحة", 5, "إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ", "اياك نعبد واياك نستعين"),
            (1, "الفاتحة", 6, "اهْدِنَا الصِّرَاطَ الْمُسْتَقِيمَ", "اهدنا الصراط المستقيم"),
            (1, "الفاتحة", 7, "صِرَاطَ الَّذِينَ أَنْعَمْتَ عَلَيْهِمْ", "صراط الذين انعمت عليهم"),
        ]
        for row in verses:
            source_hash = _hash_text("|".join(str(x) for x in row))
            cur.execute(
                """
                INSERT INTO quran_verses
                (surah_number, surah_name, ayah_number, ayah_text, ayah_text_simple, source_ref, source_url, parser_version, source_hash, ingestion_timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(surah_number, ayah_number) DO UPDATE SET
                  surah_name=excluded.surah_name,
                  ayah_text=excluded.ayah_text,
                  ayah_text_simple=excluded.ayah_text_simple,
                  source_ref=excluded.source_ref,
                  source_url=excluded.source_url,
                  parser_version=excluded.parser_version,
                  source_hash=excluded.source_hash
                """,
                (*row, SOURCE_REF, SOURCE_URL, PARSER_VERSION, source_hash, _now()),
            )

        link_specs = [
            (1, 1, "explicit name"),
            (2, 1, "explicit name"),
            (3, 1, "explicit name"),
            (1, 2, "explicit name"),
            (4, 2, "derived attribute"),
            (2, 3, "explicit name"),
            (3, 3, "explicit name"),
            (5, 4, "explicit name"),
        ]
        for name_id, ayah_number, context in link_specs:
            verse_id = cur.execute(
                "SELECT id FROM quran_verses WHERE surah_number = 1 AND ayah_number = ?",
                (ayah_number,),
            ).fetchone()["id"]
            source_hash = _hash_text(f"{name_id}|{verse_id}|{context}")
            cur.execute(
                """
                INSERT INTO name_verse_links
                (name_id, verse_id, context_note, source_ref, source_url, parser_version, source_hash, ingestion_timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(name_id, verse_id, context_note) DO NOTHING
                """,
                (name_id, verse_id, context, SOURCE_REF, SOURCE_URL, PARSER_VERSION, source_hash, _now()),
            )

        tafsir_rows = [
            ("Ibn Kathir", "Tafsir al-Quran al-Azim", 1, "This opening combines seeking blessing with Allah's Name and mercy.", "pending"),
            ("Al-Tabari", "Jami al-Bayan", 1, "The basmala introduces the Lord by Names of all-encompassing and specific mercy.", "pending"),
            ("Al-Baghawi", "Ma'alim al-Tanzil", 2, "Praise belongs to Allah alone, Lord of all creation.", "pending"),
            ("Al-Jalalayn", "Tafsir al-Jalalayn", 3, "Ar-Rahman Ar-Rahim emphasize successive layers of mercy.", "pending"),
            ("Tafsir al-Muyassar", "Tafsir al-Muyassar", 4, "Owner of the Day of Recompense signifies sovereign judgment.", "pending"),
        ]
        for scholar, book, ayah_number, text, status in tafsir_rows:
            verse_id = cur.execute(
                "SELECT id FROM quran_verses WHERE surah_number = 1 AND ayah_number = ?",
                (ayah_number,),
            ).fetchone()["id"]
            source_hash = _hash_text(f"{verse_id}|{scholar}|{text}")
            cur.execute(
                """
                INSERT INTO tafsir_entries
                (verse_id, scholar_name, source_book, tafsir_text, data_source, verification_status, source_ref, source_url, parser_version, source_hash, ingestion_timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(verse_id, scholar_name, source_hash) DO NOTHING
                """,
                (
                    verse_id,
                    scholar,
                    book,
                    text,
                    "seed_demo",
                    status,
                    SOURCE_REF,
                    SOURCE_URL,
                    PARSER_VERSION,
                    source_hash,
                    _now(),
                ),
            )

        hadith = {
            "collection": "Jami` at-Tirmidhi",
            "hadith_number": "3507",
            "book_chapter": "Supplications",
            "arabic_text": "إِنَّ لِلَّهِ تِسْعَةً وَتِسْعِينَ اسْمًا...",
            "isnad": "Abu Hurayrah",
            "matn": "Allah has ninety-nine names; whoever enumerates them enters Paradise.",
            "grade": "sahih",
            "grading_scholar": "al-Albani",
            "takhrij": "Also reported in Bukhari with variant wording",
        }
        h_hash = _hash_text("|".join(str(v) for v in hadith.values()))
        cur.execute(
            """
            INSERT INTO hadiths
            (collection, hadith_number, book_chapter, arabic_text, isnad, matn, grade, grading_scholar, takhrij, data_source, source_ref, source_url, parser_version, source_hash, ingestion_timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON CONFLICT(collection, hadith_number, source_hash) DO NOTHING
            """,
            (
                hadith["collection"],
                hadith["hadith_number"],
                hadith["book_chapter"],
                hadith["arabic_text"],
                hadith["isnad"],
                hadith["matn"],
                hadith["grade"],
                hadith["grading_scholar"],
                hadith["takhrij"],
                "seed_demo",
                SOURCE_REF,
                SOURCE_URL,
                PARSER_VERSION,
                h_hash,
                _now(),
            ),
        )
        hadith_id = cur.execute(
            "SELECT id FROM hadiths WHERE collection = ? AND hadith_number = ? ORDER BY id DESC LIMIT 1",
            (hadith["collection"], hadith["hadith_number"]),
        ).fetchone()["id"]
        for name_id in [1, 2, 3]:
            cur.execute(
                """
                INSERT INTO name_hadith_links
                (name_id, hadith_id, relevance_note, source_ref, source_url, parser_version, source_hash, ingestion_timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON CONFLICT(name_id, hadith_id) DO NOTHING
                """,
                (
                    name_id,
                    hadith_id,
                    "Core hadith of the Names",
                    SOURCE_REF,
                    SOURCE_URL,
                    PARSER_VERSION,
                    _hash_text(f"{name_id}|{hadith_id}"),
                    _now(),
                ),
            )

        commentary_rows = [
            (2, None, None, "Ibn Uthaymin", "Al-Qawa'id al-Muthla", "Ar-Rahman denotes vast mercy encompassing all creation.", "aqeedah_rule"),
            (3, None, None, "Al-Sa'di", "Tafsir Asma Allah", "Ar-Rahim indicates mercy specifically reaching believers.", "scholar_explanation"),
        ]
        for name_id, verse_id, hadith_id, scholar, book, text, ctype in commentary_rows:
            cur.execute(
                """
                INSERT INTO scholarly_commentary
                (name_id, verse_id, hadith_id, scholar_name, source_book, commentary_text, commentary_type, data_source, source_ref, source_url, parser_version, source_hash, ingestion_timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                """,
                (
                    name_id,
                    verse_id,
                    hadith_id,
                    scholar,
                    book,
                    text,
                    ctype,
                    "seed_demo",
                    SOURCE_REF,
                    SOURCE_URL,
                    PARSER_VERSION,
                    _hash_text(f"{name_id}|{scholar}|{text}"),
                    _now(),
                ),
            )

        father_narratives = [
            (1, 1, "تبدأ السورة باسم الله، وفي ذلك تأسيس للتوحيد والاستعانة."),
            (2, 3, "الرحمن والرحيم يجمعان بين سعة الرحمة وخصوصيتها."),
        ]
        for name_id, verse_ayah, text in father_narratives:
            verse_id = cur.execute(
                "SELECT id FROM quran_verses WHERE surah_number = 1 AND ayah_number = ?",
                (verse_ayah,),
            ).fetchone()["id"]
            cur.execute(
                """
                INSERT INTO father_narrative
                (name_id, verse_id, narrative_text, status)
                VALUES (?, ?, ?, 'draft')
                """,
                (name_id, verse_id, text),
            )
