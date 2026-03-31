@extends('layouts.app')

@section('title', 'Laporan Presensi')
@section('breadcrumb')
    <span>Admin & Reports</span>
    <span class="breadcrumb-sep">/</span>
    <span>Laporan</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Laporan Presensi Mahasiswa</h3>
            <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.25rem;">Rekapitulasi kehadiran mahasiswa berdasarkan filter</div>
        </div>
    </div>

    <!-- Filter Form -->
    <form action="{{ route('reports.index') }}" method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; background: #f9fafb; padding: 1.25rem; border-radius: 12px; border: 1px solid #e5e7eb;">
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

    <div class="pagination-container">
        {{ $stats->links() }}
    </div>
</div>
@endsection
