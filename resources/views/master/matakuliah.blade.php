@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">Data Mata Kuliah</h3>
        <button class="btn-kinetic"><i class="fas fa-plus"></i> Tambah MK</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Kode MK</th>
                <th>Nama Mata Kuliah</th>
                <th>SKS</th>
                <th>Semester</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-family: monospace; font-weight: 700;">MK-001</td>
                <td>Pemrograman Web Lanjut</td>
                <td>3</td>
                <td>4</td>
                <td>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-edit"></i></button>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #FDECEC; color: #BA1A1A;"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <tr>
                <td style="font-family: monospace; font-weight: 700;">MK-002</td>
                <td>IoT & Sistem Tertanam</td>
                <td>4</td>
                <td>4</td>
                <td>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-edit"></i></button>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #FDECEC; color: #BA1A1A;"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <tr>
                <td style="font-family: monospace; font-weight: 700;">MK-003</td>
                <td>Keamanan Jaringan</td>
                <td>2</td>
                <td>5</td>
                <td>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-edit"></i></button>
                    <button class="btn-kinetic" style="padding: 0.5rem; background: #FDECEC; color: #BA1A1A;"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
