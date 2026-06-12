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

    @if (session('error'))
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="margin-bottom:1.25rem; padding:1rem; border-radius:10px; background:#f0fdf4; border:1px solid #bbf7d0;">
        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap;">
            <div style="max-width:620px;">
                <div style="font-weight:800; color:#166534; margin-bottom:0.25rem;">Tarik Biometrik dari Alat</div>
                <div style="font-size:0.84rem; color:#3f6b4e;">
                    Daftarkan kartu atau sidik jari langsung di mesin ZKTeco. Setelah itu pilih alat di sini untuk menarik data dan memperbarui mahasiswa yang NIM-nya sudah ada di sistem.
                </div>
            </div>

            @if ($zktecoDevices->isNotEmpty())
                <form action="{{ route('mahasiswa.pull-biometrics') }}" method="POST" onsubmit="return confirm('Tarik kartu dan sidik jari dari alat lalu perbarui mahasiswa yang sudah terdaftar?');" style="display:flex; gap:0.6rem; align-items:flex-end; flex-wrap:wrap; margin:0;">
                    @csrf
                    <div>
                        <label for="biometric_device_id" style="font-size:0.75rem; color:#3f6b4e; display:block; margin-bottom:0.35rem;">Alat ZKTeco</label>
                        <select id="biometric_device_id" name="device_id" style="min-width:240px; padding:0.7rem; border:none; background:#fff; border-radius:8px;" required>
                            @foreach ($zktecoDevices as $device)
                                <option value="{{ $device->id }}">{{ $device->name ?: $device->device_id }} ({{ $device->ip_address }}:{{ $device->port ?: 4370 }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn-kinetic" type="submit" style="background:#16a34a;">
                        <i class="fas fa-fingerprint"></i> Tarik & Update Data
                    </button>
                </form>
            @else
                <div style="font-size:0.82rem; color:#9a3412; background:#fff7ed; border:1px solid #fed7aa; padding:0.7rem 0.8rem; border-radius:8px;">
                    Belum ada alat ZKTeco aktif. Tambahkan di <a href="{{ route('devices.index') }}" style="color:#166534;">Perangkat IoT</a>.
                </div>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('mahasiswa') }}" style="margin-bottom: 1.25rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <input name="q" value="{{ $search }}" type="text" style="flex: 1 1 260px; padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" placeholder="Cari berdasarkan Nama atau NIM...">
        <select name="kelas_id" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px; min-width: 180px;">
            <option value="">Semua Kelas</option>
            @foreach ($kelasList as $kelas)
                <option value="{{ $kelas->id }}" {{ (string) $kelasId === (string) $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
            @endforeach
        </select>
        <select name="status_akademik" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px; min-width: 180px;">
            <option value="">Semua Status</option>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" {{ (string) $statusAkademik === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn-kinetic" type="submit">Filter</button>
        <a href="{{ route('semester.promotion') }}" class="btn-kinetic" style="text-decoration:none; background:#0ea5e9;"><i class="fas fa-arrow-up"></i> Review Kenaikan</a>
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
        <select name="status_akademik" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" required>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" {{ old('status_akademik', 'aktif') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <input name="semester_level" type="number" min="1" max="14" value="{{ old('semester_level') }}" placeholder="Semester (opsional)" style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
        <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Tambah Mahasiswa</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama Lengkap</th>
                <th>Kelas</th>
                <th>Status</th>
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
                        <div style="display:flex; gap:0.35rem; flex-wrap:wrap;">
                            <span style="font-size:0.65rem; background:#eef2ff; color:#3730a3; padding:2px 6px; border-radius:4px; font-weight:700;">{{ $statusOptions[$mahasiswa->status_akademik] ?? ucfirst($mahasiswa->status_akademik) }}</span>
                            @if ($mahasiswa->semester_level)
                                <span style="font-size:0.65rem; background:#f1f5f9; color:#334155; padding:2px 6px; border-radius:4px; font-weight:700;">S{{ $mahasiswa->semester_level }}</span>
                            @endif
                            @if ($mahasiswa->promotion_paused)
                                <span style="font-size:0.65rem; background:#fff7ed; color:#9a3412; padding:2px 6px; border-radius:4px; font-weight:700;">DITAHAN</span>
                            @endif
                        </div>
                    </td>
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
                    <td colspan="6" style="text-align:center; color:#6b7280;">Belum ada data mahasiswa.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $mahasiswaList->links() }}
    </div>
</div>

@endsection
