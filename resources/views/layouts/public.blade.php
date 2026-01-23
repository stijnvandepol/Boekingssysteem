<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Boeken</title>
    <style>
        :root { color-scheme: light; }
        body { font-family: "Segoe UI", Tahoma, sans-serif; background: #f5f7fb; color: #1f2937; margin: 0; }
        header { background: #0f172a; color: #fff; padding: 24px; }
        main { max-width: 980px; margin: 24px auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); }
        h1 { margin: 0 0 12px; }
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; }
        label { font-weight: 600; display: block; margin-bottom: 6px; }
        input, select, button { width: 100%; padding: 10px 12px; border-radius: 8px; border: 1px solid #d1d5db; }
        button { background: #0f172a; color: #fff; border: none; cursor: pointer; font-weight: 600; }
        button:hover { background: #111827; }
        .slots { display: grid; gap: 10px; }
        .slot { display: flex; align-items: center; justify-content: space-between; border: 1px solid #e5e7eb; padding: 10px 12px; border-radius: 8px; }
        .muted { color: #6b7280; font-size: 0.9rem; }
        .error { background: #fee2e2; color: #991b1b; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .success { background: #dcfce7; color: #166534; padding: 10px 12px; border-radius: 8px; margin-bottom: 12px; }
        .inline { display: flex; gap: 8px; align-items: center; }
    </style>
</head>
<body>
    <header>
        <h1>{{ config('app.name', 'Rens Boekingssysteem') }}</h1>
        <div class="muted">Boek een tijdslot</div>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
