@extends('layouts.app')

@section('content')
<div class="glass-card" style="max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">{{ isset($correction) ? 'Edit Permintaan Koreksi' : 'Buat Permintaan Koreksi Baru' }}</h3>
        <a href="{{ route('correction') }}" class="btn-secondary" style="text-decoration: none; padding: 0.75rem 1.5rem;">← Kembali</a>
    </div>

    <form method="POST" action="{{ isset($correction) ? route('correction.update', $correction->id) : route('correction.store') }}" enctype="multipart/form-data">
        @csrf
        @if(isset($correction))
            @method('PUT')
        @endif

        <!-- Mahasiswa Selection -->
        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Mahasiswa</label>
            <select name="mahasiswa_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                <option value="">Pilih Mahasiswa...</option>
                @foreach($mahasiswas as $m)
                    <option value="{{ $m->id }}" {{ (isset($correction) && $correction->mahasiswa_id === $m->id) || old('mahasiswa_id') === (string)$m->id ? 'selected' : '' }}>
                        {{ $m->nama }} - {{ $m->nim }}
                    </option>
                @endforeach
            </select>
            @error('mahasiswa_id')
                <div style="color: #BA1A1A; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Jadwal Selection -->
        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Jadwal Kuliah</label>
            <select name="jadwal_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                <option value="">Pilih Jadwal...</option>
                @foreach($jadwals as $jadwal)
                    <option value="{{ $jadwal->id }}" {{ (isset($correction) && $correction->jadwal_id === $jadwal->id) || old('jadwal_id') === (string)$jadwal->id ? 'selected' : '' }}>
                        {{ $jadwal->mata_kuliah?->kode_mk ?? '-' }} - {{ $jadwal->mata_kuliah?->nama_mk ?? '-' }} | {{ $jadwal->kelas?->nama_kelas ?? '-' }} | {{ $jadwal->hari }} {{ $jadwal->jam_mulai }}-{{ $jadwal->jam_selesai }}
                    </option>
                @endforeach
            </select>
            @error('jadwal_id')
                <div style="color: #BA1A1A; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Tanggal Koreksi -->
        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Tanggal Absensi</label>
            <input type="date" name="tanggal" required value="{{ isset($correction) ? $correction->tanggal->format('Y-m-d') : old('tanggal') }}" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
            @error('tanggal')
                <div style="color: #BA1A1A; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Status Lama -->
        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Status Saat Ini</label>
            <select name="status_lama" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                <option value="">Pilih Status...</option>
                @foreach(($statusOptions ?? []) as $value => $label)
                    <option value="{{ $value }}" {{ (isset($correction) && $correction->status_lama === $value) || old('status_lama') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status_lama')
                <div style="color: #BA1A1A; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Status Baru -->
        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Status Koreksi</label>
            <select name="status_baru" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                <option value="">Pilih Status...</option>
                @foreach(($statusOptions ?? []) as $value => $label)
                    <option value="{{ $value }}" {{ (isset($correction) && $correction->status_baru === $value) || old('status_baru') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('status_baru')
                <div style="color: #BA1A1A; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Alasan -->
        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Alasan Koreksi</label>
            <textarea name="alasan" required style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; min-height: 120px; font-family: monospace;">{{ isset($correction) ? $correction->alasan : old('alasan') }}</textarea>
            @error('alasan')
                <div style="color: #BA1A1A; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Bukti Dokumen -->
        <div style="margin-bottom: 2rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Bukti Dokumen (PDF/JPG)</label>
            <input type="file" name="dokumen" accept=".pdf,.jpg,.jpeg,.png" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
            @if(isset($correction) && $correction->dokumen)
                <div style="margin-top: 0.5rem;">
                    <a href="{{ asset('storage/'.$correction->dokumen) }}" target="_blank" style="color: #0066CC; text-decoration: underline;">Lihat Dokumen Sebelumnya</a>
                </div>
            @endif
            @error('dokumen')
                <div style="color: #BA1A1A; font-size: 0.85rem; margin-top: 0.25rem;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Status Approval (untuk admin/dosen) -->
        @if(auth()->user()?->role === 'admin' || auth()->user()?->role === 'dosen')
            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Status Persetujuan</label>
                <select name="approval_status" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px;">
                    @foreach(($approvalStatusOptions ?? []) as $value => $label)
                        <option value="{{ $value }}" {{ (isset($correction) && $correction->approval_status === $value) || old('approval_status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem;">Catatan Persetujuan</label>
                <textarea name="approval_notes" style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; min-height: 100px; font-family: monospace;">{{ isset($correction) ? $correction->approval_notes : old('approval_notes') }}</textarea>
            </div>
        @endif

        <!-- Submit Buttons -->
        <div style="display: flex; gap: 1rem; margin-top: 2rem;">
            <button type="submit" class="btn-kinetic" style="padding: 0.75rem 2rem; border: none; cursor: pointer;">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
            <a href="{{ route('correction') }}" class="btn-secondary" style="padding: 0.75rem 2rem; text-decoration: none; display: inline-block;">
                <i class="fas fa-times"></i> Batal
            </a>
        </div>
    </form>
</div>
@endsection
