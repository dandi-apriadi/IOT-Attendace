@extends('layouts.app')

@section('title', 'Data Master Mahasiswa')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Mahasiswa</span>
@endsection

@section('content')
<div class="glass-card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Seluruh Mahasiswa</h3>
    </div>

    @if (session('success'))
        <div style="margin-bottom: 1rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" action="{{ route('mahasiswa') }}" style="margin-bottom: 1.25rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <input name="q" value="{{ $search }}" type="text" style="flex: 1 1 260px; padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" placeholder="Cari berdasarkan Nama atau NIM...">
        <select name="kelas_id" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px; min-width: 180px;">
            <option value="">Semua Kelas</option>
            @foreach ($kelasList as $kelas)
                <option value="{{ $kelas->id }}" {{ (string) $kelasId === (string) $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
            @endforeach
        </select>
        <button class="btn-kinetic" type="submit">Filter</button>
    </form>

    <form action="{{ route('mahasiswa.store') }}" method="POST" style="display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 0.75rem; margin-bottom: 1.5rem;">
        @csrf
        <input name="nim" type="text" value="{{ old('nim') }}" placeholder="NIM" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" required>
        <input name="nama" type="text" value="{{ old('nama') }}" placeholder="Nama lengkap" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" required>
        <select name="kelas_id" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" required>
            <option value="">Pilih Kelas</option>
            @foreach ($kelasList as $kelas)
                <option value="{{ $kelas->id }}" {{ old('kelas_id') == $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
            @endforeach
        </select>
        <input name="rfid_uid" type="text" value="{{ old('rfid_uid') }}" placeholder="RFID UID (opsional)" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
        <input name="barcode_id" type="text" value="{{ old('barcode_id') }}" placeholder="Barcode ID (opsional)" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
        <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Tambah Mahasiswa</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama Lengkap</th>
                <th>Kelas</th>
                <th>Identitas IoT</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mahasiswaList as $mahasiswa)
                <tr>
                    <td>
                        <div style="font-family: monospace; font-weight: 700; color: var(--primary-blue-container); background: #F1F3F5; padding: 2px 8px; border-radius: 4px; display: inline-block;">
                            {{ $mahasiswa->nim }}
                        </div>
                    </td>
                    <td style="font-weight: 700;">{{ $mahasiswa->nama }}</td>
                    <td>{{ $mahasiswa->kelas?->nama_kelas ?? '-' }}</td>
                    <td>
                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                            @if ($mahasiswa->rfid_uid)
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">RFID</span>
                            @endif
                            @if ($mahasiswa->barcode_id)
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">BARCODE</span>
                            @endif
                            @if ($mahasiswa->fingerprint_data)
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">FINGERPRINT</span>
                            @endif
                            @if ($mahasiswa->face_model_data)
                                <span style="font-size: 0.65rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">FACE</span>
                            @endif
                            @if (!$mahasiswa->rfid_uid && !$mahasiswa->barcode_id && !$mahasiswa->fingerprint_data && !$mahasiswa->face_model_data)
                                <span style="font-size: 0.65rem; background: #F1F3F5; color: #6b7280; padding: 2px 6px; border-radius: 4px; font-weight: 700;">BELUM TERDAFTAR</span>
                            @endif
                        </div>
                    </td>
                    <td style="display:flex; gap: 0.5rem; align-items:center;">
                        <a href="{{ route('student-detail', ['id' => $mahasiswa->id]) }}" class="btn-kinetic" style="padding: 0.45rem 0.55rem; font-size: 0.8rem; text-decoration: none;"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('mahasiswa.edit', $mahasiswa) }}" class="btn-kinetic" style="padding: 0.45rem 0.55rem; font-size: 0.8rem; background: #F1F3F5; text-decoration:none;"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('mahasiswa.destroy', $mahasiswa) }}" method="POST" onsubmit="return confirm('Hapus data mahasiswa ini?');" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-kinetic" style="padding: 0.45rem 0.55rem; font-size: 0.8rem; background: #FDECEC; color: #BA1A1A;"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#6b7280;">Belum ada data mahasiswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $mahasiswaList->links() }}
    </div>
</div>

<div class="glass-card" style="background: var(--primary-blue-container); color: #fff;">
    <h3 class="display-font" style="margin-bottom: 1rem;">Registrasi Cepat Hardware</h3>
    <p style="font-size: 0.9rem; opacity: 0.7; margin-bottom: 1.5rem;">Gunakan perintah API untuk mendaftarkan UID RFID mahasiswa langsung dari perangkat ESP32 di lab.</p>
    <div style="display: flex; align-items: center; gap: 1rem;">
        <code style="background: rgba(255, 255, 255, 0.1); padding: 0.75rem 1.5rem; border-radius: 8px; flex-grow: 1;">POST /api/register-tag { "nim": "...", "uid": "..." }</code>
        <button class="btn-kinetic" style="white-space: nowrap;">KLIK UNTUK COPY</button>
    </div>
</div>
@endsection
