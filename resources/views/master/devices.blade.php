@extends('layouts.app')

@section('title', 'Manajemen Perangkat')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Perangkat IoT</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Seluruh Perangkat</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($devicesList->total()) }} perangkat</span>
    </div>

    @if (session('success'))
        <div style="margin-bottom: 1.5rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="margin-bottom: 1.5rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('error') }}
        </div>
    @endif

    <!-- Add Device Form -->
    <div class="glass-card" style="background: #f8fafc; padding: 1.25rem; margin-bottom: 2rem;">
        <h4 class="display-font" style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-muted); text-transform: uppercase;">Tambah Perangkat Baru</h4>
        <form action="{{ route('devices.store') }}" method="POST" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @csrf
            <input name="device_id" type="text" placeholder="Device ID (ex: DEV-01)" class="form-control" style="flex: 1; min-width: 150px;" required>
            <input name="name" type="text" placeholder="Nama Perangkat (Opsional)" class="form-control" style="flex: 1; min-width: 150px;">
            <input name="ip_address" type="text" placeholder="IP Address (ex: 192.168.1.10)" class="form-control" style="flex: 1; min-width: 150px;">
            <input name="token_hash" type="text" placeholder="Token Auth" class="form-control" style="flex: 1; min-width: 150px;" required>
            
            <div style="display: flex; align-items: center; gap: 0.5rem; padding: 0 0.5rem;">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                <label for="is_active" style="font-size: 0.85rem; cursor: pointer;">Aktif</label>
            </div>
            
            <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Simpan</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
        @forelse ($devicesList as $device)
            <div class="glass-card" style="background: #fff; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid {{ $device->is_active ? '#1DB173' : '#BA1A1A' }};">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <div>
                            <h4 class="display-font" style="font-size: 1.1rem; margin: 0;">{{ $device->name ?: $device->device_id }}</h4>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; font-family: monospace;">{{ $device->device_id }}</div>
                        </div>
                        <span class="status-pill {{ $device->is_active ? 'status-present' : 'status-absent' }}" style="font-size: 0.65rem;">
                            {{ $device->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    
                    <div style="font-size: 0.8rem; color: #4b5563; margin-top: 1rem; display: flex; flex-direction: column; gap: 0.3rem;">
                        <div><i class="fas fa-network-wired" style="width: 16px; opacity: 0.5;"></i> IP: <strong>{{ $device->ip_address ?: '-' }}</strong></div>
                        <div><i class="fas fa-clock" style="width: 16px; opacity: 0.5;"></i> Last Seen: {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never' }}</div>
                    </div>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; border-top: 1px solid #f1f3f5; padding-top: 1rem;">
                    <a href="{{ route('devices.edit', $device->id) }}" class="btn-kinetic" style="flex: 1; padding: 0.5rem; font-size: 0.75rem; background: #F1F3F5; color: var(--text-primary); text-decoration: none; text-align: center; box-shadow: none;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('devices.destroy', $device->id) }}" method="POST" onsubmit="return confirm('Hapus perangkat ini?');" style="flex: 1; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-kinetic" style="width: 100%; padding: 0.5rem; font-size: 0.75rem; background: #FDECEC; color: #BA1A1A; box-shadow: none;">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1; color:#6b7280; text-align: center; padding: 2rem;">Belum ada data perangkat.</div>
        @endforelse
    </div>

    <div class="pagination-container" style="margin-top: 1.5rem;">
        {{ $devicesList->links() }}
    </div>
</div>
@endsection
