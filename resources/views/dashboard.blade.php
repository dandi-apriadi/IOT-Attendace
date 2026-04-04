@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('breadcrumb')
    <span>Dashboard</span>
@endsection

@section('content')
<div class="stats-grid">
    <div class="glass-card" style="border-left: 4px solid var(--kinetic-yellow);">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Kehadiran Hari Ini</div>
        <div style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0;">{{ number_format($hadirHariIni) }} <span style="font-size: 1rem; font-weight: 400; opacity: 0.6;">Tap</span></div>
        <div style="color: #1DB173; font-size: 0.8rem; font-weight: 700;"><i class="fas fa-database"></i> Data realtime dari tabel absensi</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Sesi Kuliah Aktif</div>
        <div style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0;">{{ number_format($sesiAktif) }} <span style="font-size: 1rem; font-weight: 400; opacity: 0.6;">Jadwal</span></div>
        <div style="color: var(--kinetic-yellow); font-size: 0.8rem; font-weight: 700;">Mengikuti hari & jam saat ini</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">IoT Health Status</div>
        <div style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0;">{{ number_format($totalDeviceAktif) }}</div>
        <div style="color: #1DB173; font-size: 0.8rem; font-weight: 700;"><i class="fas fa-check-circle"></i> Device aktif terdaftar</div>
    </div>

    @if (auth()->user()?->role === 'dosen')
        <div class="glass-card" style="border-left: 4px solid #0066CC;">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Semester Aktif</div>
            <div style="font-size: 1.4rem; font-weight: 800; margin: 0.7rem 0 0.4rem;">{{ $activeSemester?->display_name ?? 'Belum ada semester' }}</div>
            <div style="color: #0066CC; font-size: 0.8rem; font-weight: 700;">{{ number_format($dosenScheduleCount) }} jadwal pengampu</div>
        </div>
    @endif
</div>

@if (auth()->user()?->role === 'dosen')
    <div class="glass-card" style="margin-bottom: 2rem; background: linear-gradient(135deg, rgba(0,102,204,0.04), rgba(29,177,115,0.04)); border: 1px solid rgba(0,102,204,0.08);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
            <div>
                <h3 class="display-font" style="margin:0; font-size:1.1rem;">Akses Cepat Dosen</h3>
                <div style="font-size:0.85rem; color:#6b7280; margin-top:0.25rem;">Masuk ke daftar mata kuliah yang Anda kelola berdasarkan semester aktif.</div>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ route('dosen-courses') }}" class="btn-kinetic" style="text-decoration:none; padding:0.65rem 0.9rem; font-size:0.82rem; background:#0066CC; box-shadow:none;">Mata Kuliah Saya</a>
                <a href="{{ route('dosen-courses') }}" class="btn-kinetic" style="text-decoration:none; padding:0.65rem 0.9rem; font-size:0.82rem; background:#F1F5F9; color:var(--primary-dark); box-shadow:none;">Buka Sesi Jadwal</a>
                <a href="{{ route('reports.index', ['semester_id' => $activeSemester?->id]) }}" class="btn-kinetic" style="text-decoration:none; padding:0.65rem 0.9rem; font-size:0.82rem; background:#1DB173; box-shadow:none;">Laporan Semester</a>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.9rem;">
            @forelse ($dosenAssignedSchedules as $group)
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:1rem; box-shadow:0 10px 30px rgba(0,0,0,0.03);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.75rem; margin-bottom:0.75rem;">
                        <div>
                            <div style="font-size:0.72rem; font-weight:700; color:#0066CC; text-transform:uppercase; letter-spacing:0.08em;">{{ $group['semester'] }}</div>
                            <div style="font-size:1rem; font-weight:800; color:var(--primary-dark); margin-top:0.25rem;">{{ number_format($group['total']) }} jadwal</div>
                        </div>
                        <div style="font-size:0.72rem; color:#6b7280; text-transform:uppercase; font-weight:700;">Ringkas</div>
                    </div>

                    <div style="display:flex; flex-direction:column; gap:0.65rem;">
                        @foreach ($group['items'] as $jadwal)
                            <div style="padding:0.7rem 0.8rem; background:#f8fafc; border-radius:12px;">
                                <div style="font-weight:800; font-size:0.9rem;">{{ $jadwal->mata_kuliah?->nama_mk ?? '-' }}</div>
                                <div style="font-size:0.8rem; color:#6b7280; margin-top:0.2rem;">{{ $jadwal->kelas?->nama_kelas ?? '-' }} · {{ $jadwal->hari }} · {{ substr($jadwal->jam_mulai, 0, 5) }}-{{ substr($jadwal->jam_selesai, 0, 5) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.9rem;">
                        <a href="{{ route('dosen-courses') }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.8rem; font-size:0.8rem; box-shadow:none;">Lihat Semua</a>
                        <a href="{{ route('dosen-courses') }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.8rem; font-size:0.8rem; background:#F1F5F9; color:var(--primary-dark); box-shadow:none;">Mulai Sesi</a>
                    </div>
                </div>
            @empty
                <div style="padding:1rem; color:#6b7280;">Belum ada jadwal pengampu yang ditetapkan.</div>
            @endforelse
        </div>
    </div>
@endif

<!-- Charts Section -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
    @if(auth()->user()?->role === 'admin')
        <!-- Admin: Weekly Trends -->
        <div class="glass-card">
            <h3 class="display-font" style="margin-bottom: 1.5rem; font-size: 1.1rem;">Tren Kehadiran Mingguan</h3>
            <div style="height: 300px;">
                <canvas id="campusTrendsChart"></canvas>
            </div>
        </div>
        <!-- Admin: IoT Health -->
        <div class="glass-card">
            <h3 class="display-font" style="margin-bottom: 1.5rem; font-size: 1.1rem;">Status Perangkat IoT</h3>
            <div style="height: 300px; display: flex; justify-content: center;">
                <canvas id="iotHealthChart"></canvas>
            </div>
        </div>
    @else
        <!-- Dosen: Class Participation -->
        <div class="glass-card">
            <h3 class="display-font" style="margin-bottom: 1.5rem; font-size: 1.1rem;">Partisipasi per Kelas</h3>
            <div style="height: 300px;">
                <canvas id="classParticipationChart"></canvas>
            </div>
        </div>
        <!-- Dosen: Attendance Performance -->
        <div class="glass-card">
            <h3 class="display-font" style="margin-bottom: 1.5rem; font-size: 1.1rem;">Performa Kehadiran MK</h3>
            <div style="height: 300px; display: flex; justify-content: center;">
                <canvas id="coursePerformanceChart"></canvas>
            </div>
        </div>
    @endif
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
    <!-- Recent Activity Table -->
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h3 class="display-font">Data Kehadiran Terbaru</h3>
            <a href="{{ route('monitoring') }}" style="color: var(--primary-blue-container); font-size: 0.8rem; font-weight: 700; text-decoration: none;">Lihat Semua <i class="fas fa-arrow-right"></i></a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mahasiswa</th>
                    <th>Jam Tap</th>
                    <th>Mata Kuliah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($latestAbsensi as $item)
                    <tr>
                        <td>
                            <div style="font-weight: 700;">{{ $item->mahasiswa->nama ?? '-' }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $item->mahasiswa->nim ?? '-' }}</div>
                        </td>
                        <td>{{ $item->waktu_tap }}</td>
                        <td>{{ $item->jadwal->mata_kuliah->nama_mk ?? '-' }}</td>
                        <td>
                            <span class="status-pill {{ ($item->status ?? '') === 'Telat' ? 'status-late' : (($item->status ?? '') === 'Alpa' ? 'status-absent' : 'status-present') }}">
                                {{ $item->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color:#6b7280;">Belum ada data absensi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- IoT Alerts -->
    <div class="glass-card" style="background: rgba(0, 30, 64, 0.02);">
        <h3 class="display-font" style="margin-bottom: 1.5rem;">Log Perangkat IoT</h3>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @forelse ($recentDevices as $device)
                @if ($device->is_active)
                    <div style="padding: 1rem; background: #fff; border-radius: var(--radius-md); border-left: 3px solid #1DB173;">
                        <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $device->last_seen_at?->format('d M Y H:i:s') ?? '-' }}</div>
                        <div style="font-weight: 700; font-size: 0.85rem;">{{ $device->device_id }} Online</div>
                        <div style="font-size: 0.75rem;">{{ $device->name ?? 'IoT Device' }}</div>
                    </div>
                @else
                    <div style="padding: 1rem; background: #fff; border-radius: var(--radius-md); border-left: 3px solid #BA1A1A;">
                        <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $device->last_seen_at?->format('d M Y H:i:s') ?? '-' }}</div>
                        <div style="font-weight: 700; font-size: 0.85rem;">{{ $device->device_id }} Offline</div>
                        <div style="font-size: 0.75rem;">{{ $device->name ?? 'IoT Device' }}</div>
                    </div>
                @endif
            @empty
                <div style="padding: 1rem; background: #fff; border-radius: var(--radius-md);">Belum ada data perangkat.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const adminWeeklyChart = @json($adminWeeklyChart ?? ['labels' => [], 'data' => []]);
    const adminIotChart = @json($adminIotChart ?? ['labels' => [], 'data' => []]);
    const dosenClassChart = @json($dosenClassChart ?? ['labels' => [], 'data' => []]);
    const dosenCourseChart = @json($dosenCourseChart ?? ['labels' => [], 'data' => []]);

    const isChartLibReady = typeof Chart !== 'undefined';

    function normalizeChartPayload(payload) {
        if (!payload || typeof payload !== 'object') {
            return { labels: [], data: [] };
        }

        const labels = Array.isArray(payload.labels) ? payload.labels : [];
        const data = Array.isArray(payload.data) ? payload.data : [];

        return {
            labels,
            data,
        };
    }

    function renderChartUnavailable(canvasId, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !canvas.parentElement) {
            return;
        }

        canvas.parentElement.innerHTML = '<div style="height:100%;display:flex;align-items:center;justify-content:center;color:#6b7280;font-size:0.9rem;font-weight:600;">' + message + '</div>';
    }

    if (!isChartLibReady) {
        renderChartUnavailable('campusTrendsChart', 'Chart.js gagal dimuat.');
        renderChartUnavailable('iotHealthChart', 'Chart.js gagal dimuat.');
        renderChartUnavailable('classParticipationChart', 'Chart.js gagal dimuat.');
        renderChartUnavailable('coursePerformanceChart', 'Chart.js gagal dimuat.');
        return;
    }

    const safeAdminWeekly = normalizeChartPayload(adminWeeklyChart);
    const safeAdminIot = normalizeChartPayload(adminIotChart);
    const safeDosenClass = normalizeChartPayload(dosenClassChart);
    const safeDosenCourse = normalizeChartPayload(dosenCourseChart);

    const ctxTrends = document.getElementById('campusTrendsChart');
    if (ctxTrends) {
        new Chart(ctxTrends, {
            type: 'line',
            data: {
                labels: safeAdminWeekly.labels,
                datasets: [{
                    label: 'Total Kehadiran',
                    data: safeAdminWeekly.data,
                    borderColor: '#1DB173',
                    backgroundColor: 'rgba(29, 177, 115, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#1DB173'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const ctxIot = document.getElementById('iotHealthChart');
    if (ctxIot) {
        new Chart(ctxIot, {
            type: 'doughnut',
            data: {
                labels: safeAdminIot.labels,
                datasets: [{
                    data: safeAdminIot.data,
                    backgroundColor: ['#1DB173', '#BA1A1A', '#F59E0B'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });
    }

    const ctxParticipation = document.getElementById('classParticipationChart');
    if (ctxParticipation) {
        new Chart(ctxParticipation, {
            type: 'bar',
            data: {
                labels: safeDosenClass.labels,
                datasets: [{
                    label: '% Kehadiran',
                    data: safeDosenClass.data,
                    backgroundColor: 'rgba(0, 102, 204, 0.8)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, max: 100 },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    const ctxCourse = document.getElementById('coursePerformanceChart');
    if (ctxCourse) {
        new Chart(ctxCourse, {
            type: 'radar',
            data: {
                labels: safeDosenCourse.labels,
                datasets: [{
                    label: 'Performa Rata-rata',
                    data: safeDosenCourse.data,
                    borderColor: '#0066CC',
                    backgroundColor: 'rgba(0, 102, 204, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: { beginAtZero: true, max: 100 }
                }
            }
        });
    }
});
</script>
@endpush
