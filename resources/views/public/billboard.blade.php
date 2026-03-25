<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billboard | Poltek Manado Precense</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Manrope:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/premium-design.css') }}">
    <style>
        body { background: #000; color: #fff; padding: 40px; }
        .grid-display { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .student-tile { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 15px; text-align: center; }
        .student-tile.active { background: rgba(252, 212, 0, 0.1); border: 2px solid var(--kinetic-yellow); }
    </style>
</head>
<body>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <div>
            <h1 class="display-font" style="font-size: 3rem;">KEHADIRAN LIVE</h1>
            <p style="font-size: 1.5rem; opacity: 0.6;">LAB KOMPUTER 1 - IK-2A</p>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 4rem; font-weight: 800; color: var(--kinetic-yellow);">09:12</div>
            <div style="font-size: 1rem; opacity: 0.6;">RABU, 25 MARET 2026</div>
        </div>
    </div>

    <div class="grid-display">
        <div class="student-tile active">
            <div style="font-size: 1.2rem; font-weight: 800;">DANDI A.</div>
            <div style="font-size: 0.8rem; color: var(--kinetic-yellow);">HADIR</div>
        </div>
        <div class="student-tile active">
            <div style="font-size: 1.2rem; font-weight: 800;">AISYAH P.</div>
            <div style="font-size: 0.8rem; color: var(--kinetic-yellow);">HADIR</div>
        </div>
        <div class="student-tile">
            <div style="font-size: 1.2rem; font-weight: 800;">BUDI S.</div>
            <div style="font-size: 0.8rem; opacity: 0.3;">BELUM TAP</div>
        </div>
        <!-- ... more tiles ... -->
    </div>
</body>
</html>
