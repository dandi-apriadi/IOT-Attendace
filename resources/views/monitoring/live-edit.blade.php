@extends('layouts.app')

@section('content')
<div class="glass-card" style="max-width: 760px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">
        <h3 class="display-font" style="margin: 0;">Edit Data Live Monitoring</h3>
        <a href="{{ route('monitoring', ['date' => $returnDate, 'jadwal_id' => $returnJadwalId ?: null]) }}" class="btn-kinetic" style="text-decoration:none; padding:0.5rem 0.8rem; font-size:0.8rem;">
            Kembali ke Monitoring
        </a>
    </div>

    @if ($errors->any())
        <div style="margin-bottom: 1rem; background: #FADBD8; color: #BA1A1A; border: 1px solid #efb9b2; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.9rem;">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom:1rem; font-size:0.88rem; color:#4b5563;">
        <div><strong>Mahasiswa:</strong> {{ $absensi->mahasiswa?->nama ?? 'N/A' }} ({{ $absensi->mahasiswa?->nim ?? 'N/A' }})</div>
        <div><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($absensi->tanggal)->format('d M Y') }}</div>
        <div><strong>Mata Kuliah:</strong> {{ $absensi->jadwal?->mata_kuliah?->kode_mk ?? 'N/A' }} - {{ $absensi->jadwal?->mata_kuliah?->nama_mk ?? 'N/A' }}</div>
        <div><strong>Kelas:</strong> {{ $absensi->jadwal?->kelas?->nama_kelas ?? 'N/A' }}</div>
    </div>

    <form method="POST" action="{{ route('monitoring.live.update', $absensi->id) }}" style="display:grid; gap:0.9rem;">
        @csrf
        @method('PUT')

        <input type="hidden" name="return_date" value="{{ $returnDate }}">
        <input type="hidden" name="return_jadwal_id" value="{{ $returnJadwalId }}">

        <div>
            <label for="status" style="display:block; font-weight:700; margin-bottom:0.35rem;">Status Kehadiran</label>
            <select id="status" name="status" required style="width:100%; padding:0.7rem 0.75rem; border:1px solid #e5e7eb; border-radius:10px;">
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}" {{ old('status', $absensi->status) === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="metode_absensi" style="display:block; font-weight:700; margin-bottom:0.35rem;">Metode Absensi</label>
            <select id="metode_absensi" name="metode_absensi" required style="width:100%; padding:0.7rem 0.75rem; border:1px solid #e5e7eb; border-radius:10px;">
                @foreach ($methodOptions as $method)
                    <option value="{{ $method }}" {{ old('metode_absensi', $absensi->metode_absensi) === $method ? 'selected' : '' }}>{{ $method }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="waktu_tap" style="display:block; font-weight:700; margin-bottom:0.35rem;">Waktu Tap</label>
            <input
                id="waktu_tap"
                type="time"
                name="waktu_tap"
                value="{{ old('waktu_tap', \Carbon\Carbon::parse((string) $absensi->waktu_tap)->format('H:i')) }}"
                required
                style="width:100%; padding:0.7rem 0.75rem; border:1px solid #e5e7eb; border-radius:10px;"
            />
        </div>

        <div style="display:flex; justify-content:flex-end; gap:0.6rem; margin-top:0.5rem;">
            <a href="{{ route('monitoring', ['date' => $returnDate, 'jadwal_id' => $returnJadwalId ?: null]) }}" style="padding:0.6rem 0.9rem; border:1px solid #d1d5db; border-radius:10px; color:#374151; text-decoration:none;">Batal</a>
            <button type="submit" class="btn-kinetic" style="padding:0.6rem 0.9rem; border:none;">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
