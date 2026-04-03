@extends('layouts.app')

@section('title', 'Laporan Presensi')
@section('breadcrumb')
    <span>Admin & Reports</span>
    <span class="breadcrumb-sep">/</span>
    <span>Laporan</span>
@endsection

@section('styles')
<style>
    .report-card {
        text-decoration: none;
        color: inherit;
        display: block;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        background: #fff;
    }

    .report-card-semester.is-selected {
        border-color: #0066cc;
        background: rgba(0, 102, 204, 0.05);
    }

    .report-card-course.is-selected {
        border-color: #1db173;
        background: rgba(29, 177, 115, 0.05);
    }

    .report-card-class.is-selected {
        border-color: #f59e0b;
        background: rgba(245, 158, 11, 0.06);
    }

    .status-chip {
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.33rem 0.58rem;
        border-radius: 999px;
        background: #eef2f7;
        color: #4b5563;
    }

    .status-chip.is-active {
        background: #e8f2ff;
        color: #1e3a8a;
    }

    .outlier-row {
        background: #fff6f6;
    }

    .outlier-row td {
        border-top: 1px solid #fee2e2;
        border-bottom: 1px solid #fee2e2;
    }

    .outlier-badge {
        display: inline-block;
        margin-left: 0.4rem;
        font-size: 0.67rem;
        font-weight: 800;
        color: #b91c1c;
        background: #fee2e2;
        padding: 0.15rem 0.42rem;
        border-radius: 999px;
        vertical-align: middle;
    }
</style>
@endsection

@section('content')
<div class="glass-card">
    @php
        $statusWarningQuery = array_filter([
            'status_filter' => $selectedStatusFilter,
            'warning_threshold' => $selectedWarningThreshold,
        ]);
    @endphp
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Laporan Presensi Mahasiswa per Semester</h3>
            <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;">Rekapitulasi kehadiran mahasiswa berdasarkan semester, kelas, dan mata kuliah</div>
        </div>
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; justify-content:flex-end;">
            <a href="{{ route('reports.export.excel', request()->query()) }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.8rem; font-size:0.78rem; background:#1DB173; box-shadow:none;">Export Excel</a>
            <a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.8rem; font-size:0.78rem; background:#0066CC; box-shadow:none;">Export PDF</a>
            <a href="{{ route('reports.index') }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.8rem; font-size:0.78rem; background:#F1F5F9; color:var(--primary-dark); box-shadow:none;">Reset Filter</a>
        </div>
    </div>

    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-bottom:1rem;">
        <span style="font-size:0.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Jalur:</span>
        <span style="padding:0.35rem 0.6rem; border-radius:999px; background:#eef4ff; color:#003366; font-size:0.8rem; font-weight:700;">{{ $selectedSemester?->display_name ?? 'Semua Semester' }}</span>
        @if ($selectedMataKuliahId !== '')
            <span style="padding:0.35rem 0.6rem; border-radius:999px; background:#eefaf2; color:#1d6f42; font-size:0.8rem; font-weight:700;">{{ $mataKuliahList->firstWhere('id', (int) $selectedMataKuliahId)?->nama_mk ?? 'Mata Kuliah' }}</span>
        @endif
        @if ($selectedKelasId !== '')
            <span style="padding:0.35rem 0.6rem; border-radius:999px; background:#fff4e6; color:#9a5b00; font-size:0.8rem; font-weight:700;">{{ $kelasList->firstWhere('id', (int) $selectedKelasId)?->nama_kelas ?? 'Kelas' }}</span>
        @endif
        @if ($selectedStatusFilter !== '')
            <span style="padding:0.35rem 0.6rem; border-radius:999px; background:#f0f5ff; color:#1e3a8a; font-size:0.8rem; font-weight:700;">{{ $selectedStatusLabel }}</span>
        @endif
        @if ($warningThreshold !== null)
            <span style="padding:0.35rem 0.6rem; border-radius:999px; background:#fff4e6; color:#9a5b00; font-size:0.8rem; font-weight:700;">Warning < {{ number_format((float) $warningThreshold, 0) }}%</span>
        @endif
    </div>

    @if ($outlierStudent)
        <div style="display:flex; justify-content:space-between; align-items:center; gap:0.85rem; margin:0 0 0.75rem; border:1px solid #fde5e5; border-radius:12px; background:#fff7f7; padding:0.7rem 0.9rem;">
            <div>
                <div style="font-size:0.72rem; font-weight:800; color:#b91c1c; text-transform:uppercase; letter-spacing:0.08em;">Outlier Kehadiran</div>
                <div style="font-size:0.9rem; color:#111827; margin-top:0.2rem;"><strong>{{ $outlierStudent->nama }}</strong> memiliki persentase kehadiran terendah pada konteks filter aktif.</div>
                @if ($warningThreshold !== null)
                    <div style="font-size:0.77rem; color:#7f1d1d; margin-top:0.18rem;">Jumlah mahasiswa di bawah threshold: <strong>{{ (int) $warningStudentsCount }}</strong></div>
                @endif
            </div>
            <div style="font-size:1.1rem; font-weight:800; color:#b91c1c; white-space:nowrap;">{{ number_format((float) $outlierStudent->persentase, 2) }}%</div>
        </div>

        @if (! $lowestStudents->isEmpty())
            <div style="margin:0 0 1rem; border:1px solid #fee2e2; border-radius:10px; background:#fffafa; padding:0.6rem 0.85rem;">
                <div style="font-size:0.72rem; font-weight:800; color:#991b1b; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:0.35rem;">Top 3 Kehadiran Terendah</div>
                <div style="display:flex; gap:0.55rem; flex-wrap:wrap;">
                    @foreach ($lowestStudents as $low)
                        <span style="display:inline-block; font-size:0.78rem; color:#7f1d1d; background:#ffe8e8; border-radius:999px; padding:0.28rem 0.55rem; font-weight:700;">{{ $low->nama }} - {{ number_format((float) $low->persentase, 2) }}%</span>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @if ($selectedSemesterId !== '')
        @php
            $statusBaseQuery = request()->except(['page', 'status_filter']);
            $thresholdBaseQuery = request()->except(['page', 'warning_threshold']);
        @endphp
        <div style="display:flex; gap:0.45rem; flex-wrap:wrap; margin:-0.1rem 0 0.8rem; align-items:center;">
            <span style="font-size:0.74rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Status Cepat:</span>
            @foreach ($statusFilterOptions as $statusOption)
                <a href="{{ route('reports.index', array_filter(array_merge($statusBaseQuery, ['status_filter' => $statusOption['value']]))) }}" class="status-chip {{ (string) $selectedStatusFilter === (string) $statusOption['value'] ? 'is-active' : '' }}">{{ $statusOption['label'] }}</a>
            @endforeach
        </div>

        <div style="display:flex; gap:0.45rem; flex-wrap:wrap; margin:-0.35rem 0 0.9rem; align-items:center;">
            <span style="font-size:0.74rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Threshold Cepat:</span>
            @foreach ($warningThresholdOptions as $thresholdValue => $thresholdLabel)
                <a href="{{ route('reports.index', array_filter(array_merge($thresholdBaseQuery, ['warning_threshold' => $thresholdValue]))) }}" class="status-chip {{ (string) $selectedWarningThreshold === (string) $thresholdValue ? 'is-active' : '' }}">{{ $thresholdLabel }}</a>
            @endforeach
        </div>

        <div style="display:flex; gap:0.45rem; flex-wrap:wrap; margin:-0.25rem 0 1rem; align-items:center;">
            <span style="font-size:0.74rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Export Cepat:</span>
            <a href="{{ route('reports.export.excel', array_filter(array_merge(['semester_id' => $selectedSemesterId], $statusWarningQuery))) }}" style="text-decoration:none; font-size:0.75rem; font-weight:700; padding:0.32rem 0.58rem; border-radius:999px; background:#eef4ff; color:#003366;">Semester (Excel)</a>
            <a href="{{ route('reports.export.pdf', array_filter(array_merge(['semester_id' => $selectedSemesterId], $statusWarningQuery))) }}" style="text-decoration:none; font-size:0.75rem; font-weight:700; padding:0.32rem 0.58rem; border-radius:999px; background:#e8f2ff; color:#0052a3;">Semester (PDF)</a>
            @if ($selectedMataKuliahId !== '')
                <a href="{{ route('reports.export.excel', array_filter(array_merge(['semester_id' => $selectedSemesterId, 'mata_kuliah_id' => $selectedMataKuliahId], $statusWarningQuery))) }}" style="text-decoration:none; font-size:0.75rem; font-weight:700; padding:0.32rem 0.58rem; border-radius:999px; background:#eefaf2; color:#1d6f42;">Mata Kuliah (Excel)</a>
                <a href="{{ route('reports.export.pdf', array_filter(array_merge(['semester_id' => $selectedSemesterId, 'mata_kuliah_id' => $selectedMataKuliahId], $statusWarningQuery))) }}" style="text-decoration:none; font-size:0.75rem; font-weight:700; padding:0.32rem 0.58rem; border-radius:999px; background:#e5f7ef; color:#166534;">Mata Kuliah (PDF)</a>
            @endif
            @if ($selectedMataKuliahId !== '' && $selectedKelasId !== '')
                <a href="{{ route('reports.export.excel', array_filter(array_merge(['semester_id' => $selectedSemesterId, 'mata_kuliah_id' => $selectedMataKuliahId, 'kelas_id' => $selectedKelasId], $statusWarningQuery))) }}" style="text-decoration:none; font-size:0.75rem; font-weight:700; padding:0.32rem 0.58rem; border-radius:999px; background:#fff7e8; color:#9a5b00;">Kelas (Excel)</a>
                <a href="{{ route('reports.export.pdf', array_filter(array_merge(['semester_id' => $selectedSemesterId, 'mata_kuliah_id' => $selectedMataKuliahId, 'kelas_id' => $selectedKelasId], $statusWarningQuery))) }}" style="text-decoration:none; font-size:0.75rem; font-weight:700; padding:0.32rem 0.58rem; border-radius:999px; background:#fff3da; color:#7c4700;">Kelas (PDF)</a>
            @endif
        </div>
    @endif

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:0.75rem; margin-bottom:1.25rem;">
        @foreach ($semesterCards as $semester)
            <a href="{{ route('reports.index', array_filter(array_merge(['semester_id' => $semester['id']], $statusWarningQuery))) }}" class="report-card report-card-semester {{ $semester['is_selected'] ? 'is-selected' : '' }}">
                <div style="display:flex; justify-content:space-between; gap:0.75rem; align-items:flex-start; margin-bottom:0.65rem;">
                    <div>
                        <div style="font-size:0.72rem; font-weight:700; color:#0066CC; text-transform:uppercase; letter-spacing:0.08em;">Semester</div>
                        <div style="font-size:1rem; font-weight:800; color:var(--primary-dark); margin-top:0.2rem;">{{ $semester['label'] }}</div>
                    </div>
                    @if ($semester['is_active'])
                        <span style="font-size:0.7rem; font-weight:700; background:#e6f6ec; color:#1d6f42; padding:0.25rem 0.55rem; border-radius:999px;">Aktif</span>
                    @endif
                </div>
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:0.5rem; font-size:0.78rem; color:#6b7280;">
                    <div><strong style="display:block; color:#111827; font-size:1rem;">{{ number_format($semester['total_absensi']) }}</strong> Absensi</div>
                    <div><strong style="display:block; color:#111827; font-size:1rem;">{{ number_format($semester['total_mata_kuliah']) }}</strong> MK</div>
                    <div><strong style="display:block; color:#111827; font-size:1rem;">{{ number_format($semester['total_kelas']) }}</strong> Kelas</div>
                </div>
            </a>
        @endforeach
    </div>

    @if (empty($semesterCards))
        <div style="margin-bottom:1.25rem; border:1px dashed #d1d5db; border-radius:12px; background:#f8fafc; padding:0.85rem 1rem; color:#4b5563; font-size:0.86rem;">
            Belum ada data semester akademik. Tambahkan semester di Master Data agar alur laporan dapat digunakan.
        </div>
    @endif

    @if ($selectedSemesterId !== '' && ! empty($courseCards))
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin: 0.5rem 0 0.75rem;">
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Langkah 2</div>
                <div style="font-weight:800; color:var(--primary-dark);">Pilih Mata Kuliah</div>
            </div>
            @if ($selectedMataKuliahId !== '')
                <a href="{{ route('reports.index', array_filter(array_merge(['semester_id' => $selectedSemesterId], $statusWarningQuery))) }}" style="font-size:0.8rem; color:#0066CC; font-weight:700; text-decoration:none;">Hapus pilihan mata kuliah</a>
            @endif
        </div>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:0.75rem; margin-bottom:1.25rem;">
            @foreach ($courseCards as $course)
                <a href="{{ route('reports.index', array_filter(array_merge(['semester_id' => $selectedSemesterId, 'mata_kuliah_id' => $course['id']], $statusWarningQuery))) }}" class="report-card report-card-course {{ $course['is_selected'] ? 'is-selected' : '' }}">
                    <div style="display:flex; justify-content:space-between; gap:0.75rem; align-items:flex-start; margin-bottom:0.65rem;">
                        <div>
                            <div style="font-size:0.72rem; font-weight:700; color:#1DB173; text-transform:uppercase; letter-spacing:0.08em;">Mata Kuliah</div>
                            <div style="font-size:1rem; font-weight:800; color:var(--primary-dark); margin-top:0.2rem;">{{ $course['nama_mk'] }}</div>
                            <div style="font-size:0.8rem; color:#6b7280; margin-top:0.15rem;">{{ $course['kode_mk'] }}</div>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:0.5rem; font-size:0.78rem; color:#6b7280;">
                        <div><strong style="display:block; color:#111827; font-size:1rem;">{{ number_format($course['total_absensi']) }}</strong> Absensi</div>
                        <div><strong style="display:block; color:#111827; font-size:1rem;">{{ number_format($course['total_kelas']) }}</strong> Kelas</div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

    @if ($selectedSemesterId !== '' && empty($courseCards))
        <div style="margin-bottom:1.25rem; border:1px dashed #d1d5db; border-radius:12px; background:#f8fafc; padding:0.85rem 1rem; color:#4b5563; font-size:0.86rem;">
            Belum ada data absensi di semester ini. Pilih semester lain atau lakukan sinkronisasi absensi terlebih dahulu.
        </div>
    @endif

    @if ($selectedSemesterId !== '' && $selectedMataKuliahId !== '' && ! empty($classCards))
        <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin: 0.5rem 0 0.75rem;">
            <div>
                <div style="font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Langkah 3</div>
                <div style="font-weight:800; color:var(--primary-dark);">Pilih Kelas</div>
            </div>
            @if ($selectedKelasId !== '')
                <a href="{{ route('reports.index', array_filter(array_merge(['semester_id' => $selectedSemesterId, 'mata_kuliah_id' => $selectedMataKuliahId], $statusWarningQuery))) }}" style="font-size:0.8rem; color:#0066CC; font-weight:700; text-decoration:none;">Hapus pilihan kelas</a>
            @endif
        </div>
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:0.75rem; margin-bottom:1.25rem;">
            @foreach ($classCards as $classRow)
                <a href="{{ route('reports.index', array_filter(array_merge(['semester_id' => $selectedSemesterId, 'mata_kuliah_id' => $selectedMataKuliahId, 'kelas_id' => $classRow['id']], $statusWarningQuery))) }}" class="report-card report-card-class {{ $classRow['is_selected'] ? 'is-selected' : '' }}">
                    <div style="display:flex; justify-content:space-between; gap:0.75rem; align-items:flex-start; margin-bottom:0.65rem;">
                        <div>
                            <div style="font-size:0.72rem; font-weight:700; color:#F59E0B; text-transform:uppercase; letter-spacing:0.08em;">Kelas</div>
                            <div style="font-size:1rem; font-weight:800; color:var(--primary-dark); margin-top:0.2rem;">{{ $classRow['nama_kelas'] }}</div>
                        </div>
                    </div>
                    <div style="font-size:0.78rem; color:#6b7280;"><strong style="display:block; color:#111827; font-size:1rem;">{{ number_format($classRow['total_absensi']) }}</strong> Absensi</div>
                </a>
            @endforeach
        </div>
    @endif

    @if ($selectedSemesterId !== '' && $selectedMataKuliahId !== '' && empty($classCards))
        <div style="margin-bottom:1.25rem; border:1px dashed #d1d5db; border-radius:12px; background:#f8fafc; padding:0.85rem 1rem; color:#4b5563; font-size:0.86rem;">
            Mata kuliah ini belum memiliki data absensi per kelas pada semester terpilih.
        </div>
    @endif

    <!-- Filter Form -->
    <form action="{{ route('reports.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 1rem; margin-bottom: 2rem; background: #f9fafb; padding: 1.25rem; border-radius: 12px; border: 1px solid #e5e7eb;">
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Semester</label>
            <select name="semester_id" class="form-input">
                <option value="">Semua Semester</option>
                @foreach ($semesterList as $semester)
                    <option value="{{ $semester->id }}" {{ (string) $selectedSemesterId === (string) $semester->id ? 'selected' : '' }}>{{ $semester->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Kelas</label>
            <select name="kelas_id" class="form-input">
                <option value="">Semua Kelas</option>
                @foreach ($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" {{ (string) $selectedKelasId === (string) $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Mata Kuliah</label>
            <select name="mata_kuliah_id" class="form-input">
                <option value="">Semua Mata Kuliah</option>
                @foreach ($mataKuliahList as $mk)
                    <option value="{{ $mk->id }}" {{ (string) $selectedMataKuliahId === (string) $mk->id ? 'selected' : '' }}>{{ $mk->nama_mk }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Status</label>
            <select name="status_filter" class="form-input">
                @foreach ($statusFilterOptions as $statusOption)
                    <option value="{{ $statusOption['value'] }}" {{ (string) $selectedStatusFilter === (string) $statusOption['value'] ? 'selected' : '' }}>{{ $statusOption['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Threshold</label>
            <select name="warning_threshold" class="form-input">
                @foreach ($warningThresholdOptions as $thresholdValue => $thresholdLabel)
                    <option value="{{ $thresholdValue }}" {{ (string) $selectedWarningThreshold === (string) $thresholdValue ? 'selected' : '' }}>{{ $thresholdLabel }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; align-items:flex-end;">
            <button class="btn-kinetic" type="submit" style="width:100%;"><i class="fas fa-filter"></i> Terapkan Filter</button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Mahasiswa</th>
                <th>Total</th>
                <th>{{ $reportStatusLabels['hadir'] ?? 'Hadir' }}</th>
                <th>{{ $reportStatusLabels['sakit_izin'] ?? 'Sakit/Izin' }}</th>
                <th>{{ $reportStatusLabels['alpa'] ?? 'Alpa' }}</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stats as $row)
                <tr class="{{ ($outlierStudent && (int) $outlierStudent->id === (int) $row->id) ? 'outlier-row' : (($warningThreshold !== null && (float) $row->persentase < (float) $warningThreshold) ? 'outlier-row' : '') }}">
                    <td style="font-weight: 700;"><a href="{{ route('student-detail', array_merge(['id' => $row->id], request()->only(['semester_id', 'mata_kuliah_id', 'kelas_id', 'status_filter', 'warning_threshold', 'month']), ['from' => 'reports'])) }}" style="color:#003366; text-decoration:none;">{{ $row->nama }}</a>@if ($outlierStudent && (int) $outlierStudent->id === (int) $row->id)<span class="outlier-badge">OUTLIER</span>@elseif ($warningThreshold !== null && (float) $row->persentase < (float) $warningThreshold)<span class="outlier-badge">WARN</span>@endif</td>
                    <td>{{ (int) $row->total }}</td>
                    <td>{{ (int) $row->hadir }}</td>
                    <td>{{ (int) $row->sakit_izin }}</td>
                    <td>{{ (int) $row->alpa }}</td>
                    <td>
                        @if ($row->persentase >= 90)
                            <strong style="color:#1DB173;">{{ number_format((float) $row->persentase, 2) }}%</strong>
                        @elseif ($row->persentase >= 80)
                            <strong style="color:var(--kinetic-yellow);">{{ number_format((float) $row->persentase, 2) }}%</strong>
                        @else
                            <strong style="color:#BA1A1A;">{{ number_format((float) $row->persentase, 2) }}%</strong>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:#6b7280;">Tidak ada data laporan untuk filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $stats->links() }}
    </div>
</div>
@endsection
