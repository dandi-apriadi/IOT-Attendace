@extends('layouts.app')

@section('content')
<div class="stats-grid">
    <div class="glass-card" style="border-left: 4px solid var(--kinetic-yellow);">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Kehadiran Hari Ini</div>
        <div style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0;">1,240 <span style="font-size: 1rem; font-weight: 400; opacity: 0.6;">Siswa</span></div>
        <div style="color: #1DB173; font-size: 0.8rem; font-weight: 700;"><i class="fas fa-arrow-up"></i> 12% vs Kemarin</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">Sesi Kuliah Aktif</div>
        <div style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0;">42 <span style="font-size: 1rem; font-weight: 400; opacity: 0.6;">Kelas</span></div>
        <div style="color: var(--kinetic-yellow); font-size: 0.8rem; font-weight: 700;">80% dari Total Jadwal</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase;">IoT Health Status</div>
        <div style="font-size: 2.5rem; font-weight: 800; margin: 0.5rem 0;">95%</div>
        <div style="color: #1DB173; font-size: 0.8rem; font-weight: 700;"><i class="fas fa-check-circle"></i> 18 Device Online</div>
    </div>
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
                <tr>
                    <td style="display: flex; align-items: center; gap: 0.75rem;">
                        <img src="https://i.pravatar.cc/32?u=1" alt="" style="border-radius: 8px;">
                        <div>
                            <div style="font-weight: 700;">Dandi Apriadi</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">22041010</div>
                        </div>
                    </td>
                    <td>08:15:22</td>
                    <td>Pemrograman Web</td>
                    <td><span class="status-pill status-present">Hadir</span></td>
                </tr>
                <tr>
                    <td style="display: flex; align-items: center; gap: 0.75rem;">
                        <img src="https://i.pravatar.cc/32?u=2" alt="" style="border-radius: 8px;">
                        <div>
                            <div style="font-weight: 700;">Aisyah Putri</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">22041011</div>
                        </div>
                    </td>
                    <td>08:20:05</td>
                    <td>Sistem Tertanam</td>
                    <td><span class="status-pill status-late">Telat</span></td>
                </tr>
                <tr>
                    <td style="display: flex; align-items: center; gap: 0.75rem;">
                        <img src="https://i.pravatar.cc/32?u=3" alt="" style="border-radius: 8px;">
                        <div>
                            <div style="font-weight: 700;">Budi Santoso</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">22041012</div>
                        </div>
                    </td>
                    <td>08:05:12</td>
                    <td>Basis Data</td>
                    <td><span class="status-pill status-present">Hadir</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- IoT Alerts -->
    <div class="glass-card" style="background: rgba(0, 30, 64, 0.02);">
        <h3 class="display-font" style="margin-bottom: 1.5rem;">Log Perangkat IoT</h3>
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div style="padding: 1rem; background: #fff; border-radius: var(--radius-md); border-left: 3px solid #1DB173;">
                <div style="font-size: 0.7rem; color: var(--text-muted);">08:00:10</div>
                <div style="font-weight: 700; font-size: 0.85rem;">Device R-402 Online</div>
                <div style="font-size: 0.75rem;">Sistem pendeteksi wajah diaktifkan.</div>
            </div>
            <div style="padding: 1rem; background: #fff; border-radius: var(--radius-md); border-left: 3px solid var(--kinetic-yellow);">
                <div style="font-size: 0.7rem; color: var(--text-muted);">07:45:22</div>
                <div style="font-weight: 700; font-size: 0.85rem;">Signal Jitter R-102</div>
                <div style="font-size: 0.75rem;">ESP32 mengalami guncangan sinyal WiFi.</div>
            </div>
            <div style="padding: 1rem; background: #fff; border-radius: var(--radius-md); border-left: 3px solid #BA1A1A;">
                <div style="font-size: 0.7rem; color: var(--text-muted);">07:30:00</div>
                <div style="font-weight: 700; font-size: 0.85rem;">Reader L-001 Offline</div>
                <div style="font-size: 0.75rem;">Perangkat RFID tidak merespon server.</div>
            </div>
        </div>
    </div>
</div>
@endsection
