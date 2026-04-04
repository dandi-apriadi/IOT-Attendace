@extends('layouts.app')

@section('title', 'Edit Semester Akademik')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span><a href="{{ route('semester') }}" style="color: inherit; text-decoration: none;">Semester Akademik</a></span>
    <span class="breadcrumb-sep">/</span>
    <span>Edit</span>
@endsection

@section('content')
<div class="glass-card" style="max-width: 700px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Edit Semester Akademik</h3>
        <a href="{{ route('semester') }}" class="btn-secondary" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.8rem;">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div style="margin-bottom: 1.5rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            <strong>✗ Validasi Gagal!</strong>
            <ul style="margin: 0.5rem 0 0 1rem; padding: 0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('semester.update', $semester->id) }}" method="POST" style="display: grid; gap: 1.25rem;">
        @csrf
        @method('PUT')

        <div>
            <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:0.5rem; color:#374151;">Nama Semester</label>
            <input name="nama_semester" type="text" value="{{ old('nama_semester', $semester->nama_semester) }}" class="form-input" placeholder="Ganjil/Genap" required>
        </div>

        <div>
            <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:0.5rem; color:#374151;">Tahun Ajaran</label>
            <input name="tahun_ajaran" type="text" value="{{ old('tahun_ajaran', $semester->tahun_ajaran) }}" class="form-input" placeholder="2025/2026" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:0.5rem; color:#374151;">Tanggal Mulai</label>
                <input name="tanggal_mulai" type="date" value="{{ old('tanggal_mulai', $semester->tanggal_mulai?->format('Y-m-d')) }}" class="form-input" required>
            </div>
            <div>
                <label style="display:block; font-size:0.85rem; font-weight:700; margin-bottom:0.5rem; color:#374151;">Tanggal Selesai</label>
                <input name="tanggal_selesai" type="date" value="{{ old('tanggal_selesai', $semester->tanggal_selesai?->format('Y-m-d')) }}" class="form-input" required>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <input name="is_active" type="checkbox" id="is_active" value="1" {{ old('is_active', $semester->is_active) ? 'checked' : '' }} style="width: 18px; height: 18px;">
            <label for="is_active" style="font-size:0.85rem; font-weight:600; color:#374151;">Set sebagai semester aktif</label>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
            <button type="submit" class="btn-kinetic" style="padding: 0.75rem 2rem;">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="{{ route('semester') }}" class="btn-kinetic" style="padding: 0.75rem 2rem; background: #F1F3F5; color: #374151; text-decoration: none;">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection
