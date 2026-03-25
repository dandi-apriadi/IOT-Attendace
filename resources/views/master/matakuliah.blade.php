@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">Data Mata Kuliah</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($mataKuliahList->total()) }} mata kuliah</span>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Kode MK</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th>Dipakai di Jadwal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mataKuliahList as $mk)
                <tr>
                    <td style="font-family: monospace; font-weight: 700;">{{ $mk->kode_mk }}</td>
                    <td>{{ $mk->nama_mk }}</td>
                    <td>{{ $mk->sks }}</td>
                    <td>{{ number_format($mk->jadwal_count) }} jadwal</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#6b7280;">Belum ada data mata kuliah.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        {{ $mataKuliahList->links() }}
    </div>
</div>
@endsection
