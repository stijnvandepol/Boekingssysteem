<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Boeken</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

        :root {
            color-scheme: light;
            --ink: #0f172a;
            --ink-soft: #1f2937;
            --accent: #0f766e;
            --accent-dark: #0b5f58;
            --bg: #f6f2ec;
            --card: #ffffff;
            --line: #e7e2da;
            --muted: #6b7280;
        }

        body {
            font-family: "Manrope", "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #ffffff 0%, #f6f2ec 45%, #efe7dd 100%);
            color: var(--ink-soft);
            margin: 0;
        }

        header {
            background: linear-gradient(120deg, #111827 0%, #1f2937 55%, #0f766e 140%);
            color: #f8fafc;
            padding: 28px 24px;
        }

        header .muted {
            color: rgba(248, 250, 252, 0.72);
        }

        main {
            max-width: 1040px;
            margin: 28px auto 40px;
            background: rgba(255, 255, 255, 0.9);
            padding: 28px;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12);
            border: 1px solid rgba(231, 226, 218, 0.8);
            backdrop-filter: blur(6px);
        }

        h1 {
            font-family: "Fraunces", serif;
            font-size: 2rem;
            margin: 0 0 8px;
        }

        h2 {
            font-family: "Fraunces", serif;
            font-size: 1.2rem;
            margin: 0 0 12px;
        }

        .grid { display: grid; gap: 18px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }

        label { font-weight: 600; display: block; margin-bottom: 6px; color: var(--ink); }

        input, select, button {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid #d6d3cd;
            font-family: inherit;
            font-size: 0.95rem;
            background: #fff;
        }

        button {
            background: var(--accent);
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 600;
            letter-spacing: 0.01em;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        button:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(15, 118, 110, 0.22);
        }

        .slots { display: grid; gap: 10px; }

        .slot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid var(--line);
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff;
        }

        a.slot {
            color: inherit;
            text-decoration: none;
            transition: border 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
        }

        a.slot:hover {
            border-color: rgba(15, 118, 110, 0.4);
            box-shadow: 0 10px 18px rgba(15, 118, 110, 0.12);
            transform: translateY(-1px);
        }

        .muted { color: var(--muted); font-size: 0.9rem; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; }
        .success { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; }
        .inline { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    </style>
</head>
<body>
    <header>
        <h1>{{ config('app.name', 'Boekingssysteem') }}</h1>
        <div class="muted">Boek een tijdslot</div>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
