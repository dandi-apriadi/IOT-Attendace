@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">System Audit Log</h3>
        <button class="btn-kinetic" style="background: #F1F3F5;"><i class="fas fa-filter"></i> Filter Logs</button>
    </div>
    
    <div style="background: #fff; border-radius: 12px; border: 1px solid #E9ECEF;">
        <div style="padding: 1.25rem; border-bottom: 1px solid #E9ECEF; display: grid; grid-template-columns: 150px 100px 1fr; gap: 1rem; align-items: center;">
            <div style="font-size: 0.75rem; color: var(--text-muted);">2026-03-25 08:45:12</div>
            <span class="status-pill status-present" style="font-size: 0.65rem;">SUCCESS</span>
            <div style="font-size: 0.85rem;"><strong>Admin</strong> melakukan registrasi RFID Tag baru untuk Mahasiswa <strong>22041010</strong>.</div>
        </div>
        <div style="padding: 1.25rem; border-bottom: 1px solid #E9ECEF; display: grid; grid-template-columns: 150px 100px 1fr; gap: 1rem; align-items: center;">
            <div style="font-size: 0.75rem; color: var(--text-muted);">2026-03-25 08:30:00</div>
            <span class="status-pill status-present" style="font-size: 0.65rem; background: var(--primary-blue); color: #fff;">SYSTEM</span>
            <div style="font-size: 0.85rem;">Sesi Perkuliahan <strong>Pemrograman Web (IK-2A)</strong> diaktifkan otomatis oleh jadwal.</div>
        </div>
        <div style="padding: 1.25rem; border-bottom: 1px solid #E9ECEF; display: grid; grid-template-columns: 150px 100px 1fr; gap: 1rem; align-items: center;">
            <div style="font-size: 0.75rem; color: var(--text-muted);">2026-03-25 08:25:22</div>
            <span class="status-pill status-absent" style="font-size: 0.65rem;">WARNING</span>
            <div style="font-size: 0.85rem;">Perangkat <strong>READER-L001</strong> kehilangan koneksi saat proses sinkronisasi fingerprint.</div>
        </div>
    </div>
</div>
@endsection
