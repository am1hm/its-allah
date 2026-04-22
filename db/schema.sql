PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS allah_names (
  id INTEGER PRIMARY KEY,
  name_arabic TEXT NOT NULL,
  name_transliteration TEXT,
  meaning TEXT,
  category TEXT,
  source_type TEXT CHECK(source_type IN ('quran', 'sunnah', 'both')) DEFAULT 'both',
  verification_status TEXT CHECK(verification_status IN ('pending', 'approved', 'rejected', 'approved_with_edit')) DEFAULT 'pending',
  reviewer_notes TEXT,
  source_ref TEXT,
  source_url TEXT,
  ingestion_timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  parser_version TEXT,
  source_hash TEXT
);

CREATE TABLE IF NOT EXISTS quran_verses (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  surah_number INTEGER NOT NULL,
  surah_name TEXT NOT NULL,
  ayah_number INTEGER NOT NULL,
  ayah_text TEXT NOT NULL,
  ayah_text_simple TEXT,
  source_ref TEXT,
  source_url TEXT,
  ingestion_timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  parser_version TEXT,
  source_hash TEXT,
  UNIQUE(surah_number, ayah_number)
);

CREATE TABLE IF NOT EXISTS name_verse_links (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name_id INTEGER NOT NULL,
  verse_id INTEGER NOT NULL,
  context_note TEXT,
  verification_status TEXT CHECK(verification_status IN ('pending', 'approved', 'rejected', 'approved_with_edit')) DEFAULT 'pending',
  source_ref TEXT,
  source_url TEXT,
  ingestion_timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  parser_version TEXT,
  source_hash TEXT,
  UNIQUE(name_id, verse_id, context_note),
  FOREIGN KEY(name_id) REFERENCES allah_names(id),
  FOREIGN KEY(verse_id) REFERENCES quran_verses(id)
);

CREATE TABLE IF NOT EXISTS tafsir_entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  verse_id INTEGER NOT NULL,
  scholar_name TEXT NOT NULL,
  source_book TEXT,
  volume TEXT,
  page_number TEXT,
  tafsir_text TEXT NOT NULL,
  data_source TEXT NOT NULL,
  verification_status TEXT CHECK(verification_status IN ('pending', 'approved', 'rejected', 'approved_with_edit')) DEFAULT 'pending',
  reviewer_notes TEXT,
  source_ref TEXT,
  source_url TEXT,
  ingestion_timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  parser_version TEXT,
  source_hash TEXT,
  UNIQUE(verse_id, scholar_name, source_hash),
  FOREIGN KEY(verse_id) REFERENCES quran_verses(id)
);

CREATE TABLE IF NOT EXISTS hadiths (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  collection TEXT NOT NULL,
  hadith_number TEXT NOT NULL,
  book_chapter TEXT,
  arabic_text TEXT NOT NULL,
  isnad TEXT,
  matn TEXT,
  grade TEXT,
  grading_scholar TEXT,
  takhrij TEXT,
  data_source TEXT NOT NULL,
  verification_status TEXT CHECK(verification_status IN ('pending', 'approved', 'rejected', 'approved_with_edit')) DEFAULT 'pending',
  reviewer_notes TEXT,
  source_ref TEXT,
  source_url TEXT,
  ingestion_timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  parser_version TEXT,
  source_hash TEXT,
  UNIQUE(collection, hadith_number, source_hash)
);

CREATE TABLE IF NOT EXISTS name_hadith_links (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name_id INTEGER NOT NULL,
  hadith_id INTEGER NOT NULL,
  relevance_note TEXT,
  verification_status TEXT CHECK(verification_status IN ('pending', 'approved', 'rejected', 'approved_with_edit')) DEFAULT 'pending',
  source_ref TEXT,
  source_url TEXT,
  ingestion_timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  parser_version TEXT,
  source_hash TEXT,
  UNIQUE(name_id, hadith_id),
  FOREIGN KEY(name_id) REFERENCES allah_names(id),
  FOREIGN KEY(hadith_id) REFERENCES hadiths(id)
);

CREATE TABLE IF NOT EXISTS scholarly_commentary (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name_id INTEGER,
  verse_id INTEGER,
  hadith_id INTEGER,
  scholar_name TEXT NOT NULL,
  source_book TEXT,
  volume TEXT,
  page_number TEXT,
  commentary_text TEXT NOT NULL,
  commentary_type TEXT CHECK(commentary_type IN ('companion_comment', 'scholar_explanation', 'aqeedah_rule')) NOT NULL,
  data_source TEXT NOT NULL,
  verification_status TEXT CHECK(verification_status IN ('pending', 'approved', 'rejected', 'approved_with_edit')) DEFAULT 'pending',
  reviewer_notes TEXT,
  source_ref TEXT,
  source_url TEXT,
  ingestion_timestamp TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  parser_version TEXT,
  source_hash TEXT,
  FOREIGN KEY(name_id) REFERENCES allah_names(id),
  FOREIGN KEY(verse_id) REFERENCES quran_verses(id),
  FOREIGN KEY(hadith_id) REFERENCES hadiths(id)
);

CREATE TABLE IF NOT EXISTS father_narrative (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name_id INTEGER,
  verse_id INTEGER,
  narrative_text TEXT NOT NULL,
  status TEXT CHECK(status IN ('draft', 'final')) DEFAULT 'draft',
  created_date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(name_id) REFERENCES allah_names(id),
  FOREIGN KEY(verse_id) REFERENCES quran_verses(id)
);

CREATE TABLE IF NOT EXISTS review_audit_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  content_type TEXT NOT NULL,
  content_id INTEGER NOT NULL,
  old_status TEXT,
  new_status TEXT,
  reviewer TEXT NOT NULL DEFAULT 'father',
  notes TEXT,
  reviewed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
