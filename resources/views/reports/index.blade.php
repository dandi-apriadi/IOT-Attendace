@extends('layouts.app')

@section('content')
<div class="glass-card" style="margin-bottom: 2rem;">
    <h3 class="display-font" style="margin-bottom: 1.5rem;">Cetak Laporan Kehadiran</h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="form-group">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">Pilih Kelas</label>
            <select style="width: 100%; padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
                <option>IK-2A</option>
                <option>IK-2B</option>
            </select>
        </div>
        <div class="form-group">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">Mata Kuliah</label>
            <select style="width: 100%; padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
                <option>Semua MK</option>
                <option>Pemrograman Web</option>
            </select>
        </div>
        <div class="form-group">
            <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">Rentang Waktu</label>
            <input type="month" style="width: 100%; padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
        </div>
    </div>
    <div style="display: flex; gap: 1rem;">
        <button class="btn-kinetic"><i class="fas fa-file-pdf"></i> GENERATE PDF</button>
        <button class="btn-kinetic" style="background: #E6F6EC; color: #1DB173;"><i class="fas fa-file-excel"></i> EXCEL EXPORT</button>
    </div>
</div>

<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Statistik Kehadiran Bulanan</h3>
    <table>
        <thead>
            <tr>
                <th>Mahasiswa</th>
                <th>Hadir</th>
                <th>Sakit/Izin</th>
                <th>Alpa</th>
                <th>Persentase</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight: 700;">Dandi Apriadi</td>
                <td>16</td>
                <td>0</td>
                <td>0</td>
                <td><strong style="color: #1DB173;">100%</strong></td>
            </tr>
            <tr>
                <td style="font-weight: 700;">Aisyah Putri</td>
                <td>14</td>
                <td>2</td>
                <td>0</td>
                <td><strong style="color: var(--kinetic-yellow);">87%</strong></td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
