<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Presensi Mahasiswa</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { margin-bottom: 14px; }
        .meta div { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; }
        th { background: #f3f4f6; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Presensi Mahasiswa</h1>
    <div class="meta">
        <div><strong>Semester:</strong> {{ $selectedSemesterLabel }}</div>
        <div><strong>Mata Kuliah:</strong> {{ $selectedMataKuliahLabel }}</div>
        <div><strong>Kelas:</strong> {{ $selectedKelasLabel }}</div>
        <div><strong>Status:</strong> {{ $selectedStatusLabel ?? 'Semua Status' }}</div>
        <div><strong>Dibuat:</strong> {{ $generatedAt }}</div>
    </div>

    <div class="meta" style="border:1px solid #d1d5db; border-radius:6px; padding:8px 10px; background:#f9fafb;">
        <div><strong>Total Mahasiswa:</strong> {{ (int) ($summary['total_mahasiswa'] ?? 0) }}</div>
        <div><strong>Total Pertemuan:</strong> {{ (int) ($summary['total_pertemuan'] ?? 0) }}</div>
        <div><strong>Total Hadir:</strong> {{ (int) ($summary['total_hadir'] ?? 0) }}</div>
        <div><strong>Rata-rata Kehadiran:</strong> {{ number_format((float) ($summary['rata_rata_persentase'] ?? 0), 2) }}%</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Mahasiswa</th>
                <th class="right">Total</th>
                <th class="right">Hadir</th>
                <th class="right">Sakit/Izin</th>
                <th class="right">Alpa</th>
                <th class="right">Persentase</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->nama }}</td>
                    <td class="right">{{ (int) $row->total }}</td>
                    <td class="right">{{ (int) $row->hadir }}</td>
                    <td class="right">{{ (int) $row->sakit_izin }}</td>
                    <td class="right">{{ (int) $row->alpa }}</td>
                    <td class="right">{{ number_format((float) $row->persentase, 2) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>