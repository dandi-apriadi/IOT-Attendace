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

            <div class="form-group">
                <label>Semester Level</label>
                <input type="number" name="semester_level" min="1" max="14" value="{{ old('semester_level', $kelas->semester_level) }}" class="form-control" placeholder="Contoh: 3">
                @error('semester_level')
                    <span style="color: #BA1A1A; font-size: 0.8rem;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Kelas Berikutnya</label>
                <select name="next_kelas_id" class="form-control">
                    <option value="">Belum diatur</option>
                    @foreach ($allKelas as $nextKelas)
                        <option value="{{ $nextKelas->id }}" {{ (string) old('next_kelas_id', $kelas->next_kelas_id) === (string) $nextKelas->id ? 'selected' : '' }}>{{ $nextKelas->nama_kelas }}</option>
                    @endforeach
                </select>
                @error('next_kelas_id')
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
