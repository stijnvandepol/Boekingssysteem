<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Admin</title>
    <style>
<<<<<<< HEAD
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * { box-sizing: border-box; }

        :root {
            color-scheme: light;
            --ink: #050f1f;
            --ink-soft: #1a2332;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --accent-light: #dbeafe;
            --danger: #dc2626;
            --danger-hover: #b91c1c;
            --success: #059669;
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
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(5, 15, 31, 0.08);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        header > div:first-child {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        header strong {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        header .muted {
            color: rgba(248, 249, 251, 0.64);
            font-size: 0.85rem;
        }

        main {
            max-width: 1200px;
            margin: 24px auto;
            background: rgba(255, 255, 255, 0.98);
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(5, 15, 31, 0.04), 0 20px 40px rgba(5, 15, 31, 0.08);
            border: 1px solid var(--border-light);
            backdrop-filter: blur(8px);
            min-height: calc(100vh - 100px);
        }

        a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        a:hover {
            color: var(--accent-hover);
        }

        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: 1fr; }
        @media (min-width: 768px) {
            .grid-2 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .grid-2 { grid-template-columns: repeat(3, 1fr); }
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border-light);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(5, 15, 31, 0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card:hover {
            border-color: var(--border);
            box-shadow: 0 4px 12px rgba(5, 15, 31, 0.06);
        }

        .card h2 {
            margin: 0 0 12px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--ink);
        }

        label {
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            color: var(--ink);
            font-size: 0.95rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: #fff;
            font-family: inherit;
            font-size: 1rem;
            color: var(--ink-soft);
            transition: all 0.2s ease;
            margin-bottom: 16px;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: rgba(37, 99, 235, 0.01);
        }

        button, .button {
            padding: 12px 16px;
            border-radius: 8px;
            border: none;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1rem;
            letter-spacing: -0.2px;
        }

        button {
            background: var(--accent);
            color: #fff;
            width: 100%;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
        }

        button:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .button {
            display: inline-block;
            padding: 10px 14px;
            background: transparent;
            color: var(--accent);
            border: 1px solid var(--accent);
            font-size: 0.95rem;
            width: auto;
        }

        .button:hover {
            background: var(--accent);
            color: #fff;
        }

        .button.danger {
            color: var(--danger);
            border-color: var(--danger);
        }

        .button.danger:hover {
            background: var(--danger);
            color: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid var(--border-light);
        }

        th {
            background: rgba(5, 15, 31, 0.02);
            font-weight: 600;
            color: var(--ink);
            font-size: 0.9rem;
            letter-spacing: -0.2px;
        }

        tr:hover {
            background: rgba(5, 15, 31, 0.01);
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

        .warning {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            color: #92400e;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .inline {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .inline form { margin: 0; }
        .inline button, .inline .button { margin: 0; width: auto; padding: 8px 12px; font-size: 0.9rem; }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge.success { background: rgba(5, 150, 105, 0.1); color: var(--success); }
        .badge.danger { background: rgba(220, 38, 38, 0.1); color: var(--danger); }
        .badge.muted { background: rgba(107, 117, 136, 0.1); color: var(--muted); }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                padding: 12px 16px;
                flex-direction: column;
                align-items: flex-start;
            }

            header > div:first-child {
                width: 100%;
            }

            main {
                margin: 12px;
                padding: 16px;
                width: calc(100% - 24px);
                min-height: auto;
            }

            .grid-2 {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 16px;
            }

            input, select, textarea, button {
                font-size: 16px;
                padding: 12px;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 10px 8px;
            }

            .muted {
                font-size: 0.85rem;
            }
        }

        @media (hover: none) {
            button, .button {
                padding: 12px 16px;
            }

            input, select {
                font-size: 16px;
                padding: 12px;
            }
        }
=======
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
>>>>>>> parent of 1568204 (errors fixed)
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
