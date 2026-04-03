<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Detail Sesi - {{ $mataKuliah->kode_mk }} - {{ $selectedDate }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; margin: 20px; }
        .meta { margin-bottom: 14px; font-size: 13px; color: #4b5563; }
        .cards { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-bottom: 14px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; }
        .label { font-size: 11px; text-transform: uppercase; color: #6b7280; }
        .value { font-size: 20px; font-weight: 700; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 7px; }
        th { background: #f9fafb; text-align: left; }
        .center { text-align: center; }
    </style>
</head>
<body>

    <h2 style="margin: 0 0 6px;">Detail Sesi Jadwal</h2>
    <div class="meta">
        {{ $mataKuliah->nama_mk }} ({{ $mataKuliah->kode_mk }}) | Kelas {{ $kelas->nama_kelas }} | {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('d F Y') }}
    </div>

    <div class="cards">
        <div class="card"><div class="label">Total</div><div class="value">{{ $summary['total_students'] }}</div></div>
        <div class="card"><div class="label">Hadir</div><div class="value">{{ $summary['hadir'] }}</div></div>
        <div class="card"><div class="label">Telat</div><div class="value">{{ $summary['telat'] }}</div></div>
        <div class="card"><div class="label">Sakit/Izin</div><div class="value">{{ $summary['sakit'] + $summary['izin'] }}</div></div>
        <div class="card"><div class="label">Alpa</div><div class="value">{{ $summary['alpa'] }}</div></div>
        <div class="card"><div class="label">Pending</div><div class="value">{{ $summary['pending'] }}</div></div>
    </div>

    <table>
        <thead>
        <tr>
            <th>NIM</th>
            <th>Nama</th>
            <th class="center">Status</th>
            <th class="center">Metode</th>
            <th class="center">Waktu Tap</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($studentRows as $row)
            <tr>
                <td>{{ $row['nim'] }}</td>
                <td>{{ $row['nama'] }}</td>
                <td class="center">{{ $row['status'] === 'Pending' ? 'Belum Absensi' : $row['status'] }}</td>
                <td class="center">{{ $row['metode'] }}</td>
                <td class="center">{{ $row['waktu_tap'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="center">Belum ada data siswa untuk kelas ini.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
