@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>Reviewer Dashboard</h1>
        <p>Pending Tafsir: {{ $stats['pending_tafsir'] }}</p>
        <p>Pending Hadith: {{ $stats['pending_hadith'] }}</p>
        <p>Pending Commentary: {{ $stats['pending_commentary'] }}</p>
        <p>Reviewed Names: {{ $stats['reviewed_names'] }} / {{ $stats['total_names'] }}</p>
    </div>

    <div class="card">
        <h2>Review Queue</h2>
        @forelse($queue as $item)
            <form class="card" method="POST" action="{{ route('admin.review') }}">
                @csrf
                <input type="hidden" name="content_type" value="{{ $item->content_type }}">
                <input type="hidden" name="content_id" value="{{ $item->id }}">
                <strong>{{ $item->content_type }} #{{ $item->id }} — {{ $item->title }}</strong>
                <p>{{ $item->text }}</p>
                <label>Edited Text (optional for approve_with_edit)</label>
                <textarea name="edited_text"></textarea>
                <label>Notes</label>
                <textarea name="notes"></textarea>
                <label>Action</label>
                <select name="action">
                    <option value="approve">Approve</option>
                    <option value="approve_with_edit">Approve with Edit</option>
                    <option value="reject">Reject</option>
                </select>
                <button class="btn" type="submit">Submit Review</button>
            </form>
        @empty
            <p>No pending entries.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>Add Father Narrative</h2>
        <form method="POST" action="{{ route('admin.narrative') }}">
            @csrf
            <label>Name ID</label>
            <input type="number" name="name_id" required>
            <label>Verse ID (optional)</label>
            <input type="number" name="verse_id">
            <label>Narrative Text</label>
            <textarea name="narrative_text" required></textarea>
            <label>Status</label>
            <select name="status">
                <option value="draft">draft</option>
                <option value="final">final</option>
            </select>
            <button class="btn" type="submit">Add Narrative</button>
        </form>
    </div>
@endsection

