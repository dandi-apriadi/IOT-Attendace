@extends('layouts.app')

@section('content')
<div style="max-width: 600px; margin: 0 auto;">
    <div class="glass-card">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
            <a href="{{ route('matakuliah') }}" style="color: var(--text-muted); text-decoration: none;"><i class="fas fa-arrow-left"></i></a>
            <h3 class="display-font">Edit Mata Kuliah</h3>
        </div>

        <form action="{{ route('matakuliah.update', $mk->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label>Kode Mata Kuliah</label>
                <input type="text" name="kode_mk" value="{{ old('kode_mk', $mk->kode_mk) }}" class="form-control" required>
                @error('kode_mk')
                    <span style="color: #BA1A1A; font-size: 0.8rem;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Nama Mata Kuliah</label>
                <input type="text" name="nama_mk" value="{{ old('nama_mk', $mk->nama_mk) }}" class="form-control" required>
                @error('nama_mk')
                    <span style="color: #BA1A1A; font-size: 0.8rem;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>SKS</label>
                <input type="number" name="sks" value="{{ old('sks', $mk->sks) }}" class="form-control" min="1" max="6" required>
                @error('sks')
                    <span style="color: #BA1A1A; font-size: 0.8rem;">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label>Semester Akademik</label>
                <select name="semester_akademik_id" class="form-control">
                    <option value="">-- Pilih Semester --</option>
                    @foreach ($semesterList as $sem)
                        <option value="{{ $sem->id }}" {{ old('semester_akademik_id', $mk->semester_akademik_id) == $sem->id ? 'selected' : '' }}>
                            {{ $sem->display_name }}
                        </option>
                    @endforeach
                </select>
                <small style="color: #6b7280; font-size: 0.75rem;">Pilih semester di mana mata kuliah ini diajarkan</small>
                @error('semester_akademik_id')
                    <span style="color: #BA1A1A; font-size: 0.8rem;">{{ $message }}</span>
                @enderror
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn-kinetic" style="flex-grow: 1;">Simpan Perubahan</button>
                <a href="{{ route('matakuliah') }}" class="btn-secondary" style="text-decoration: none; text-align: center;">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
