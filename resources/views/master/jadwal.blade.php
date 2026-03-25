@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">Jadwal Perkuliahan</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($jadwalList->total()) }} jadwal</span>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Hari</th>
                <th>Mata Kuliah</th>
                <th>Kelas</th>
                <th>Dosen Pengampu</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($jadwalList as $jadwal)
                <tr>
                    <td><strong>{{ $jadwal->hari }}</strong></td>
                    <td>{{ $jadwal->mata_kuliah->nama_mk ?? '-' }}</td>
                    <td>{{ $jadwal->kelas->nama_kelas ?? '-' }}</td>
                    <td>{{ $jadwal->dosen->name ?? '-' }}</td>
                    <td>{{ $jadwal->jam_mulai }} - {{ $jadwal->jam_selesai }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; color:#6b7280;">Belum ada data jadwal.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        {{ $jadwalList->links() }}
    </div>
</div>
@endsection
