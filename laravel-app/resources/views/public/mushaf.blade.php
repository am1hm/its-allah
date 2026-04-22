@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>التصفح بترتيب المصحف</h1>
        @foreach($verses as $verse)
            <div class="card quran">
                <strong>{{ $verse->surah_name }} {{ $verse->ayah_number }}</strong>
                <p>{{ $verse->ayah_text }}</p>
            </div>
        @endforeach
    </div>
@endsection

