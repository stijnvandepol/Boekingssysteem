<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Admin</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

        * { box-sizing: border-box; }

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
            padding: 0;
            min-height: 100vh;
        }

        header {
            background: #0f172a;
            color: #f8fafc;
            padding: 16px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
        }

        header > div:first-child {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        header strong {
            font-size: 1.1rem;
        }

        header .muted {
            color: rgba(248, 250, 252, 0.7);
            font-size: 0.9rem;
        }

        main {
            max-width: 1100px;
            margin: 16px auto;
            background: rgba(255, 255, 255, 0.96);
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
            border: 1px solid rgba(229, 231, 235, 0.9);
            backdrop-filter: blur(6px);
            min-height: calc(100vh - 100px);
        }

        a { color: var(--accent); text-decoration: none; font-weight: 600; }

        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: 1fr; }
        @media (min-width: 640px) {
            .grid-2 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .grid-2 { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); }
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
        }

        .card h2 {
            margin: 0 0 8px 0;
            font-size: 1.2rem;
        }

        label { font-weight: 600; display: block; margin-bottom: 6px; color: var(--ink); font-size: 0.95rem; }
        input, select, button {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
            font-family: inherit;
            font-size: 1rem;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }

        input + label, select + label {
            margin-top: 12px;
        }

        button {
            background: var(--accent);
            border: none;
            color: #fff;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
            margin-top: 8px;
        }

        button:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(29, 78, 216, 0.2);
        }

        button:active {
            transform: translateY(0);
        }

        .button {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid var(--accent);
            color: var(--accent);
            background: transparent;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.15s ease;
        }

        .button:hover { background: var(--accent); color: #fff; }

        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 8px; border-bottom: 1px solid #e5e7eb; font-size: 0.95rem; }
        th { background: #f8fafc; font-weight: 600; color: var(--ink); }
        
        .muted { color: var(--muted); font-size: 0.9rem; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-size: 0.95rem; }
        .success { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-size: 0.95rem; }
        
        .inline { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .inline form { margin: 0; }
        .inline button { margin: 0; width: auto; padding: 6px 12px; }

        .calendar-scroll { overflow-x: auto; margin: 0 -16px; padding: 0 16px; }
        .calendar { min-width: 100%; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
        .calendar-grid { display: grid; grid-template-columns: 70px repeat(7, 1fr); }
        @media (max-width: 640px) {
            .calendar-grid { grid-template-columns: 50px repeat(7, 1fr); }
        }
        .calendar-head { background: #f8fafc; font-weight: 600; color: #0f172a; }
        .calendar-cell { border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; padding: 8px 4px; min-height: 50px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; text-align: center; }
        @media (min-width: 640px) {
            .calendar-cell { padding: 10px; font-size: 0.9rem; }
        }
        .calendar-cell:last-child { border-right: none; }
        .calendar-time { background: #f8fafc; font-weight: 600; color: #0f172a; font-size: 0.75rem; }
        @media (min-width: 640px) {
            .calendar-time { font-size: 0.9rem; }
        }
        .calendar-slot { background: #ffffff; cursor: pointer; transition: background 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease; }
        .calendar-slot:hover { background: #eff6ff; box-shadow: inset 0 0 0 1px rgba(29, 78, 216, 0.3); transform: translateY(-1px); }
        .calendar-busy { background: #ecfeff; color: #0f766e; font-weight: 600; cursor: not-allowed; }
        .calendar-selected { background: #dbeafe; box-shadow: inset 0 0 0 1px rgba(29, 78, 216, 0.55); color: #1e3a8a; font-weight: 600; }

        /* Mobile-friendly improvements */
        @media (max-width: 640px) {
            main { margin: 8px auto; padding: 12px; width: calc(100% - 16px); }
            header { padding: 12px; font-size: 0.95rem; }
            header > div:first-child { flex: 1; }
            header .inline { flex: 1; width: 100%; }
            header .inline { gap: 4px; }
            header a, header button { font-size: 0.85rem; padding: 6px 8px; }
            .card { padding: 12px; margin-bottom: 8px; }
            .card h2 { font-size: 1.1rem; margin-bottom: 12px; }
            label { font-size: 0.9rem; }
            input, select, button { font-size: 16px; padding: 12px; }
            .grid-2 { grid-template-columns: 1fr; }
            table { font-size: 0.85rem; }
            th, td { padding: 8px 4px; }
            .muted { font-size: 0.8rem; }
            .button { font-size: 0.85rem; padding: 6px 10px; }
        }

        @media (max-width: 900px) {
            main { margin: 12px auto; padding: 16px; }
        }

        /* Touch-friendly improvements */
        @media (hover: none) {
            button, .button { padding: 12px 16px; }
            input, select { padding: 12px; font-size: 16px; }
        }

        /* Landscape orientation fixes */
        @media (max-height: 600px) {
            header { padding: 8px 12px; }
            main { margin: 8px auto; }
            .card { padding: 12px; }
        }
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
                <a href="{{ route('admin.settings') }}">Instellingen</a>
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
