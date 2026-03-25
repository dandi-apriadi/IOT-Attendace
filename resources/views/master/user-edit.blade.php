@extends('layouts.app')

@section('content')
<div style="max-width: 600px; margin: 0 auto;">
    <div class="glass-card">
        <h3 class="display-font" style="margin-bottom: 2rem;">Edit User: {{ $user->name }}</h3>

        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf @method('PUT')
            
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

            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required style="padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;}">
                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin (Akses Penuh)</option>
                    <option value="dosen" {{ $user->role === 'dosen' ? 'selected' : '' }}>Dosen (Akses Terbatas)</option>
                </select>
                @error('role')
                    <span style="font-size: 0.75rem; color: #BA1A1A;">{{ $message }}</span>
                @enderror
            </div>

            <div style="background: #F9FAFB; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                <p style="font-size: 0.85rem; color: #6b7280; margin: 0;">
                    <i class="fas fa-info-circle"></i> &nbsp; Untuk mengubah password user, gunakan tombol "Reset Password" di halaman daftar.
                </p>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="submit" class="btn-kinetic" style="flex: 1; padding: 0.75rem; border: none; cursor: pointer;">SIMPAN PERUBAHAN</button>
                <a href="{{ route('users') }}" class="btn-secondary" style="flex: 1; padding: 0.75rem; text-decoration: none; text-align: center; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer;">BATAL</a>
            </div>
        </form>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h4 style="color: #BA1A1A; margin-bottom: 1rem;">Danger Zone</h4>
            <form action="{{ route('users.destroy', $user) }}" method="POST">
                @csrf @method('DELETE')
                <button type="submit" class="btn-kinetic" style="background: #BA1A1A; color: #fff; padding: 0.75rem 1.5rem; border: none; cursor: pointer; border-radius: 8px;"
                    onclick="return confirm('Apakah Anda yakin ingin menghapus user ini? Proses ini tidak bisa dibatalkan.')">
                    <i class="fas fa-trash"></i> HAPUS USER SELAMANYA
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
