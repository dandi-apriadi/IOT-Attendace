@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font">Manajemen Kelas</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($kelasList->total()) }} kelas</span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
        @forelse ($kelasList as $kelas)
            <div class="glass-card" style="background: #fff; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4 class="display-font" style="font-size: 1.2rem;">{{ $kelas->nama_kelas }}</h4>
                    <span class="status-pill status-present">{{ number_format($kelas->mahasiswa_count) }} Mahasiswa</span>
                </div>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.5rem;">Data realtime dari database kelas.</p>
            </div>
        @empty
            <div style="grid-column:1/-1; color:#6b7280;">Belum ada data kelas.</div>
        @endforelse
    </div>

    <div style="margin-top:1rem;">
        {{ $kelasList->links() }}
    </div>
</div>
@endsection
