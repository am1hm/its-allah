<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeedFatihahCommand extends Command
{
    protected $signature = 'innahu:seed-fatihah';

    protected $description = 'Seed Al-Fatihah demo dataset and bootstrap admin login';

    public function handle(): int
    {
        User::updateOrCreate(
            ['email' => 'admin@innahu.local'],
            ['name' => 'Project Admin', 'password' => Hash::make('admin12345'), 'role' => 'admin']
        );

        $names = [
            [1, 'الله', 'Allah', 'The One True God', 'perfection', 'both'],
            [2, 'الرحمن', 'Ar-Rahman', 'The Entirely Merciful', 'beauty', 'quran'],
            [3, 'الرحيم', 'Ar-Rahim', 'The Especially Merciful', 'beauty', 'quran'],
            [4, 'الرب', 'Ar-Rabb', 'The Lord and Sustainer', 'majesty', 'quran'],
            [5, 'المالك', 'Al-Malik', 'The Sovereign Owner', 'majesty', 'quran'],
        ];
        foreach ($names as [$id, $ar, $tr, $meaning, $category, $sourceType]) {
            DB::table('allah_names')->updateOrInsert(
                ['id' => $id],
                [
                    'name_arabic' => $ar,
                    'name_transliteration' => $tr,
                    'meaning' => $meaning,
                    'category' => $category,
                    'source_type' => $sourceType,
                    'verification_status' => 'approved',
                    'source_ref' => 'fatihah_seed',
                    'source_url' => 'internal_seed',
                    'ingestion_timestamp' => Carbon::now(),
                    'parser_version' => 'v1',
                    'updated_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                ]
            );
        }

        $verses = [
            [1, 'الفاتحة', 1, 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', 'بسم الله الرحمن الرحيم'],
            [1, 'الفاتحة', 2, 'الْحَمْدُ لِلَّهِ رَبِّ الْعَالَمِينَ', 'الحمد لله رب العالمين'],
            [1, 'الفاتحة', 3, 'الرَّحْمَٰنِ الرَّحِيمِ', 'الرحمن الرحيم'],
            [1, 'الفاتحة', 4, 'مَالِكِ يَوْمِ الدِّينِ', 'مالك يوم الدين'],
            [1, 'الفاتحة', 5, 'إِيَّاكَ نَعْبُدُ وَإِيَّاكَ نَسْتَعِينُ', 'اياك نعبد واياك نستعين'],
            [1, 'الفاتحة', 6, 'اهْدِنَا الصِّرَاطَ الْمُسْتَقِيمَ', 'اهدنا الصراط المستقيم'],
            [1, 'الفاتحة', 7, 'صِرَاطَ الَّذِينَ أَنْعَمْتَ عَلَيْهِمْ', 'صراط الذين انعمت عليهم'],
        ];
        foreach ($verses as [$surahNo, $surahName, $ayahNo, $ayahText, $simple]) {
            DB::table('quran_verses')->updateOrInsert(
                ['surah_number' => $surahNo, 'ayah_number' => $ayahNo],
                [
                    'surah_name' => $surahName,
                    'ayah_text' => $ayahText,
                    'ayah_text_simple' => $simple,
                    'source_ref' => 'fatihah_seed',
                    'source_url' => 'internal_seed',
                    'ingestion_timestamp' => Carbon::now(),
                    'parser_version' => 'v1',
                    'updated_at' => Carbon::now(),
                    'created_at' => Carbon::now(),
                ]
            );
        }

        $verse1 = DB::table('quran_verses')->where(['surah_number' => 1, 'ayah_number' => 1])->value('id');
        $verse2 = DB::table('quran_verses')->where(['surah_number' => 1, 'ayah_number' => 2])->value('id');

        DB::table('name_verse_links')->updateOrInsert(['name_id' => 1, 'verse_id' => $verse1], ['context_note' => 'explicit name']);
        DB::table('name_verse_links')->updateOrInsert(['name_id' => 2, 'verse_id' => $verse1], ['context_note' => 'explicit name']);
        DB::table('name_verse_links')->updateOrInsert(['name_id' => 3, 'verse_id' => $verse1], ['context_note' => 'explicit name']);
        DB::table('name_verse_links')->updateOrInsert(['name_id' => 4, 'verse_id' => $verse2], ['context_note' => 'derived attribute']);

        DB::table('tafsir_entries')->insertOrIgnore([
            'verse_id' => $verse1,
            'scholar_name' => 'Ibn Kathir',
            'source_book' => 'Tafsir al-Quran al-Azim',
            'tafsir_text' => "This opening combines seeking blessing with Allah's Name and mercy.",
            'data_source' => 'seed_demo',
            'verification_status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('hadiths')->insertOrIgnore([
            'collection' => 'Jami` at-Tirmidhi',
            'hadith_number' => '3507',
            'book_chapter' => 'Supplications',
            'arabic_text' => 'إِنَّ لِلَّهِ تِسْعَةً وَتِسْعِينَ اسْمًا...',
            'isnad' => 'Abu Hurayrah',
            'matn' => 'Allah has ninety-nine names; whoever enumerates them enters Paradise.',
            'grade' => 'sahih',
            'grading_scholar' => 'al-Albani',
            'takhrij' => 'Also reported in Bukhari with variant wording',
            'data_source' => 'seed_demo',
            'verification_status' => 'pending',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->info('Seed complete. Admin: admin@innahu.local / admin12345');

        return self::SUCCESS;
    }
}

