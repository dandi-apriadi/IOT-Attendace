@extends('layouts.app')

@section('title', 'Detail Kehadiran: ' . $mahasiswa->nama)
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('matakuliah') }}" style="color: inherit; text-decoration: none;">Mata Kuliah</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('matakuliah.report', $mk->id) }}" style="color: inherit; text-decoration: none;">Report: {{ $mk->kode_mk }}</a>
    <span class="breadcrumb-sep">/</span>
    <span>Detail: {{ $mahasiswa->nama }}</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">
                Detail Kehadiran Per Pertemuan
            </h3>
            <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;">
                {{ $mk->kode_mk }} · {{ $mk->nama_mk }}
            </div>
            <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.15rem;">
                <strong>{{ $mahasiswa->nama }}</strong> ({{ $mahasiswa->nim }})
            </div>
        </div>
        <a href="{{ route('matakuliah.report', $mk->id) }}" class="btn-secondary" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.8rem;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: rgba(0, 102, 204, 0.05); padding: 1rem; border-left: 3px solid #0066CC; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Total Pertemuan</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0066CC;">{{ $totalMeetings }}</div>
        </div>
        <div style="background: rgba(29, 177, 115, 0.05); padding: 1rem; border-left: 3px solid #1DB173; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Hadir</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1DB173;">{{ $hadirCount }}</div>
        </div>
        <div style="background: rgba(255, 152, 0, 0.05); padding: 1rem; border-left: 3px solid #F59E0B; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Sakit/Izin</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #F59E0B;">{{ $sakitIzinCount }}</div>
        </div>
        <div style="background: rgba(186, 26, 26, 0.05); padding: 1rem; border-left: 3px solid #BA1A1A; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Alpa</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #BA1A1A;">{{ $alpaCount }}</div>
        </div>
        <div style="background: rgba(0, 102, 204, 0.05); padding: 1rem; border-left: 3px solid #0066CC; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Persentase</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0066CC;">{{ $persentase }}%</div>
        </div>
    </div>

    <!-- Attendance Per Meeting Table -->
    <h4 class="display-font" style="margin-bottom: 1rem; font-size: 1rem; color: var(--primary-blue-container);">
        Riwayat Kehadiran Per Pertemuan
    </h4>

    <table style="width: 100%; font-size: 0.85rem;">
        <thead>
            <tr>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb; width: 80px;">Pertemuan</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Tanggal</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb; width: 120px;">Status</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb; width: 120px;">Waktu Tap</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($meetingRows as $row)
                @php
                    $statusColors = [
                        'Hadir' => ['bg' => '#E6F6EC', 'text' => '#1DB173'],
                        'Telat' => ['bg' => '#FEF3C7', 'text' => '#92400E'],
                        'Sakit' => ['bg' => '#D1E7FF', 'text' => '#0066CC'],
                        'Izin' => ['bg' => '#D1E7FF', 'text' => '#0066CC'],
                        'Alpa' => ['bg' => '#FADBD8', 'text' => '#BA1A1A'],
                    ];
                    $colors = $statusColors[$row['status']] ?? ['bg' => '#E5E7EB', 'text' => '#374151'];
                @endphp
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.75rem; text-align: center; font-weight: 700;">P{{ $row['pertemuan'] }}</td>
                    <td style="padding: 0.75rem;">{{ \Carbon\Carbon::parse($row['tanggal'])->format('d F Y') }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="background: {{ $colors['bg'] }}; color: {{ $colors['text'] }}; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">
                            {{ $row['status'] }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center; font-family: monospace; color: #6b7280;">
                        {{ $row['waktu_tap'] !== '-' ? substr($row['waktu_tap'], 0, 8) : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem; color: #6b7280;">
                        Belum ada data kehadiran untuk mahasiswa ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
