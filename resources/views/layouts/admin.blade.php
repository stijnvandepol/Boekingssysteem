<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Admin</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, sans-serif; background: #0b1120; color: #e2e8f0; margin: 0; }
        header { background: #111827; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; }
        main { max-width: 1100px; margin: 24px auto; background: #111827; padding: 24px; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.35); }
        a { color: #93c5fd; text-decoration: none; }
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
        .card { background: #0f172a; border: 1px solid #1f2937; border-radius: 12px; padding: 16px; }
        label { font-weight: 600; display: block; margin-bottom: 6px; }
        input, select, button { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #334155; background: #0b1120; color: #e2e8f0; }
        button { background: #2563eb; border: none; cursor: pointer; font-weight: 600; }
        button:hover { background: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #1f2937; }
        .muted { color: #94a3b8; font-size: 0.9rem; }
        .error { background: #7f1d1d; color: #fee2e2; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .success { background: #14532d; color: #dcfce7; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .inline { display: flex; gap: 12px; align-items: center; }
        .inline form { margin: 0; }
    </style>
</head>
<body>
    <header>
        <div>
            <strong>{{ config('app.name', 'Rens Boekingssysteem') }}</strong>
            <span class="muted">Admin</span>
        </div>
        @auth
            <div class="inline">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a href="{{ route('admin.bookings.index') }}">Boekingen</a>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit">Uitloggen</button>
                </form>
            </div>
        @endauth
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
