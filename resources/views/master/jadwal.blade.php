@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">Jadwal Perkuliahan</h3>
        <button class="btn-kinetic"><i class="fas fa-calendar-plus"></i> Tambah Jadwal</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Hari</th>
                <th>Mata Kuliah</th>
                <th>Kelas</th>
                <th>Dosen Pengampu</th>
                <th>Waktu</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Senin</strong></td>
                <td>Pemrograman Web Lanjut</td>
                <td>IK-2A</td>
                <td>Dandi Apriadi, M.T</td>
                <td>08:00 - 10:30</td>
                <td>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
            <tr>
                <td><strong>Selasa</strong></td>
                <td>Sistem Tertanam</td>
                <td>IK-2B</td>
                <td>Ir. Aisyah Putri</td>
                <td>13:00 - 15:00</td>
                <td>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
            <tr>
                <td><strong>Rabu</strong></td>
                <td>Basis Data</td>
                <td>IK-2A</td>
                <td>Budi Santoso, S.Kom</td>
                <td>10:00 - 12:00</td>
                <td>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-edit"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
