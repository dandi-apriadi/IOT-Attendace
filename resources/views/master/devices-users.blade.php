@extends('layouts.app')

@section('title', 'User Perangkat')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('devices.index') }}" style="color: inherit; text-decoration: none;">Perangkat IoT</a>
    <span class="breadcrumb-sep">/</span>
    <span>User Perangkat</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container); margin:0;">User di {{ $device->name ?: $device->device_id }}</h3>
            <div style="font-size: 0.78rem; color: #6b7280; margin-top: 2px;">{{ $device->ip_address }}:{{ $device->port ?: 4370 }}</div>
        </div>
        <a href="{{ route('devices.index') }}" class="btn-kinetic" style="background:#F1F3F5; color:var(--text-primary); text-decoration:none; box-shadow:none; font-size:0.8rem;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    @if (session('success'))
        <div style="margin-bottom: 1rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            <ul style="margin:0; padding-left:1.2rem;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($error)
        <div style="background:#fdecec; color:#ba1a1a; padding:1rem; border-radius:8px;">
            <strong>Gagal membaca user dari perangkat:</strong><br>{{ $error }}
        </div>
    @else
        @php $unmatched = collect($deviceUsers)->where('matched', false)->count(); @endphp

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.75rem; margin-bottom:1rem;">
            <span style="font-size:0.85rem; color:#6b7280;">
                Total {{ count($deviceUsers) }} user di perangkat &middot;
                <span style="color:#b45309;">{{ $unmatched }} belum terdaftar</span>
            </span>

            @if ($unmatched > 0)
                <form action="{{ route('devices.import-users', $device->id) }}" method="POST" style="display:flex; gap:0.5rem; align-items:center; margin:0;"
                      onsubmit="return confirm('Import {{ $unmatched }} user yang belum terdaftar sebagai mahasiswa baru?');">
                    @csrf
                    <select name="kelas_id" class="form-control" style="min-width:180px; font-size:0.8rem;" required>
                        <option value="">-- Pilih Kelas Tujuan --</option>
                        @foreach ($kelasList as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-kinetic" style="font-size:0.8rem;">
                        <i class="fas fa-file-import"></i> Import yang belum terdaftar
                    </button>
                </form>
            @endif
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                <thead>
                    <tr style="text-align:left; border-bottom:2px solid #e5e7eb; color:#6b7280;">
                        <th style="padding:0.6rem;">UID</th>
                        <th style="padding:0.6rem;">User ID (NIM)</th>
                        <th style="padding:0.6rem;">Nama di Alat</th>
                        <th style="padding:0.6rem;">Role</th>
                        <th style="padding:0.6rem;">Status</th>
                        <th style="padding:0.6rem; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deviceUsers as $u)
                        <tr style="border-bottom:1px solid #f1f3f5;">
                            <td style="padding:0.6rem; font-family:monospace;">{{ $u['uid'] }}</td>
                            <td style="padding:0.6rem; font-family:monospace;">{{ $u['userid'] }}</td>
                            <td style="padding:0.6rem;">{{ $u['name'] ?: '-' }}</td>
                            <td style="padding:0.6rem;">{{ $u['role'] == 14 ? 'Admin' : 'User' }}</td>
                            <td style="padding:0.6rem;">
                                @if ($u['matched'])
                                    <span class="status-pill status-present" style="font-size:0.65rem;">Terdaftar</span>
                                    <div style="font-size:0.7rem; color:#6b7280;">{{ $u['mahasiswa_nama'] }}</div>
                                @else
                                    <span class="status-pill status-absent" style="font-size:0.65rem;">Belum terdaftar</span>
                                @endif
                            </td>
                            <td style="padding:0.6rem; text-align:right;">
                                <form action="{{ route('devices.remove-user', $device->id) }}" method="POST" style="margin:0;"
                                      onsubmit="return confirm('Hapus user uid {{ $u['uid'] }} ({{ $u['name'] }}) dari perangkat?');">
                                    @csrf
                                    <input type="hidden" name="uid" value="{{ $u['uid'] }}">
                                    <button type="submit" class="btn-kinetic" style="padding:0.35rem 0.6rem; font-size:0.7rem; background:#FDECEC; color:#BA1A1A; box-shadow:none;">
                                        <i class="fas fa-trash"></i> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="padding:2rem; text-align:center; color:#6b7280;">Tidak ada user di perangkat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
