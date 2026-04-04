@extends('layouts.app')

@section('title', $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('users') }}" style="color: inherit; text-decoration: none;">Pengguna</a>
    <span class="breadcrumb-sep">/</span>
    <span>{{ $user->exists ? 'Edit' : 'Tambah' }}</span>
@endsection

@section('content')
<div class="glass-card" style="max-width: 600px;">
    <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container); margin-bottom: 1.5rem;">
        {{ $user->exists ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}
    </h3>

    @if ($errors->any())
        <div style="background: #FEE2E2; color: #BA1A1A; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #BA1A1A;">
            <strong>✗ Terjadi kesalahan:</strong>
            <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" method="POST">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                style="width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.9rem;" />
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                style="width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.9rem;" />
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                Password {{ $user->exists ? '(kosongkan jika tidak ingin mengubah)' : '' }}
            </label>
            <input type="password" name="password" {{ $user->exists ? '' : 'required' }}
                style="width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.9rem;" />
        </div>

        <div style="margin-bottom: 1.25rem;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">
                Konfirmasi Password {{ $user->exists ? '(kosongkan jika tidak ingin mengubah)' : '' }}
            </label>
            <input type="password" name="password_confirmation" {{ $user->exists ? '' : 'required' }}
                style="width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.9rem;" />
        </div>

        <div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">Role</label>
            <select name="role" required
                style="width: 100%; padding: 0.7rem 0.85rem; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 0.9rem;">
                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="dosen" {{ old('role', $user->role) === 'dosen' ? 'selected' : '' }}>Dosen</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <button type="submit" class="btn-kinetic" style="padding: 0.7rem 1.5rem; font-size: 0.9rem; border: none; cursor: pointer;">
                <i class="fas fa-save"></i> {{ $user->exists ? 'Perbarui' : 'Simpan' }}
            </button>
            <a href="{{ route('users') }}" class="btn-kinetic" style="text-decoration: none; padding: 0.7rem 1.5rem; font-size: 0.9rem; background: #F1F5F9; color: var(--primary-dark);">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
