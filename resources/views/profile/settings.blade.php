@extends('layouts.app')

@section('content')
<div style="max-width: 800px;">
    <div class="glass-card" style="margin-bottom: 2rem;">
        <h3 class="display-font" style="margin-bottom: 2rem;">Pengaturan Akun</h3>
        <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 3rem;">
            <img src="https://ui-avatars.com/api/?name=Dandi+Apriadi&background=003366&color=fff" style="width: 100px; height: 100px; border-radius: var(--radius-xl);">
            <div>
                <button class="btn-kinetic">Ganti Foto</button>
                <button class="btn-secondary" style="margin-left: 0.5rem;">Hapus</button>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">JPG, GIF atau PNG. Maksimal 2MB.</p>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" class="form-control" value="Dandi Apriadi">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" class="form-control" value="dandi@poltek.ac.id">
            </div>
        </div>
        <button class="btn-kinetic" style="margin-top: 2rem;">UPDATE PROFIL</button>
    </div>

    <div class="glass-card">
        <h3 class="display-font" style="margin-bottom: 1.5rem; color: #BA1A1A;">Keamanan</h3>
        <div class="form-group">
            <label>Password Saat Ini</label>
            <input type="password" class="form-control" placeholder="Masukkan password lama">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" class="form-control" placeholder="Minimum 8 karakter">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" class="form-control" placeholder="Ulangi password baru">
            </div>
        </div>
        <button class="btn-kinetic" style="margin-top: 2rem;">UBAH PASSWORD</button>
    </div>
</div>
@endsection
