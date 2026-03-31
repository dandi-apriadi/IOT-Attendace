@extends('layouts.app')

@section('title', 'Manajemen Kelas')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Kelas</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Seluruh Kelas</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($kelasList->total()) }} kelas</span>
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

    <!-- Add Class Form -->
    <div class="glass-card" style="background: #f8fafc; padding: 1.25rem; margin-bottom: 2rem;">
        <h4 class="display-font" style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-muted); text-transform: uppercase;">Tambah Kelas Baru</h4>
        <form action="{{ route('kelas.store') }}" method="POST" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @csrf
            <input name="nama_kelas" type="text" placeholder="Nama Kelas (ex: IK-3B)" class="form-control" style="flex: 1; min-width: 200px;" required>
            <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Simpan</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
        @forelse ($kelasList as $kelas)
            <div class="glass-card" style="background: #fff; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 class="display-font" style="font-size: 1.2rem;">{{ $kelas->nama_kelas }}</h4>
                        <span class="status-pill status-present" style="font-size: 0.65rem;">{{ number_format($kelas->mahasiswa_count) }} Mahasiswa</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Database Management: {{ $kelas->nama_kelas }}</p>
                </div>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; border-top: 1px solid #f1f3f5; padding-top: 1rem;">
                    <a href="{{ route('kelas.edit', $kelas->id) }}" class="btn-kinetic" style="flex: 1; padding: 0.5rem; font-size: 0.75rem; background: #F1F3F5; color: var(--text-primary); text-decoration: none; text-align: center; box-shadow: none;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('kelas.destroy', $kelas->id) }}" method="POST" onsubmit="return confirm('Hapus kelas ini?');" style="flex: 1; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-kinetic" style="width: 100%; padding: 0.5rem; font-size: 0.75rem; background: #FDECEC; color: #BA1A1A; box-shadow: none;">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1; color:#6b7280;">Belum ada data kelas.</div>
        @endforelse
    </div>

    <div class="pagination-container">
        {{ $kelasList->links() }}
    </div>
</div>
@endsection
