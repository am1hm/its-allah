@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>دخول المراجع</h1>
        @if ($errors->any())
            <p style="color:#b91c1c;">{{ $errors->first() }}</p>
        @endif
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <label>البريد الإلكتروني</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            <label>كلمة المرور</label>
            <input type="password" name="password" required>
            <button class="btn" type="submit">دخول</button>
        </form>
    </div>
@endsection
