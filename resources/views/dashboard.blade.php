@extends('layouts.app')

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
</div>

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
                <div style="padding: 1rem; background: #fff; border-radius: var(--radius-md); border-left: 3px solid {{ $device->is_active ? '#1DB173' : '#BA1A1A' }};">
                    <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $device->last_seen_at?->format('d M Y H:i:s') ?? '-' }}</div>
                    <div style="font-weight: 700; font-size: 0.85rem;">{{ $device->device_id }} {{ $device->is_active ? 'Online' : 'Offline' }}</div>
                    <div style="font-size: 0.75rem;">{{ $device->name ?? 'IoT Device' }}</div>
                </div>
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

    const ctxTrends = document.getElementById('campusTrendsChart');
    if (ctxTrends) {
        new Chart(ctxTrends, {
            type: 'line',
            data: {
                labels: adminWeeklyChart.labels,
                datasets: [{
                    label: 'Total Kehadiran',
                    data: adminWeeklyChart.data,
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
                labels: adminIotChart.labels,
                datasets: [{
                    data: adminIotChart.data,
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
                labels: dosenClassChart.labels,
                datasets: [{
                    label: '% Kehadiran',
                    data: dosenClassChart.data,
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
                labels: dosenCourseChart.labels,
                datasets: [{
                    label: 'Performa Rata-rata',
                    data: dosenCourseChart.data,
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
