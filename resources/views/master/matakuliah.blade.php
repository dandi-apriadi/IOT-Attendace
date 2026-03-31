@extends('layouts.app')

@section('title', 'Data Master Mata Kuliah')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Mata Kuliah</span>
@endsection

@section('content')
<div class="glass-card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Mata Kuliah</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($mataKuliahList->total()) }} mata kuliah</span>
    </div>
    
    @if (session('success'))
        <div style="margin-bottom: 1rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('matakuliah.store') }}" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 0.75rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px;">
        @csrf
        <div class="form-group" style="margin-bottom:0;">
            <input name="kode_mk" type="text" value="{{ old('kode_mk') }}" placeholder="Kode MK (ex: IF101)" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input name="nama_mk" type="text" value="{{ old('nama_mk') }}" placeholder="Nama Mata Kuliah" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <input name="sks" type="number" value="{{ old('sks') }}" placeholder="SKS" class="form-control" min="1" max="6" required>
        </div>
        <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Tambah MK</button>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>Kode MK</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th>Penggunaan</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mataKuliahList as $mk)
                <tr>
                    <td style="font-family: monospace; font-weight: 700;">{{ $mk->kode_mk }}</td>
                    <td>{{ $mk->nama_mk }}</td>
                    <td>{{ $mk->sks }} SKS</td>
                    <td>{{ number_format($mk->jadwal_count) }} Sesi Kuliah</td>
                    <td style="text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <a href="{{ route('matakuliah.edit', $mk->id) }}" class="btn-kinetic" style="padding: 0.45rem 0.6rem; font-size: 0.75rem; background: #F1F3F5; color: var(--text-primary); box-shadow: none; text-decoration: none;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('matakuliah.destroy', $mk->id) }}" method="POST" onsubmit="return confirm('Hapus mata kuliah ini?');" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-kinetic" style="padding: 0.45rem 0.6rem; font-size: 0.75rem; background: #FDECEC; color: #BA1A1A; box-shadow: none;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#6b7280;">Belum ada data mata kuliah.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $mataKuliahList->links() }}
    </div>
</div>
@endsection
