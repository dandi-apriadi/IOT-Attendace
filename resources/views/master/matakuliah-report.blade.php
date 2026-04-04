@extends('layouts.app')

@section('title', 'Report Absensi: ' . $mk->nama_mk)
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('matakuliah') }}" style="color: inherit; text-decoration: none;">Mata Kuliah</a>
    <span class="breadcrumb-sep">/</span>
    <span>Report: {{ $mk->nama_mk }}</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">
                Report Absensi: {{ $mk->nama_mk }}
            </h3>
            <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;">
                {{ $mk->kode_mk }} · {{ $mk->sks }} SKS
            </div>
        </div>
        <a href="{{ route('matakuliah') }}" class="btn-secondary" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.8rem;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Semester Info Badge -->
    @if ($mk->semesterAkademik)
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 12px;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size:0.74rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Semester:</span>
                <span style="padding:0.35rem 0.8rem; border-radius:999px; background:#eef4ff; color:#003366; font-size:0.85rem; font-weight:700;">
                    <i class="fas fa-calendar-alt" style="margin-right:0.4rem;"></i>{{ $mk->semesterAkademik->display_name }}
                </span>
                <span style="font-size:0.75rem; color:#6b7280;">
                    ({{ $mk->semesterAkademik->tanggal_mulai?->format('d M Y') }} — {{ $mk->semesterAkademik->tanggal_selesai?->format('d M Y') }})
                </span>
            </div>
            <a href="{{ route('matakuliah.report.export', $mk->id) }}" class="btn-kinetic" style="text-decoration:none; padding:0.6rem 1.2rem; font-size:0.82rem; background:#1DB173;">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>
    @else
        <div style="margin-bottom: 1.5rem; background: #FEF3C7; color: #92400E; padding: 0.75rem 1rem; border-radius: 8px;">
            <i class="fas fa-exclamation-triangle"></i> Mata kuliah ini belum terikat semester.
        </div>
    @endif

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: rgba(0, 102, 204, 0.05); padding: 1rem; border-left: 3px solid #0066CC; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Total Mahasiswa</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0066CC;">{{ $totalMahasiswa }}</div>
        </div>
        <div style="background: rgba(29, 177, 115, 0.05); padding: 1rem; border-left: 3px solid #1DB173; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Total Hadir</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1DB173;">{{ $totalHadir }}</div>
        </div>
        <div style="background: rgba(255, 152, 0, 0.05); padding: 1rem; border-left: 3px solid #F59E0B; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Sakit/Izin</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #F59E0B;">{{ $totalSakitIzin }}</div>
        </div>
        <div style="background: rgba(186, 26, 26, 0.05); padding: 1rem; border-left: 3px solid #BA1A1A; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Alpa</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #BA1A1A;">{{ $totalAlpa }}</div>
        </div>
        <div style="background: rgba(0, 102, 204, 0.05); padding: 1rem; border-left: 3px solid #0066CC; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Rata-rata Hadir</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0066CC;">{{ $avgPersentase }}%</div>
        </div>
    </div>

    <!-- Attendance Stats Table -->
    <h4 class="display-font" style="margin-bottom: 1rem; font-size: 1rem; color: var(--primary-blue-container);">
        Statistik Kehadiran Per Mahasiswa
    </h4>

    <table style="width: 100%; font-size: 0.85rem;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">NIM</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Nama Mahasiswa</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Total</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Hadir</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Sakit/Izin</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Alpa</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Persentase</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Progress</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stats as $row)
                @php
                    $progressPercent = $row->persentase;
                    $barColor = $progressPercent >= 75 ? '#1DB173' : ($progressPercent >= 60 ? '#F59E0B' : '#BA1A1A');
                    $bgColor = $progressPercent >= 75 ? '#E6F6EC' : ($progressPercent >= 60 ? '#FEF3C7' : '#FADBD8');
                @endphp
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.75rem; font-family: monospace; color: #0066CC;">{{ $row->nim }}</td>
                    <td style="padding: 0.75rem;">
                        <a href="{{ route('matakuliah.report.student', ['id' => $mk->id, 'mahasiswaId' => $row->id]) }}" 
                           style="font-weight: 600; color: #0066CC; text-decoration: none; cursor: pointer;"
                           onmouseover="this.style.textDecoration='underline'" 
                           onmouseout="this.style.textDecoration='none'">
                            {{ $row->nama }}
                        </a>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">{{ $row->total }}</td>
                    <td style="padding: 0.75rem; text-align: center; color: #1DB173; font-weight: 700;">{{ $row->hadir }}</td>
                    <td style="padding: 0.75rem; text-align: center; color: #F59E0B;">{{ $row->sakit_izin }}</td>
                    <td style="padding: 0.75rem; text-align: center; color: #BA1A1A;">{{ $row->alpa }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="background: {{ $bgColor }}; color: {{ $barColor }}; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;">
                            {{ number_format($progressPercent, 1) }}%
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <div style="background: #e5e7eb; border-radius: 999px; height: 6px; width: 80px; margin: 0 auto; overflow: hidden;">
                            <div style="background: {{ $barColor }}; height: 100%; width: {{ $progressPercent }}%; border-radius: 999px;"></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 2rem; color: #6b7280;">
                        Belum ada data absensi untuk mata kuliah ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $stats->links() }}
    </div>
</div>
@endsection
