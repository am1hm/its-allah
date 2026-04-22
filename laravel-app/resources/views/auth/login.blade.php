@extends('layouts.app')

@section('content')
    <div class="card">
        <h1>Admin Login</h1>
        @if ($errors->any())
            <p style="color:#b91c1c;">{{ $errors->first() }}</p>
        @endif
        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button class="btn" type="submit">Sign In</button>
        </form>
    </div>
@endsection

