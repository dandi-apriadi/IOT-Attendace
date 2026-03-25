@extends('layouts.app')

@section('content')
<div style="max-width: 600px; margin: 0 auto;">
    <div class="glass-card">
        <h3 class="display-font" style="margin-bottom: 2rem;">Tambah User Baru</h3>

        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" class="form-control" name="name" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" required>
                @error('name')
                    <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="contoh@poltek.ac.id" required>
                @error('email')
                    <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required style="padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;}">
                    <option value="">-- Pilih Role --</option>
                    <option value="admin">Admin (Akses Penuh)</option>
                    <option value="dosen">Dosen (Akses Terbatas)</option>
                </select>
                @error('role')
                    <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" name="password" placeholder="Minimum 8 karakter" required>
                @error('password')
                    <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" class="form-control" name="password_confirmation" placeholder="Ulangi password" required>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn-kinetic" style="flex: 1; padding: 0.75rem; border: none; cursor: pointer;">SIMPAN USER</button>
                <a href="{{ route('users') }}" class="btn-secondary" style="flex: 1; padding: 0.75rem; text-decoration: none; text-align: center; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer;">BATAL</a>
            </div>
        </form>
    </div>
</div>
@endsection
