@extends('layouts.app')

@section('content')
<div class="glass-card" style="max-width: 900px; margin: 0 auto;">
    <h3 class="display-font" style="margin-bottom: 1rem;">Edit Mahasiswa</h3>

    @if ($errors->any())
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('mahasiswa.update', $mahasiswa) }}" method="POST" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:0.75rem;">
        @csrf
        @method('PUT')

        <div>
            <label style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">NIM</label>
            <input name="nim" type="text" value="{{ old('nim', $mahasiswa->nim) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;" required>
        </div>

        <div>
            <label style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Nama</label>
            <input name="nama" type="text" value="{{ old('nama', $mahasiswa->nama) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;" required>
        </div>

        <div>
            <label style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Kelas</label>
            <select name="kelas_id" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;" required>
                @foreach ($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" {{ (string) old('kelas_id', $mahasiswa->kelas_id) === (string) $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">RFID UID</label>
            <input name="rfid_uid" type="text" value="{{ old('rfid_uid', $mahasiswa->rfid_uid) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;">
        </div>

        <div>
            <label style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Barcode ID</label>
            <input name="barcode_id" type="text" value="{{ old('barcode_id', $mahasiswa->barcode_id) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;">
        </div>

        <div>
            <label style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Fingerprint Data</label>
            <input name="fingerprint_data" type="text" value="{{ old('fingerprint_data', $mahasiswa->fingerprint_data) }}" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px;">
        </div>

        <div style="grid-column:1/-1;">
            <label style="font-size:0.75rem; color:#6b7280; display:block; margin-bottom:0.4rem;">Face Model Data</label>
            <textarea name="face_model_data" style="width:100%; padding:0.75rem; border:none; background:#F1F3F5; border-radius:8px; min-height:90px;">{{ old('face_model_data', $mahasiswa->face_model_data) }}</textarea>
        </div>

        <div style="grid-column:1/-1; display:flex; gap:0.75rem; margin-top:0.5rem;">
            <button type="submit" class="btn-kinetic">Simpan Perubahan</button>
            <a href="{{ route('mahasiswa') }}" class="btn-kinetic" style="background:#F1F3F5; text-decoration:none;">Kembali</a>
        </div>
    </form>
</div>
@endsection
