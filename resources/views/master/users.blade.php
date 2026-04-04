@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Pengguna</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Admin & Dosen Terdaftar</h3>
        <a href="{{ route('users.create') }}" class="btn-kinetic" style="text-decoration: none; padding: 0.75rem 1.5rem; font-size: 0.9rem; cursor: pointer;">+ Tambah User</a>
    </div>

    @if (session('success'))
        <div style="background: #E6F6EC; color: #1DB173; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #1DB173;">
            <strong>✓ Berhasil!</strong> {{ session('success') }}
        </div>
    @endif

    <table style="width: 100%; font-size: 0.9rem;">
        <thead>
            <tr>
                <th style="text-align: left;">Nama</th>
                <th style="text-align: left;">Email</th>
                <th style="text-align: center;">Role</th>
                <th style="text-align: center;">Terdaftar</th>
                <th style="text-align: center; width: 200px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr style="border-bottom: 1px solid #e5e7eb;">
                    <td style="padding: 1rem 0;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=003366&color=fff&size=32" style="width: 32px; height: 32px; border-radius: 8px;">
                            <span style="font-weight: 600;">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td style="padding: 1rem 0; color: #6b7280;">{{ $user->email }}</td>
                    <td style="padding: 1rem 0; text-align: center;">
                        <span style="background: {{ $user->role === 'admin' ? '#FEE2E2' : '#E6F6EC' }}; color: {{ $user->role === 'admin' ? '#BA1A1A' : '#1DB173' }}; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600;">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td style="padding: 1rem 0; text-align: center; color: #6b7280; font-size: 0.85rem;">
                        {{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}
                    </td>
                    <td style="padding: 1rem 0; text-align: center;">
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <a href="{{ route('users.edit', $user) }}" class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5; color: #000; text-decoration: none; font-size: 0.8rem; border-radius: 8px; cursor: pointer;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('users.reset-password', $user) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" class="btn-kinetic" style="padding: 0.5rem; background: #FEF3C7; color: #991b1b; border: none; font-size: 0.8rem; border-radius: 8px; cursor: pointer;"
                                    onclick="return confirm('Reset password untuk {{ $user->name }}?')">
                                    <i class="fas fa-key"></i>
                                </button>
                            </form>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" style="display: inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-kinetic" style="padding: 0.5rem; background: #FEE2E2; color: #BA1A1A; border: none; font-size: 0.8rem; border-radius: 8px; cursor: pointer;"
                                    onclick="return confirm('Hapus user {{ $user->name }}?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada user</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $users->links() }}
    </div>
</div>
@endsection
