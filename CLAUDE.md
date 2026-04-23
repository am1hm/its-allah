# CLAUDE.md — مشروع "إنه الله"

## ما هو المشروع

موقع إلكتروني + كتاب مطبوع متخصص في أسماء الله الحسنى وصفاته.
يعرض لكل اسم: الآيات القرآنية + تفسيرها + الأحاديث + أقوال العلماء + تعليق والده.
مرتب من سورة الفاتحة إلى سورة الناس.

## الفريق

- المطور: يوسف عمار (@am1hm)
- المراجع العلمي: الوالد (دكتور في أصول الفقه)
- المنهج: أهل السنة والجماعة — المرجع: القواعد المثلى لابن عثيمين

**قاعدة لا تُكسر:** لا يُنشر أي محتوى بدون موافقة الوالد (`verification_status = approved`)

## Stack

- Backend: Laravel (PHP) — `laravel-app/`
- Prototype: Python — `innahu_allah/` (للاختبار السريع فقط، ليس للإنتاج)
- DB: MySQL/SQLite (dev) → PostgreSQL (production)
- Frontend: Laravel Blade (هيكل فقط حتى الآن)

## بيئة التطوير

PHP 8.4 **مُثبَّت** عبر winget. المسار الكامل:
```
C:\Users\almou\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
```
قاعدة البيانات: SQLite في `laravel-app/database/database.sqlite`

---

## الحالة الحالية (2026-04-23)

### ✅ مكتمل

| المكون | التفاصيل |
|--------|----------|
| استيراد القرآن | 6236 آية من KFGQPC (حفص عن عاصم) |
| نظام المقارنة | مقارنة مع Tanzil + تقرير JSON |
| Normalizer V3 | مُصلَّح ومُختبَر — 97.9% تطابق |
| المقارنة | **مُشغَّلة** — 6107 تطابق، 129 mismatch |
| Laravel skeleton | Auth + Roles + Admin + Migrations |
| نظام الحوكمة | `data/source_registry.yaml` |
| قاعدة البيانات | SQLite مع 6236 آية مستوردة |

### 📊 نتائج المقارنة الأخيرة (2026-04-23)
```
normalized_matches: 6107 / 6236 = 97.9%
mismatches: 129 = 112 (بسملة هيكلية) + 17 (فروق نصية)
```

### ❌ لم يبدأ بعد
- إصلاح مشكلة البسملة في `QuranCompareTanzilCommand.php` → يرفع التطابق إلى 99.7%
- استيراد أسماء الله الـ99
- استيراد التفاسير (Quran.com API / AlQuran.cloud)
- استيراد الأحاديث (sunnah.com / Dorar API)
- استيراد أقوال العلماء
- واجهة الموقع العام
- النشر

---

## الملفات الجوهرية

| الملف | الغرض |
|-------|--------|
| `laravel-app/app/Services/QuranTextNormalizer.php` | تطبيع النص للمقارنة |
| `laravel-app/app/Console/Commands/QuranImportPrimaryCommand.php` | استيراد القرآن |
| `laravel-app/app/Console/Commands/QuranCompareTanzilCommand.php` | مقارنة Tanzil |
| `laravel-app/database/migrations/2026_04_21_170100_create_innahu_allah_tables.php` | المخطط الرئيسي |
| `exports/quran_diff_report.json` | آخر نتائج المقارنة (6.7MB) |
| `data/source_registry.yaml` | إعدادات الحوكمة والمصادر |
| `plan.md` | الخطة الكاملة للمشروع (7 مراحل) |

---

## مصادر البيانات

| المحتوى | المصدر | الحالة |
|---------|--------|--------|
| القرآن (أساسي) | KFGQPC — `github.com/thetruetruth/quran-data-kfgqpc` | ✅ مستورد |
| القرآن (تحقق) | Tanzil — `tanzil.net/pub/download/v1.0/download.php` | ✅ مربوط |
| أسماء الله | `github.com/KabDeveloper/99-Names-Of-Allah` | ❌ |
| التفاسير | Quran.com API / AlQuran.cloud / spa5k/tafsir_api | ❌ |
| الأحاديث | `sunnah.com` API / `AhmedBaset/hadith-json` | ❌ |
| أقوال العلماء | المكتبة الشاملة / OpenITI | ❌ |

---

## الخطوات التالية بالأولوية

1. تشغيل المقارنة لتأكيد إصلاح الـ Normalizer
2. استيراد أسماء الله الـ99 + ربطها بالآيات
3. استيراد التفاسير عبر API
4. استيراد الأحاديث عبر API
5. بناء Admin UI لمراجعة الوالد
6. بناء الموقع العام

---

## هيكل قاعدة البيانات (الجداول الرئيسية)

```
allah_names          — أسماء الله الـ99
quran_verses         — 6236 آية (مكتملة)
name_verse_links     — ربط الأسماء بالآيات
tafsir_entries       — التفاسير (فارغ)
hadiths              — الأحاديث (فارغ)
name_hadith_links    — ربط الأحاديث بالأسماء
scholarly_commentary — أقوال العلماء (فارغ)
father_narrative     — تعليقات الوالد (فارغ)
review_audit_log     — سجل المراجعة
```

كل جدول يحتوي: `verification_status`, `source_ref`, `reviewer_notes`
