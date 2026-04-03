@extends('layouts.app')

@section('title', 'Jadwal Perkuliahan')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Jadwal</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Management Jadwal</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($jadwalList->total()) }} jadwal</span>
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

    <form action="{{ route('jadwal.store') }}" method="POST" style="margin-bottom: 2rem; background: #F8FAFC; padding: 1.5rem; border-radius: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        @csrf
        <div class="form-group" style="margin-bottom:0;">
            <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; display: block; margin-bottom: 0.5rem;">SEMESTER</label>
            <select name="semester_akademik_id" class="form-control" required>
                <option value="">Pilih Semester</option>
                @foreach ($semesterList as $semester)
                    <option value="{{ $semester->id }}" {{ (string) old('semester_akademik_id', $activeSemesterId) === (string) $semester->id ? 'selected' : '' }}>{{ $semester->display_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; display: block; margin-bottom: 0.5rem;">CARI MATA KULIAH</label>
            <select name="mata_kuliah_id" class="form-control" required>
                <option value="">Pilih Mata Kuliah</option>
                @foreach ($mataKuliahList as $mk)
                    <option value="{{ $mk->id }}">{{ $mk->nama_mk }} ({{ $mk->kode_mk }})</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; display: block; margin-bottom: 0.5rem;">KELAS</label>
            <select name="kelas_id" class="form-control" required>
                <option value="">Pilih Kelas</option>
                @foreach ($kelasList as $kelas)
                    <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; display: block; margin-bottom: 0.5rem;">DOSEN PENGAMPU</label>
            <select name="user_id" class="form-control" required>
                <option value="">Pilih Dosen</option>
                @foreach ($dosenList as $dosen)
                    <option value="{{ $dosen->id }}">{{ $dosen->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; display: block; margin-bottom: 0.5rem;">HARI</label>
            <select name="hari" class="form-control" required>
                <option value="Monday">Senin (Monday)</option>
                <option value="Tuesday">Selasa (Tuesday)</option>
                <option value="Wednesday">Rabu (Wednesday)</option>
                <option value="Thursday">Kamis (Thursday)</option>
                <option value="Friday">Jumat (Friday)</option>
                <option value="Saturday">Sabtu (Saturday)</option>
                <option value="Sunday">Minggu (Sunday)</option>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; display: block; margin-bottom: 0.5rem;">JAM MULAI</label>
            <input type="time" name="jam_mulai" class="form-control" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label style="font-size: 0.75rem; font-weight: 700; color: #6b7280; display: block; margin-bottom: 0.5rem;">JAM SELESAI</label>
            <input type="time" name="jam_selesai" class="form-control" required>
        </div>
        <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end; margin-top: 1rem;">
            <button type="submit" class="btn-kinetic"><i class="fas fa-calendar-plus"></i> Simpan Jadwal</button>
        </div>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>Semester</th>
                <th>Hari</th>
                <th>Mata Kuliah</th>
                <th>Kelas</th>
                <th>Dosen Pengampu</th>
                <th>Waktu</th>
                <th style="text-align: right;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jadwalList as $jadwal)
                <tr>
                    <td>{{ $jadwal->semesterAkademik?->display_name ?? 'Belum ditentukan' }}</td>
                    <td><strong>{{ $jadwal->hari }}</strong></td>
                    <td style="font-weight: 700; color: var(--primary-blue-container);">{{ $jadwal->mata_kuliah->nama_mk ?? '-' }}</td>
                    <td>{{ $jadwal->kelas->nama_kelas ?? '-' }}</td>
                    <td><span style="font-size: 0.85rem;">{{ $jadwal->dosen->name ?? '-' }}</span></td>
                    <td><code style="background: #F1F3F5; padding: 2px 6px; border-radius: 4px;">{{ substr($jadwal->jam_mulai,0,5) }} - {{ substr($jadwal->jam_selesai,0,5) }}</code></td>
                    <td style="text-align: right; display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <a href="{{ route('jadwal.edit', $jadwal->id) }}" class="btn-kinetic" style="padding: 0.45rem 0.6rem; font-size: 0.75rem; background: #F1F3F5; color: var(--text-primary); box-shadow: none; text-decoration: none;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('jadwal.destroy', $jadwal->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal ini?');" style="margin:0;">
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
                    <td colspan="7" style="text-align:center; color:#6b7280;">Belum ada data jadwal.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-container">
        {{ $jadwalList->links() }}
    </div>
</div>
@endsection
