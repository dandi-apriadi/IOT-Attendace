@extends('layouts.app')

@section('content')
<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 1.5rem;">Statistik Kehadiran Bulanan</h3>

    <form method="GET" action="{{ route('reports.index') }}" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Kelas</label>
            <select name="kelas_id" class="form-input">
                <option value="">Semua Kelas</option>
                @foreach ($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" {{ (string) $selectedKelasId === (string) $kelas->id ? 'selected' : '' }}>{{ $kelas->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Mata Kuliah</label>
            <select name="mata_kuliah_id" class="form-input">
                <option value="">Semua Mata Kuliah</option>
                @foreach ($mataKuliahList as $mk)
                    <option value="{{ $mk->id }}" {{ (string) $selectedMataKuliahId === (string) $mk->id ? 'selected' : '' }}>{{ $mk->nama_mk }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display:block; font-size:0.75rem; font-weight:700; margin-bottom:0.4rem;">Bulan</label>
            <input type="month" name="month" class="form-input" value="{{ $selectedMonth }}">
        </div>
        <div style="display:flex; align-items:flex-end;">
            <button class="btn-kinetic" type="submit" style="width:100%;"><i class="fas fa-filter"></i> Terapkan Filter</button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Mahasiswa</th>
                <th>{{ $reportStatusLabels['hadir'] ?? 'Hadir' }}</th>
                <th>{{ $reportStatusLabels['sakit_izin'] ?? 'Sakit/Izin' }}</th>
                <th>{{ $reportStatusLabels['alpa'] ?? 'Alpa' }}</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stats as $row)
                <tr>
                    <td style="font-weight: 700;">{{ $row->nama }}</td>
                    <td>{{ (int) $row->hadir }}</td>
                    <td>{{ (int) $row->sakit_izin }}</td>
                    <td>{{ (int) $row->alpa }}</td>
                    <td>
                        <strong style="color: {{ $row->persentase >= 90 ? '#1DB173' : ($row->persentase >= 80 ? 'var(--kinetic-yellow)' : '#BA1A1A') }};">
                            {{ number_format((float) $row->persentase, 2) }}%
                        </strong>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#6b7280;">Tidak ada data laporan untuk filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 1rem;">
        {{ $stats->links() }}
    </div>
</div>
@endsection
