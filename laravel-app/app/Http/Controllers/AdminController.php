<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'pending_tafsir' => DB::table('tafsir_entries')->where('verification_status', 'pending')->count(),
            'pending_hadith' => DB::table('hadiths')->where('verification_status', 'pending')->count(),
            'pending_commentary' => DB::table('scholarly_commentary')->where('verification_status', 'pending')->count(),
            'reviewed_names' => DB::table('allah_names')->whereIn('verification_status', ['approved', 'approved_with_edit'])->count(),
            'total_names' => DB::table('allah_names')->count(),
        ];

        $queue = DB::table('tafsir_entries')
            ->where('verification_status', 'pending')
            ->selectRaw("'tafsir_entries' as content_type, id, scholar_name as title, tafsir_text as text")
            ->unionAll(
                DB::table('hadiths')
                    ->where('verification_status', 'pending')
                    ->selectRaw("'hadiths' as content_type, id, collection || ' ' || hadith_number as title, arabic_text as text")
            )
            ->unionAll(
                DB::table('scholarly_commentary')
                    ->where('verification_status', 'pending')
                    ->selectRaw("'scholarly_commentary' as content_type, id, scholar_name as title, commentary_text as text")
            )
            ->limit(100)
            ->get();

        return view('admin.dashboard', compact('stats', 'queue'));
    }

    public function review(Request $request)
    {
        $payload = $request->validate([
            'content_type' => ['required', 'in:tafsir_entries,hadiths,scholarly_commentary'],
            'content_id' => ['required', 'integer'],
            'action' => ['required', 'in:approve,approve_with_edit,reject'],
            'edited_text' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $newStatus = match ($payload['action']) {
            'approve' => 'approved',
            'approve_with_edit' => 'approved_with_edit',
            default => 'rejected'
        };

        $textColumn = [
            'tafsir_entries' => 'tafsir_text',
            'hadiths' => 'arabic_text',
            'scholarly_commentary' => 'commentary_text',
        ][$payload['content_type']];

        $oldStatus = DB::table($payload['content_type'])->where('id', $payload['content_id'])->value('verification_status');
        abort_if(! $oldStatus, 404);

        $update = [
            'verification_status' => $newStatus,
            'reviewer_notes' => $payload['notes'] ?? null,
            'updated_at' => Carbon::now(),
        ];

        if ($payload['action'] === 'approve_with_edit' && filled($payload['edited_text'])) {
            $update[$textColumn] = trim($payload['edited_text']);
        }

        DB::table($payload['content_type'])->where('id', $payload['content_id'])->update($update);

        DB::table('review_audit_log')->insert([
            'content_type' => $payload['content_type'],
            'content_id' => $payload['content_id'],
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reviewer' => optional($request->user())->name ?? 'father',
            'notes' => $payload['notes'] ?? null,
            'reviewed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('admin.dashboard');
    }

    public function addNarrative(Request $request)
    {
        $payload = $request->validate([
            'name_id' => ['required', 'integer'],
            'verse_id' => ['nullable', 'integer'],
            'narrative_text' => ['required', 'string'],
            'status' => ['required', 'in:draft,final'],
        ]);

        DB::table('father_narrative')->insert([
            'name_id' => $payload['name_id'],
            'verse_id' => $payload['verse_id'] ?? null,
            'narrative_text' => trim($payload['narrative_text']),
            'status' => $payload['status'],
            'created_date' => Carbon::now(),
            'updated_date' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return redirect()->route('admin.dashboard');
    }
}

