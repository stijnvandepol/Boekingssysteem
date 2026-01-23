<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Admin</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

        :root {
            color-scheme: light;
            --ink: #0f172a;
            --ink-soft: #1f2937;
            --accent: #1d4ed8;
            --accent-dark: #1e40af;
            --bg: #f4f6fb;
            --card: #ffffff;
            --line: #e5e7eb;
            --muted: #6b7280;
        }

        body {
            font-family: "Manrope", "Segoe UI", sans-serif;
            background: radial-gradient(circle at top right, #ffffff 0%, #f4f6fb 42%, #eef2f9 100%);
            color: var(--ink-soft);
            margin: 0;
        }

        header {
            background: #0f172a;
            color: #f8fafc;
            padding: 22px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header .muted {
            color: rgba(248, 250, 252, 0.7);
        }

        main {
            max-width: 1100px;
            margin: 28px auto 40px;
            background: rgba(255, 255, 255, 0.96);
            padding: 26px;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(229, 231, 235, 0.9);
            backdrop-filter: blur(6px);
        }

        a { color: var(--accent); text-decoration: none; font-weight: 600; }

        .grid { display: grid; gap: 18px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        label { font-weight: 600; display: block; margin-bottom: 6px; color: var(--ink); }
        input, select, button {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            background: #fff;
            font-family: inherit;
        }

        button {
            background: var(--accent);
            border: none;
            color: #fff;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        button:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 12px 20px rgba(29, 78, 216, 0.25);
        }

        .button {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 10px;
            border: 1px solid var(--accent);
            color: var(--accent);
            background: transparent;
            font-weight: 600;
        }

        .button:hover { background: var(--accent); color: #fff; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
        .muted { color: var(--muted); font-size: 0.9rem; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; }
        .success { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; }
        .inline { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .inline form { margin: 0; }
    </style>
</head>
<body>
    <header>
        <div>
            <strong>{{ config('app.name', 'Boekingssysteem') }}</strong>
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
