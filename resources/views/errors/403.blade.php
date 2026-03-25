<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>
    <style>
        :root {
            --bg: #0f172a;
            --card: #111827;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --accent: #f59e0b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: radial-gradient(circle at top, #1f2937, var(--bg));
            color: var(--text);
            font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif;
            padding: 24px;
        }
        .card {
            width: min(640px, 100%);
            background: rgba(17, 24, 39, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        }
        h1 {
            margin: 0 0 8px;
            font-size: 40px;
            letter-spacing: 0.02em;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            margin-bottom: 14px;
            color: #111827;
            background: var(--accent);
            font-weight: 700;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 12px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        a {
            text-decoration: none;
            color: #111827;
            background: #f3f4f6;
            border-radius: 10px;
            padding: 10px 14px;
            font-weight: 600;
        }
        a.secondary {
            background: transparent;
            color: var(--text);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Akses Ditolak</span>
        <h1>403 Forbidden</h1>
        <p>
            Anda tidak memiliki izin untuk membuka halaman ini. Jika Anda merasa ini adalah kesalahan,
            silakan hubungi administrator sistem.
        </p>
        <div class="actions">
            <a href="{{ route('dashboard') }}">Kembali ke Dashboard</a>
            <a class="secondary" href="{{ route('public-display') }}">Buka Public Display</a>
        </div>
    </div>
</body>
</html>
