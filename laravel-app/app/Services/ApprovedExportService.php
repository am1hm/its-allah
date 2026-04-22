<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ApprovedExportService
{
    public function exportJson(string $path): string
    {
        $payload = [
            'names' => DB::table('allah_names')
                ->where('verification_status', 'approved')
                ->orWhereIn('id', DB::table('name_verse_links')->select('name_id')->distinct())
                ->get(),
            'verses' => DB::table('quran_verses as qv')
                ->join('name_verse_links as nvl', 'nvl.verse_id', '=', 'qv.id')
                ->select('qv.*')
                ->distinct()
                ->orderBy('qv.surah_number')
                ->orderBy('qv.ayah_number')
                ->get(),
            'tafsir_entries' => DB::table('tafsir_entries')->where('verification_status', 'approved')->get(),
            'hadiths' => DB::table('hadiths')->where('verification_status', 'approved')->get(),
            'scholarly_commentary' => DB::table('scholarly_commentary')->where('verification_status', 'approved')->get(),
            'father_narrative' => DB::table('father_narrative')->where('status', 'final')->get(),
        ];

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    public function exportMarkdown(string $path): string
    {
        $verses = DB::table('quran_verses as qv')
            ->join('name_verse_links as nvl', 'nvl.verse_id', '=', 'qv.id')
            ->select('qv.*')
            ->distinct()
            ->orderBy('qv.surah_number')
            ->orderBy('qv.ayah_number')
            ->get();

        $lines = ['# Innahu Allah - Approved Manuscript', ''];
        foreach ($verses as $verse) {
            $lines[] = '## '.$verse->surah_name.' '.$verse->ayah_number;
            $lines[] = $verse->ayah_text;
            $lines[] = '';

            $tafsir = DB::table('tafsir_entries')
                ->where('verse_id', $verse->id)
                ->where('verification_status', 'approved')
                ->get(['scholar_name', 'tafsir_text']);
            if ($tafsir->isNotEmpty()) {
                $lines[] = '### Tafsir';
                foreach ($tafsir as $row) {
                    $lines[] = '- **'.$row->scholar_name.'**: '.$row->tafsir_text;
                }
                $lines[] = '';
            }

            $comments = DB::table('scholarly_commentary')
                ->where('verse_id', $verse->id)
                ->where('verification_status', 'approved')
                ->get(['scholar_name', 'commentary_text']);
            if ($comments->isNotEmpty()) {
                $lines[] = '### Scholarly Commentary';
                foreach ($comments as $row) {
                    $lines[] = '- **'.$row->scholar_name.'**: '.$row->commentary_text;
                }
                $lines[] = '';
            }

            $narratives = DB::table('father_narrative')
                ->where('verse_id', $verse->id)
                ->where('status', 'final')
                ->get(['narrative_text']);
            if ($narratives->isNotEmpty()) {
                $lines[] = '### Father Narrative';
                foreach ($narratives as $row) {
                    $lines[] = '- '.$row->narrative_text;
                }
                $lines[] = '';
            }
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode(PHP_EOL, $lines));

        return $path;
    }
}

