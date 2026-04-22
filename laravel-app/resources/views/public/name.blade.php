@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>{{ $name->name_arabic }} — {{ $name->name_transliteration }}</h1>
        <p>{{ $name->meaning }}</p>
    </div>

    <div class="card">
        <h2>الآيات</h2>
        @foreach($verses as $verse)
            <div class="card quran">
                <strong>{{ $verse->surah_name }} {{ $verse->ayah_number }}</strong> ({{ $verse->context_note }})
                <p>{{ $verse->ayah_text }}</p>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h2>التفسير المعتمد</h2>
        @forelse($tafsir as $row)
            <div class="card"><strong>{{ $row->scholar_name }}</strong><p>{{ $row->tafsir_text }}</p></div>
        @empty
            <p>لا يوجد تفسير معتمد بعد.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>الأحاديث المعتمدة</h2>
        @forelse($hadith as $row)
            <div class="card hadith"><strong>{{ $row->collection }} {{ $row->hadith_number }}</strong><p>{{ $row->arabic_text }}</p></div>
        @empty
            <p>لا يوجد حديث معتمد بعد.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>أقوال العلماء</h2>
        @forelse($commentary as $row)
            <div class="card"><strong>{{ $row->scholar_name }}</strong> — {{ $row->commentary_text }}</div>
        @empty
            <p>لا يوجد أقوال معتمدة بعد.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>تعليق الوالد</h2>
        @forelse($narratives as $row)
            <div class="card">{{ $row->narrative_text }}</div>
        @empty
            <p>لا يوجد تعليق نهائي بعد.</p>
        @endforelse
    </div>
@endsection

