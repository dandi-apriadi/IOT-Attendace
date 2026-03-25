<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Attendance Billboard - IoT System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&family=Manrope:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/premium-design.css') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #001E40 0%, #0D3B66 100%);
            color: #fff;
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            height: 100vh;
        }
        .billboard-container {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            padding: 30px;
            height: 100vh;
        }
        .stats-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .stat-card {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            text-align: center;
        }
        .stat-value {
            font-size: 3.5rem;
            font-weight: 800;
            margin: 10px 0;
            font-family: 'Manrope', sans-serif;
        }
        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-subtext {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.5);
            margin-top: 5px;
        }
        .live-section {
            grid-column: 2 / 4;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .live-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .live-indicator {
            width: 12px;
            height: 12px;
            background: #FF4444;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .stream-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            overflow-y: auto;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
            max-height: 100%;
        }
        .stream-item {
            background: rgba(0,102,204,0.15);
            border-left: 3px solid #0066CC;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .student-name {
            font-weight: 700;
            color: #fff;
            margin-bottom: 3px;
        }
        .student-info {
            color: rgba(255,255,255,0.6);
            font-size: 0.7rem;
        }
        .top-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0066CC, #00D9FF);
            transition: width 0.5s ease;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 700;
            margin-top: 8px;
        }
        .status-hadir { background: #1DB173; color: #fff; }
        .status-izin { background: #FFA500; color: #fff; }
        .status-alpa { background: #BA1A1A; color: #fff; }
    </style>
</head>
<body>
    <div class="billboard-container">
        <!-- Stats Section (Left) -->
        <div class="stats-section">
            <div class="stat-card" style="background: linear-gradient(135deg, rgba(29, 177, 115, 0.2), rgba(29, 177, 115, 0.05));">
                <div class="stat-label">Hadir</div>
                <div class="stat-value" style="color: #1DB173;">{{ $hadirCount }}</div>
                <div class="stat-subtext">dari {{ $totalMahasiswa }} mahasiswa</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.2), rgba(255, 152, 0, 0.05));">
                <div class="stat-label">Sakit/Izin</div>
                <div class="stat-value" style="color: #FFA500;">{{ $sabitIzinCount }}</div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, rgba(186, 26, 26, 0.2), rgba(186, 26, 26, 0.05));">
                <div class="stat-label">Alpa</div>
                <div class="stat-value" style="color: #BA1A1A;">{{ $alpaCount }}</div>
            </div>
        </div>

        <!-- Live Stream Section (Right) -->
        <div class="live-section">
            <div class="live-title">
                <span class="live-indicator"></span>
                LIVE ATTENDANCE STREAM
            </div>
            
            <div class="stream-grid">
                @forelse ($todayAbsensi->take(30) as $tap)
                    <div class="stream-item">
                        <div class="student-name">{{ $tap->mahasiswa?->nama_mahasiswa ?? 'N/A' }}</div>
                        <div class="student-info">
                            {{ $tap->jadwal?->mataKuliah?->kode_mk ?? 'N/A' }}<br>
                            {{ $tap->created_at->format('H:i:s') }}
                        </div>
                        <span class="status-badge status-{{ $tap->status }}">
                            {{ str_replace('_', ' ', ucfirst($tap->status)) }}
                        </span>
                    </div>
                @empty
                    <div style="grid-column: 1/-1; text-align: center; color: rgba(255,255,255,0.5); padding: 20px;">
                        Menunggu data presensi...
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Performance Section (Bottom Right) -->
        <div colspan="3" style="grid-column: 1 / 3; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-content: start; padding-top: 20px;">
            <div class="stat-card">
                <div class="stat-label">Persentase Kehadiran</div>
                <div class="stat-value">{{ $persentaseHadir }}%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ $persentaseHadir }}%"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Tap Hari Ini</div>
                <div class="stat-value">{{ $hadirCount + $sabitIzinCount + $alpaCount }}</div>
                <div class="stat-subtext">{{ now()->format('d M Y H:i') }}</div>
            </div>
        </div>

        <!-- Top Active Section -->
        <div style="grid-column: 3; grid-row: 2 / 4; display: flex; flex-direction: column; gap: 10px;">
            <h3 style="font-size: 1rem; margin-bottom: 5px;">TOP ACTIVE</h3>
            @forelse ($topMahasiswa as $item)
                <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px; border-left: 2px solid #0066CC;">
                    <div style="font-weight: 700; font-size: 0.9rem;">{{ $item->mahasiswa?->nama_mahasiswa ?? 'N/A' }}</div>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.6);">{{ $item->tap_count }} taps</div>
                </div>
            @empty
                <div style="text-align: center; color: rgba(255,255,255,0.5);">Belum ada data</div>
            @endforelse
        </div>
    </div>

    <script>
        // Auto-refresh every 5 seconds
        setTimeout(() => location.reload(), 5000);
    </script>
</body>
</html>
