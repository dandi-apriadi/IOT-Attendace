@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h2 class="display-font">Monitoring Real-time</h2>
        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
            <span class="status-pill status-present" style="background: var(--primary-blue); color: #fff;">IK-2A</span>
            <span style="font-size: 0.85rem; font-weight: 700;">Pemrograman Web Lanjut</span>
            <span style="font-size: 0.85rem; opacity: 0.6;">(08:00 - 10:30)</span>
        </div>
    </div>
    <div style="text-align: right;">
        <div style="font-size: 1.5rem; font-weight: 800; color: #1DB173;">24 / 32</div>
        <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700;">MAHASISWA HADIR</div>
    </div>
</div>

<div class="glass-card" style="border-top: 4px solid var(--kinetic-yellow);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 12px; height: 12px; background: #1DB173; border-radius: 50%; animation: pulse 2s infinite;"></div>
            <h3 class="display-font">Log Presensi Terkini</h3>
        </div>
        <button class="btn-kinetic" style="background: #BA1A1A; color: #fff;"><i class="fas fa-stop"></i> Akhiri Sesi</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Mahasiswa</th>
                <th>Metode</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="presence-log">
            <tr>
                <td style="font-weight: 700; color: var(--primary-blue);">08:45:12</td>
                <td style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="https://i.pravatar.cc/32?u=1" style="border-radius: 8px;">
                    <div>
                        <div style="font-weight: 700;">Dandi Apriadi</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">22041010</div>
                    </div>
                </td>
                <td><i class="fas fa-id-card"></i> RFID Tap</td>
                <td><span class="status-pill status-present">Hadir</span></td>
                <td><button style="border: none; background: none; color: var(--text-muted); cursor: pointer;"><i class="fas fa-ellipsis-v"></i></button></td>
            </tr>
            <tr style="background: #FFF4E6;">
                <td style="font-weight: 700;">08:35:00</td>
                <td style="display: flex; align-items: center; gap: 0.75rem;">
                    <img src="https://i.pravatar.cc/32?u=2" style="border-radius: 8px;">
                    <div>
                        <div style="font-weight: 700;">Aisyah Putri</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">22041011</div>
                    </div>
                </td>
                <td><i class="fas fa-camera"></i> Face Rec.</td>
                <td><span class="status-pill status-late">Telat</span></td>
                <td><button style="border: none; background: none; color: var(--text-muted); cursor: pointer;"><i class="fas fa-ellipsis-v"></i></button></td>
            </tr>
        </tbody>
    </table>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(29, 177, 115, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(29, 177, 115, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(29, 177, 115, 0); }
}
</style>
@endsection
