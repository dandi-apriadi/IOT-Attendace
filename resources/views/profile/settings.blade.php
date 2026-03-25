@extends('layouts.app')

@section('content')
<div style="max-width: 800px;">
    @if (session('success'))
        <div style="background: #E6F6EC; color: #1DB173; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #1DB173;">
            <strong>✓ Berhasil!</strong> {{ session('success') }}
        </div>
    @endif

    <div class="glass-card" style="margin-bottom: 2rem;">
        <h3 class="display-font" style="margin-bottom: 2rem;">Pengaturan Akun</h3>
        <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 3rem;">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=003366&color=fff" style="width: 100px; height: 100px; border-radius: var(--radius-xl);">
            <div>
                <div style="font-weight: 700;">{{ $user->name }}</div>
                <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;">{{ $user->email }}</div>
                <div style="font-size: 0.75rem; background: #F1F3F5; color: #6b7280; padding: 0.25rem 0.75rem; border-radius: 999px; display: inline-block; margin-top: 0.75rem;">{{ ucfirst($user->role) }}</div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem;">Avatar dihasilkan otomatis dari nama (read-only).</p>
            </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <button type="submit" class="btn-kinetic" style="margin-top: 2rem; border: none; cursor: pointer;">UPDATE PROFIL</button>
        </form>
    </div>

    <div class="glass-card">
        <h3 class="display-font" style="margin-bottom: 1.5rem; color: #BA1A1A;">Keamanan</h3>
        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Password Saat Ini</label>
                <input type="password" class="form-control" name="current_password" placeholder="Masukkan password lama" required>
                @error('current_password')
                    <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                @enderror
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1.5rem;">
                <div class="form-group">
                    <label>Password Baru</label>
                    <input type="password" class="form-control" name="password" placeholder="Minimum 8 karakter" required>
                    @error('password')
                        <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <input type="password" class="form-control" name="password_confirmation" placeholder="Ulangi password baru" required>
                </div>
            </div>
            <button type="submit" class="btn-kinetic" style="margin-top: 2rem; border: none; cursor: pointer;">UBAH PASSWORD</button>
        </form>
    </div>
</div>
@endsection
