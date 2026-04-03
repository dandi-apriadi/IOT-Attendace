<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Presensi Mahasiswa</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        .meta { margin-bottom: 12px; }
        .meta div { margin-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Riwayat Presensi Mahasiswa</h1>
    <div class="meta">
        <div><strong>NIM:</strong> {{ $mahasiswa->nim }}</div>
        <div><strong>Nama:</strong> {{ $mahasiswa->nama }}</div>
        <div><strong>Semester:</strong> {{ $selectedSemesterLabel }}</div>
        <div><strong>Mata Kuliah:</strong> {{ $selectedMataKuliahLabel }}</div>
        <div><strong>Kelas:</strong> {{ $selectedKelasLabel }}</div>
        <div><strong>Periode:</strong> {{ $selectedStartDate !== '' ? $selectedStartDate : '-' }} s/d {{ $selectedEndDate !== '' ? $selectedEndDate : '-' }}</div>
        <div><strong>Dibuat:</strong> {{ $generatedAt }}</div>
    </div>

    <div class="meta" style="border:1px solid #d1d5db; border-radius:6px; padding:8px 10px; background:#f9fafb;">
        <div><strong>Total Absensi:</strong> {{ (int) $totalAbsensi }}</div>
        <div><strong>Hadir:</strong> {{ (int) $hadirCount }}</div>
        <div><strong>Sakit/Izin:</strong> {{ (int) $sabitIzinCount }}</div>
        <div><strong>Alpa:</strong> {{ (int) $alpaCount }}</div>
        <div><strong>Persentase Hadir:</strong> {{ number_format((float) $persentaseHadir, 1) }}%</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Waktu Tap</th>
                <th>Mata Kuliah</th>
                <th>Kode MK</th>
                <th>Kelas</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $record)
                <tr>
                    <td>{{ $record->tanggal }}</td>
                    <td>{{ $record->waktu_tap ? substr((string) $record->waktu_tap, 0, 8) : '-' }}</td>
                    <td>{{ $record->jadwal?->mataKuliah?->nama_mk ?? '-' }}</td>
                    <td>{{ $record->jadwal?->mataKuliah?->kode_mk ?? '-' }}</td>
                    <td>{{ $record->jadwal?->kelas?->nama_kelas ?? '-' }}</td>
                    <td>{{ $statusLabels[$record->status] ?? $record->status }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Tidak ada data riwayat sesuai filter.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
