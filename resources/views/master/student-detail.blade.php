@extends('layouts.app')

@section('title', 'Detail Mahasiswa: ' . $mahasiswa->nama)
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Mahasiswa</span>
    <span class="breadcrumb-sep">/</span>
    <span>Detail</span>
@endsection

@section('styles')
<style>
    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .status-badge-present { background: #E6F6EC; color: #1DB173; }
    .status-badge-excused { background: #FEF3C7; color: #F59E0B; }
    .status-badge-absent { background: #FADBD8; color: #BA1A1A; }
    .status-badge-unknown { background: #E5E7EB; color: #374151; }

    .preset-chip {
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.32rem 0.58rem;
        border-radius: 999px;
        background: #eef2f7;
        color: #4b5563;
    }

    .preset-chip.is-active {
        background: #e8f2ff;
        color: #003f80;
    }

    .trend-grid {
        display: flex;
        gap: 0.55rem;
        align-items: flex-end;
        height: 150px;
    }

    .trend-col {
        flex: 1 1 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-end;
        height: 100%;
    }

    .trend-bar {
        width: 100%;
        max-width: 42px;
        background: linear-gradient(180deg, #1db173 0%, #0f8c58 100%);
        border-radius: 8px 8px 4px 4px;
    }

    .trend-h-10 { height: 10%; }
    .trend-h-20 { height: 20%; }
    .trend-h-30 { height: 30%; }
    .trend-h-40 { height: 40%; }
    .trend-h-50 { height: 50%; }
    .trend-h-60 { height: 60%; }
    .trend-h-70 { height: 70%; }
    .trend-h-80 { height: 80%; }
    .trend-h-90 { height: 90%; }
    .trend-h-100 { height: 100%; }

    .trend-delta {
        font-size: 0.73rem;
        font-weight: 700;
        padding: 0.26rem 0.55rem;
        border-radius: 999px;
    }

    .trend-delta-up { background: #E6F6EC; color: #166534; }
    .trend-delta-down { background: #FDECEC; color: #9F1239; }
    .trend-delta-flat { background: #EFF6FF; color: #1E3A8A; }
</style>
@endsection

@section('content')
<div class="glass-card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Informasi Profil</h3>
        <a href="{{ $hasReportContext ? $reportBackUrl : route('mahasiswa') }}" class="btn-secondary" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.8rem;"><i class="fas fa-arrow-left"></i> {{ $hasReportContext ? 'Kembali ke Laporan' : 'Kembali' }}</a>
    </div>

    @if ($hasReportContext)
        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin:-1rem 0 1.25rem; align-items:center;">
            <span style="font-size:0.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Konteks Laporan:</span>
            @if ($selectedSemesterLabel)
                <span style="padding:0.34rem 0.58rem; border-radius:999px; background:#eef4ff; color:#003366; font-size:0.78rem; font-weight:700;">{{ $selectedSemesterLabel }}</span>
            @endif
            @if ($selectedMataKuliahLabel)
                <span style="padding:0.34rem 0.58rem; border-radius:999px; background:#eefaf2; color:#1d6f42; font-size:0.78rem; font-weight:700;">{{ $selectedMataKuliahLabel }}</span>
            @endif
            @if ($selectedKelasLabel)
                <span style="padding:0.34rem 0.58rem; border-radius:999px; background:#fff4e6; color:#9a5b00; font-size:0.78rem; font-weight:700;">{{ $selectedKelasLabel }}</span>
            @endif
        </div>
    @endif

    <form action="{{ route('student-detail', ['id' => $mahasiswa->id]) }}" method="GET" style="display:grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap:0.75rem; margin-bottom:1.5rem; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:0.9rem;">
        @foreach ($filtersQuery as $key => $value)
            @if (! in_array($key, ['start_date', 'end_date', 'status_filter'], true))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        @if ($hasReportContext)
            <input type="hidden" name="from" value="reports">
        @endif
        <div>
            <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280; text-transform:uppercase;">Status</label>
            <select name="status_filter" class="form-input">
                @foreach ($statusFilterOptions as $option)
                    <option value="{{ $option['value'] }}" {{ $selectedStatusFilter === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280; text-transform:uppercase;">Tanggal Mulai</label>
            <input type="date" name="start_date" value="{{ $selectedStartDate }}" class="form-input">
        </div>
        <div>
            <label style="display:block; font-size:0.74rem; font-weight:700; margin-bottom:0.35rem; color:#6b7280; text-transform:uppercase;">Tanggal Selesai</label>
            <input type="date" name="end_date" value="{{ $selectedEndDate }}" class="form-input">
        </div>
        <div style="display:flex; align-items:flex-end;">
            <button type="submit" class="btn-kinetic" style="width:100%;"><i class="fas fa-filter"></i> Terapkan</button>
        </div>
        <div style="display:flex; align-items:flex-end;">
            <a href="{{ route('student-detail', array_merge(['id' => $mahasiswa->id], array_filter($filtersQuery, static fn ($value, $key) => ! in_array($key, ['start_date', 'end_date', 'status_filter'], true), ARRAY_FILTER_USE_BOTH), $hasReportContext ? ['from' => 'reports'] : [])) }}" class="btn-kinetic" style="width:100%; text-decoration:none; text-align:center; background:#F1F5F9; color:var(--primary-dark); box-shadow:none;">Reset Filter Detail</a>
        </div>
        <div style="display:flex; gap:0.45rem; align-items:flex-end; justify-content:flex-end;">
            <a href="{{ route('student-detail.export.excel', array_merge(['id' => $mahasiswa->id], $filtersQuery, $hasReportContext ? ['from' => 'reports'] : [])) }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.7rem; font-size:0.76rem; background:#1DB173; box-shadow:none;">Export Excel</a>
            <a href="{{ route('student-detail.export.pdf', array_merge(['id' => $mahasiswa->id], $filtersQuery, $hasReportContext ? ['from' => 'reports'] : [])) }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.7rem; font-size:0.76rem; background:#0066CC; box-shadow:none;">Export PDF</a>
        </div>
        <div style="grid-column:1 / -1; display:flex; gap:0.45rem; flex-wrap:wrap; margin-top:0.15rem; align-items:center;">
            <span style="font-size:0.72rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em;">Preset:</span>
            @foreach ($quickDateRanges as $preset)
                <a href="{{ $preset['url'] }}" class="preset-chip {{ $preset['is_active'] ? 'is-active' : '' }}">{{ $preset['label'] }}</a>
            @endforeach
        </div>
    </form>

    @if (! empty($weeklyTrend))
        <div style="margin-bottom:1.5rem; border:1px solid #e5e7eb; border-radius:12px; background:#ffffff; padding:0.9rem 1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; margin-bottom:0.85rem;">
                <div style="font-size:0.78rem; font-weight:800; color:#003366; text-transform:uppercase; letter-spacing:0.08em;">Tren Kehadiran Mingguan</div>
                <div style="display:flex; gap:0.45rem; align-items:center;">
                    <span style="font-size:0.73rem; color:#6b7280;">{{ count($weeklyTrend) }} minggu terakhir</span>
                    <span class="trend-delta trend-delta-{{ $trendInsight['direction'] ?? 'flat' }}">{{ $trendInsight['text'] ?? '' }}</span>
                </div>
            </div>
            <div class="trend-grid">
                @foreach ($weeklyTrend as $point)
                    @php
                        $heightBucket = ((int) floor(max(10, min(100, (float) $point['persentase'])) / 10)) * 10;
                        $heightClass = 'trend-h-' . $heightBucket;
                    @endphp
                    <div class="trend-col">
                        <div style="font-size:0.69rem; color:#4b5563; margin-bottom:0.3rem; font-weight:700;">{{ number_format((float) $point['persentase'], 1) }}%</div>
                        <div class="trend-bar {{ $heightClass }}" title="{{ $point['label'] }}: {{ $point['hadir'] }}/{{ $point['total'] }}"></div>
                        <div style="margin-top:0.35rem; font-size:0.68rem; color:#6b7280; font-weight:700;">{{ $point['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Student Info Card -->
    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div style="text-align: center;">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($mahasiswa->nama) }}&background=003366&color=fff&size=150" style="width: 150px; height: 150px; border-radius: 15px; margin-bottom: 1rem;">
            <div style="font-weight: 700; font-size: 1.1rem;">{{ $mahasiswa->nama }}</div>
            <div style="font-size: 0.85rem; color: #6b7280; font-family: monospace;">{{ $mahasiswa->nim }}</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div style="background: #E6F6EC; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700; text-transform: uppercase;">Total Absensi</div>
                <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ $totalAbsensi }}</div>
            </div>
            <div style="background: #E6F6EC; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700; text-transform: uppercase;">Persentase Hadir</div>
                <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ $persentaseHadir }}%</div>
            </div>
            <div style="background: #FEF3C7; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #F59E0B; font-weight: 700; text-transform: uppercase;">Bulan Ini</div>
                <div style="font-size: 1.5rem; font-weight: 800;">{{ $thisMonthHadir }}/{{ $thisMonthAbsensi }}</div>
            </div>
            <div style="background: #FADBD8; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #BA1A1A; font-weight: 700; text-transform: uppercase;">Alpa Total</div>
                <div style="font-size: 2rem; font-weight: 800; color: #BA1A1A;">{{ $alpaCount }}</div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: rgba(29, 177, 115, 0.05); padding: 1rem; border-left: 3px solid #1DB173; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Hadir</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1DB173;">{{ $hadirCount }}</div>
        </div>
        <div style="background: rgba(255, 152, 0, 0.05); padding: 1rem; border-left: 3px solid #F59E0B; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Sakit/Izin</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #F59E0B;">{{ $sabitIzinCount }}</div>
        </div>
        <div style="background: rgba(186, 26, 26, 0.05); padding: 1rem; border-left: 3px solid #BA1A1A; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Alpa</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #BA1A1A;">{{ $alpaCount }}</div>
        </div>
        <div style="background: rgba(0, 102, 204, 0.05); padding: 1rem; border-left: 3px solid #0066CC; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Total</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0066CC;">{{ $totalAbsensi }}</div>
        </div>
    </div>

    <!-- Attendance History -->
    <h4 class="display-font" style="margin-bottom: 1rem; margin-top: 2rem;">Riwayat Absensi</h4>

    <table style="width: 100%; font-size: 0.85rem;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Tanggal & Waktu</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Mata Kuliah</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Jadwal</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($absensiHistory as $record)
                @php
                    $statusRaw = (string) $record->status;
                    $statusKey = strtolower($statusRaw);
                    $statusType = $statusTypeMap[$statusKey] ?? 'unknown';
                    $statusLabel = $statusLabels[$statusRaw] ?? str_replace('_', ' ', ucfirst($statusRaw));
                    $statusClass = 'status-badge status-badge-' . $statusType;
                @endphp
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.75rem;">{{ \Illuminate\Support\Carbon::parse($record->tanggal)->format('d M Y') }} {{ $record->waktu_tap ? substr((string) $record->waktu_tap, 0, 8) : '' }}</td>
                    <td style="padding: 0.75rem;">
                        <div style="font-weight: 600;">{{ $record->jadwal?->mataKuliah?->nama_mk ?? 'N/A' }}</div>
                        <div style="font-size: 0.8rem; color: #6b7280;">{{ $record->jadwal?->mataKuliah?->kode_mk ?? 'N/A' }}</div>
                    </td>
                    <td style="padding: 0.75rem; font-size: 0.8rem;">{{ $record->jadwal?->hari ?? 'N/A' }} - {{ $record->jadwal?->jam_mulai ?? 'N/A' }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada riwayat absensi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $absensiHistory->links() }}
    </div>
</div>
@endsection
