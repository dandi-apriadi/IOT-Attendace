@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 class="display-font">Profil Mahasiswa</h3>
            <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.5rem;">{{ $mahasiswa->kelas?->nama_kelas ?? 'N/A' }} - {{ $mahasiswa->nim }}</div>
        </div>
        <a href="{{ route('mahasiswa') }}" class="btn-secondary" style="text-decoration: none; padding: 0.75rem 1.5rem;">← Kembali</a>
    </div>

    <!-- Student Info Card -->
    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem; margin-bottom: 2rem;">
        <div style="text-align: center;">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($mahasiswa->nama) }}&background=003366&color=fff&size=150" style="width: 150px; height: 150px; border-radius: 15px; margin-bottom: 1rem;">
            <div style="font-weight: 700; font-size: 1.1rem;">{{ $mahasiswa->nama }}</div>
            <div style="font-size: 0.85rem; color: #6b7280; font-family: monospace;">{{ $mahasiswa->nim }}</div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div style="background: #E6F6EC; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700; text-transform: uppercase;">Total Absensi</div>
                <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ $totalAbsensi }}</div>
            </div>
            <div style="background: #E6F6EC; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700; text-transform: uppercase;">Persentase Hadir</div>
                <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ $persentaseHadir }}%</div>
            </div>
            <div style="background: #FEF3C7; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #F59E0B; font-weight: 700; text-transform: uppercase;">Bulan Ini</div>
                <div style="font-size: 1.5rem; font-weight: 800;">{{ $thisMonthHadir }}/{{ $thisMonthAbsensi }}</div>
            </div>
            <div style="background: #FADBD8; padding: 1.5rem; border-radius: 12px;">
                <div style="font-size: 0.8rem; color: #BA1A1A; font-weight: 700; text-transform: uppercase;">Alpa Total</div>
                <div style="font-size: 2rem; font-weight: 800; color: #BA1A1A;">{{ $alpaCount }}</div>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: rgba(29, 177, 115, 0.05); padding: 1rem; border-left: 3px solid #1DB173; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Hadir</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #1DB173;">{{ $hadirCount }}</div>
        </div>
        <div style="background: rgba(255, 152, 0, 0.05); padding: 1rem; border-left: 3px solid #F59E0B; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Sakit/Izin</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #F59E0B;">{{ $sabitIzinCount }}</div>
        </div>
        <div style="background: rgba(186, 26, 26, 0.05); padding: 1rem; border-left: 3px solid #BA1A1A; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Alpa</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #BA1A1A;">{{ $alpaCount }}</div>
        </div>
        <div style="background: rgba(0, 102, 204, 0.05); padding: 1rem; border-left: 3px solid #0066CC; border-radius: 8px;">
            <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase;">Total</div>
            <div style="font-size: 1.5rem; font-weight: 800; color: #0066CC;">{{ $totalAbsensi }}</div>
        </div>
    </div>

    <!-- Attendance History -->
    <h4 class="display-font" style="margin-bottom: 1rem; margin-top: 2rem;">Riwayat Absensi</h4>
    
    <table style="width: 100%; font-size: 0.85rem;">
            <thead>
            <tr>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Tanggal & Waktu</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Mata Kuliah</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Jadwal</th>
               <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($absensiHistory as $record)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.75rem;">{{ $record->created_at->format('d M Y H:i:s') }}</td>
                    <td style="padding: 0.75rem;">
                        <div style="font-weight: 600;">{{ $record->jadwal?->mataKuliah?->nama_mk ?? 'N/A' }}</div>
                        <div style="font-size: 0.8rem; color: #6b7280;">{{ $record->jadwal?->mataKuliah?->kode_mk ?? 'N/A' }}</div>
                    </td>
                    <td style="padding: 0.75rem; font-size: 0.8rem;">{{ $record->jadwal?->hari ?? 'N/A' }} - {{ $record->jadwal?->jam_mulai ?? 'N/A' }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
                            background: {{ $record->status === 'hadir' ? '#E6F6EC' : ($record->status === 'sakit_izin' ? '#FEF3C7' : '#FADBD8') }};
                            color: {{ $record->status === 'hadir' ? '#1DB173' : ($record->status === 'sakit_izin' ? '#F59E0B' : '#BA1A1A') }};">
                            {{ str_replace('_', ' ', ucfirst($record->status)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada riwayat absensi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination -->
    <div style="margin-top: 2rem;">
        {{ $absensiHistory->links() }}
    </div>
</div>
@endsection
