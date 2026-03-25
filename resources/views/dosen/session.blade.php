@extends('layouts.app')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="glass-card" style="text-align: center; padding: 4rem 2rem;">
        <div style="font-size: 4rem; color: var(--kinetic-yellow); margin-bottom: 2rem;"><i class="fas fa-play-circle"></i></div>
        <h2 class="display-font" style="font-size: 2rem; margin-bottom: 1rem;">Aktivasi Sesi Presensi</h2>
        <p style="color: var(--text-muted); margin-bottom: 3rem;">Pilih mata kuliah dan kelas untuk mulai menerima data dari terminal IoT.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; text-align: left; margin-bottom: 3rem;">
            <div class="form-group">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-muted);">Mata Kuliah</label>
                <select style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px; font-weight: 600;">
                    <option>Pemrograman Web Lanjut (MK-001)</option>
                    <option>IoT & Sistem Tertanam (MK-002)</option>
                </select>
            </div>
            <div class="form-group">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-muted);">Kelas</label>
                <select style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px; font-weight: 600;">
                    <option>IK-2A</option>
                    <option>IK-2B</option>
                </select>
            </div>
        </div>

        <div style="background: rgba(0, 30, 64, 0.05); padding: 2rem; border-radius: var(--radius-xl); margin-bottom: 3rem;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                <i class="fas fa-microchip" style="color: #1DB173;"></i>
                <span style="font-weight: 700; font-size: 0.9rem;">Terminal R-402 (Ready)</span>
            </div>
            <p style="font-size: 0.8rem; opacity: 0.7;">Perangkat keras akan otomatis beralih ke mode 'Scanning' setelah Anda menekan tombol di bawah.</p>
        </div>

        <form action="{{ route('monitoring') }}" method="GET">
            <button class="btn-kinetic" style="padding: 1.25rem 3rem; font-size: 1.1rem; width: 100%;">MULAI SESI SEKARANG</button>
        </form>
    </div>
</div>
@endsection
