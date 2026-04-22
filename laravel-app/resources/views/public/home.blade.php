@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>إنه الله</h1>
        <p>نسخة خاصة للمراجعة العلمية قبل النشر العام.</p>
    </div>

    <div class="card">
        <h2>أسماء الله الحسنى</h2>
        @foreach($names as $name)
            <div><a href="{{ route('name.show', ['nameId' => $name->id]) }}">{{ $name->name_arabic }} ({{ $name->name_transliteration }})</a></div>
        @endforeach
    </div>

    <div class="card">
        <h2>سورة الفاتحة</h2>
        @foreach($verses as $verse)
            <div class="card quran">
                <strong>{{ $verse->surah_name }} {{ $verse->ayah_number }}</strong>
                <p>{{ $verse->ayah_text }}</p>
            </div>
        @endforeach
    </div>
@endsection

