@extends('layouts.app')

@section('content')
<div style="max-width: 500px; margin: 0 auto;">
    <div class="glass-card">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
            <a href="{{ route('kelas') }}" style="color: var(--text-muted); text-decoration: none;"><i class="fas fa-arrow-left"></i></a>
            <h3 class="display-font">Edit Data Kelas</h3>
        </div>

        <form action="{{ route('kelas.update', $kelas->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>Nama Kelas</label>
                <input type="text" name="nama_kelas" value="{{ old('nama_kelas', $kelas->nama_kelas) }}" class="form-control" required placeholder="Contoh: IK-3B">
                @error('nama_kelas')
                    <span style="color: #BA1A1A; font-size: 0.8rem;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn-kinetic" style="flex-grow: 1;">Simpan Perubahan</button>
                <a href="{{ route('kelas') }}" class="btn-secondary" style="text-decoration: none; text-align: center;">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
