@extends('layouts.app')

@section('content')
<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Live Attendance Stream</h3>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 2rem;">
        <div style="background: #E6F6EC; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700; text-transform: uppercase;">Hari Ini Total</div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #1DB173; margin: 0.5rem 0;">{{ $todayTotal }}</div>
            <div style="font-size: 0.75rem; color: #6b7280;">Tap presensi</div>
        </div>
        <div style="background: #FEF3C7; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 0.8rem; color: #F59E0B; font-weight: 700; text-transform: uppercase;">Jam Ini</div>
            <div style="font-size: 2.5rem; font-weight: 800; color: #F59E0B; margin: 0.5rem 0;">{{ $thisHourTotal }}</div>
            <div style="font-size: 0.75rem; color: #6b7280;">Tap dalam 1 jam terakhir</div>
        </div>
        <div style="background: #F0F5FF; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 0.8rem; color: #0066CC; font-weight: 700; text-transform: uppercase;">Live Status</div>
            <div style="font-size: 2rem; font-weight: 800; color: #0066CC; margin: 0.5rem 0;">
                <span style="display: inline-block; width: 12px; height: 12px; background: #1DB173; border-radius: 50%; margin-right: 0.5rem; animation: pulse 1.5s infinite;"></span>
                ACTIVE
            </div>
            <div style="font-size: 0.75rem; color: #6b7280;">Real-time updates</div>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>

    <h4 class="display-font" style="margin-bottom: 1rem; font-size: 1.2rem;">Latest Attendance Records</h4>
    
    <table style="width: 100%; font-size: 0.85rem;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Waktu</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Mahasiswa</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">NIM</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Jadwal</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($liveStream as $record)
                <tr style="border-bottom: 1px solid #f3f4f6; animation: slideIn 0.3s ease-out;">
                    <td style="padding: 0.75rem; font-weight: 600; color: #6b7280;">{{ $record->created_at->format('H:i:s') }}</td>
                    <td style="padding: 0.75rem;">{{ $record->mahasiswa?->nama_mahasiswa ?? 'N/A' }}</td>
                    <td style="padding: 0.75rem; font-family: monospace; color: #0066CC;">{{ $record->mahasiswa?->nim ?? 'N/A' }}</td>
                    <td style="padding: 0.75rem; font-size: 0.8rem;">{{ $record->jadwal?->mataKuliah?->kode_mk ?? 'N/A' }} - {{ $record->jadwal?->hari ?? 'N/A' }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
                            background: {{ $record->status === 'hadir' ? '#E6F6EC' : ($record->status === 'sakit_izin' ? '#FEF3C7' : '#FADBD8') }};
                            color: {{ $record->status === 'hadir' ? '#1DB173' : ($record->status === 'sakit_izin' ? '#F59E0B' : '#BA1A1A') }};">
                            {{ str_replace('_', ' ', ucfirst($record->status)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div style="margin-top: 1rem; font-size: 0.8rem; color: #6b7280; text-align: center;">
        <i class="fas fa-sync-alt"></i> Halaman auto-refresh setiap 3 detik
    </div>
</div>

<script>
    // Auto-refresh every 3 seconds
    setTimeout(() => location.reload(), 3000);
</script>
@endsection

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
            @forelse ($liveStream as $item)
                <tr>
                    <td style="font-weight: 700; color: var(--primary-blue);">{{ $item->waktu_tap }}</td>
                    <td style="display: flex; align-items: center; gap: 0.75rem;">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($item->mahasiswa->nama ?? '-') }}&background=003366&color=fff&size=32" style="border-radius: 8px;">
                        <div>
                            <div style="font-weight: 700;">{{ $item->mahasiswa->nama ?? '-' }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ $item->mahasiswa->nim ?? '-' }}</div>
                        </div>
                    </td>
                    <td>
                        <i class="fas fa-{{ ($item->metode_absensi === 'RFID' ? 'id-card' : ($item->metode_absensi === 'Fingerprint' ? 'fingerprint' : ($item->metode_absensi === 'Face' ? 'camera' : 'barcode'))) }}"></i> 
                        {{ $item->metode_absensi }}
                    </td>
                    <td>
                        <span class="status-pill {{ ($item->status ?? '') === 'Telat' ? 'status-late' : (($item->status ?? '') === 'Alpa' ? 'status-absent' : 'status-present') }}">
                            {{ $item->status }}
                        </span>
                    </td>
                    <td><button style="border: none; background: none; color: var(--text-muted); cursor: pointer;"><i class="fas fa-ellipsis-v"></i></button></td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 2rem; color: #6b7280;">Belum ada aktivitas hari ini.</td>
                </tr>
            @endforelse
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
