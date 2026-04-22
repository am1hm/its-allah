<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Innahu Allah</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap');
        body { font-family: Tajawal, sans-serif; background: #f7f7f1; color: #1f2937; margin: 0; }
        .container { max-width: 1000px; margin: 0 auto; padding: 16px; }
        .card { background: white; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
        .quran { background: #f3efe0; border-right: 4px solid #365c4f; }
        .hadith { background: #eef6ff; border-right: 4px solid #325f9e; }
        input, textarea, select { width: 100%; padding: 8px; margin-bottom: 10px; }
        .btn { background: #365c4f; color: #fff; border: 0; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
        a { color: #365c4f; text-decoration: none; }
        .topbar { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; }
    </style>
</head>
<body>
<div class="container">
    <div class="card topbar">
        <a href="{{ route('home') }}">إنه الله</a>
        <div>
            <a href="{{ route('mushaf') }}">ترتيب المصحف</a> |
            <a href="{{ route('admin.dashboard') }}">Admin</a> |
            @auth
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button class="btn" type="submit">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}">Login</a>
            @endauth
        </div>
    </div>
    @yield('content')
</div>
</body>
</html>

