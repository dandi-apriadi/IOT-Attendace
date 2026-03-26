@extends('layouts.app')

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="glass-card">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
            <a href="{{ route('jadwal') }}" style="color: var(--text-muted); text-decoration: none;"><i class="fas fa-arrow-left"></i></a>
            <h3 class="display-font">Edit Jadwal Perkuliahan</h3>
        </div>

        <form action="{{ route('jadwal.update', $jadwal->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label>Mata Kuliah</label>
                    <select name="mata_kuliah_id" class="form-control" required>
                        @foreach ($mataKuliahList as $mk)
                            <option value="{{ $mk->id }}" {{ $jadwal->mata_kuliah_id == $mk->id ? 'selected' : '' }}>{{ $mk->nama_mk }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Kelas</label>
                    <select name="kelas_id" class="form-control" required>
                        @foreach ($kelasList as $kelas)
                            <option value="{{ $kelas->id }}" {{ $jadwal->kelas_id == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Dosen Pengampu</label>
                    <select name="user_id" class="form-control" required>
                        @foreach ($dosenList as $dosen)
                            <option value="{{ $dosen->id }}" {{ $jadwal->user_id == $dosen->id ? 'selected' : '' }}>{{ $dosen->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Hari</label>
                    <select name="hari" class="form-control" required>
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                            <option value="{{ $day }}" {{ $jadwal->hari == $day ? 'selected' : '' }}>{{ $day }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Jam Mulai</label>
                    <input type="time" name="jam_mulai" value="{{ substr($jadwal->jam_mulai,0,5) }}" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Jam Selesai</label>
                    <input type="time" name="jam_selesai" value="{{ substr($jadwal->jam_selesai,0,5) }}" class="form-control" required>
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn-kinetic" style="flex-grow: 1;">Simpan Perubahan</button>
                <a href="{{ route('jadwal') }}" class="btn-secondary" style="text-decoration: none; text-align: center;">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
