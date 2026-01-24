<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Boeken</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * { box-sizing: border-box; }

        :root {
            color-scheme: light;
            --ink: #050f1f;
            --ink-soft: #1a2332;
            --accent: #059669;
            --accent-hover: #047857;
            --accent-light: #d1fae5;
            --bg: #f8f9fb;
            --bg-darker: #eef0f5;
            --card: #ffffff;
            --border: #d4d9e3;
            --border-light: #e5e8f0;
            --muted: #6b7588;
            --muted-light: #94a3b8;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #f8f9fb 0%, #eef0f5 100%);
            color: var(--ink-soft);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        header {
            background: linear-gradient(135deg, #050f1f 0%, #1a2332 100%);
            color: #f8f9fb;
            padding: 20px 24px;
            box-shadow: 0 2px 8px rgba(5, 15, 31, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        header .muted {
            color: rgba(248, 249, 251, 0.64);
            font-size: 0.9rem;
            margin-top: 4px;
        }

        main {
            max-width: 1080px;
            margin: 32px auto;
            background: rgba(255, 255, 255, 0.98);
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(5, 15, 31, 0.04), 0 20px 40px rgba(5, 15, 31, 0.08);
            border: 1px solid var(--border-light);
            backdrop-filter: blur(8px);
        }

        h2 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0 0 16px;
            color: var(--ink);
            letter-spacing: -0.3px;
        }

        .grid { display: grid; gap: 24px; }
        .grid-2 { grid-template-columns: 1fr; }
        @media (min-width: 900px) {
            .grid-2 { grid-template-columns: 1fr 1fr; }
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(5, 15, 31, 0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card:hover {
            border-color: var(--border);
            box-shadow: 0 4px 12px rgba(5, 15, 31, 0.06);
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            color: var(--ink);
            font-size: 0.95rem;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 1rem;
            background: #fff;
            color: var(--ink-soft);
            transition: all 0.2s ease;
            margin-bottom: 16px;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
            background: rgba(5, 150, 105, 0.01);
        }

        button {
            width: 100%;
            padding: 12px 16px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: -0.2px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
        }

        button:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .slots { display: grid; gap: 12px; }

        .slot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid var(--border-light);
            padding: 14px 16px;
            border-radius: 8px;
            background: #fff;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        a.slot {
            color: inherit;
            text-decoration: none;
        }

        a.slot:hover {
            border-color: var(--accent);
            background: rgba(5, 150, 105, 0.02);
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.12);
            transform: translateY(-1px);
        }

        .slot strong {
            color: var(--ink);
            font-weight: 600;
        }

        .muted {
            color: var(--muted);
            font-size: 0.9rem;
        }

        .error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #7f1d1d;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .success {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            color: #166534;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

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
