<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function home()
    {
        $names = DB::table('allah_names')->orderBy('id')->get();
        $verses = DB::table('quran_verses')->where('surah_number', 1)->orderBy('ayah_number')->get();

        return view('public.home', compact('names', 'verses'));
    }

    public function namePage(int $nameId)
    {
        $name = DB::table('allah_names')->where('id', $nameId)->first();
        abort_if(! $name, 404);

        $verses = DB::table('quran_verses as qv')
            ->join('name_verse_links as nvl', 'nvl.verse_id', '=', 'qv.id')
            ->where('nvl.name_id', $nameId)
            ->orderBy('qv.surah_number')
            ->orderBy('qv.ayah_number')
            ->select('qv.*', 'nvl.context_note')
            ->get();

        $verseIds = $verses->pluck('id')->all();

        $tafsir = empty($verseIds) ? collect() : DB::table('tafsir_entries')
            ->whereIn('verse_id', $verseIds)
            ->where('verification_status', 'approved')
            ->orderBy('id')
            ->get();

        $hadith = DB::table('hadiths as h')
            ->join('name_hadith_links as nhl', 'nhl.hadith_id', '=', 'h.id')
            ->where('nhl.name_id', $nameId)
            ->where('h.verification_status', 'approved')
            ->select('h.*')
            ->orderBy('h.id')
            ->get();

        $commentary = DB::table('scholarly_commentary')
            ->where('name_id', $nameId)
            ->where('verification_status', 'approved')
            ->orderBy('id')
            ->get();

        $narratives = DB::table('father_narrative')
            ->where('name_id', $nameId)
            ->where('status', 'final')
            ->orderBy('id')
            ->get();

        return view('public.name', compact('name', 'verses', 'tafsir', 'hadith', 'commentary', 'narratives'));
    }

    public function mushaf()
    {
        $verses = DB::table('quran_verses as qv')
            ->join('name_verse_links as nvl', 'nvl.verse_id', '=', 'qv.id')
            ->select('qv.*')
            ->distinct()
            ->orderBy('qv.surah_number')
            ->orderBy('qv.ayah_number')
            ->get();

        return view('public.mushaf', compact('verses'));
    }
}

