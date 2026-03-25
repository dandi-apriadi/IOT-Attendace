@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">Manajemen Kelas</h3>
        <button class="btn-kinetic"><i class="fas fa-plus"></i> Tambah Kelas</button>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        <div class="glass-card" style="background: #fff; padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4 class="display-font" style="font-size: 1.5rem;">IK-2A</h4>
                <span class="status-pill status-present">32 Mahasiswa</span>
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 1rem 0;">Teknik Informatika - Semester 4</p>
            <div style="display: flex; gap: 0.5rem; margin-top: 2rem;">
                <button class="btn-kinetic" style="flex-grow: 1; font-size: 0.8rem;">Daftar Siswa</button>
                <button class="btn-kinetic" style="background: #F1F3F5;"><i class="fas fa-edit"></i></button>
            </div>
        </div>
        
        <div class="glass-card" style="background: #fff; padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4 class="display-font" style="font-size: 1.5rem;">IK-2B</h4>
                <span class="status-pill status-present">28 Mahasiswa</span>
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 1rem 0;">Teknik Informatika - Semester 4</p>
            <div style="display: flex; gap: 0.5rem; margin-top: 2rem;">
                <button class="btn-kinetic" style="flex-grow: 1; font-size: 0.8rem;">Daftar Siswa</button>
                <button class="btn-kinetic" style="background: #F1F3F5;"><i class="fas fa-edit"></i></button>
            </div>
        </div>

        <div class="glass-card" style="background: #fff; padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h4 class="display-font" style="font-size: 1.5rem;">EL-3A</h4>
                <span class="status-pill status-present">25 Mahasiswa</span>
            </div>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin: 1rem 0;">Teknik Elektro - Semester 6</p>
            <div style="display: flex; gap: 0.5rem; margin-top: 2rem;">
                <button class="btn-kinetic" style="flex-grow: 1; font-size: 0.8rem;">Daftar Siswa</button>
                <button class="btn-kinetic" style="background: #F1F3F5;"><i class="fas fa-edit"></i></button>
            </div>
        </div>
    </div>
</div>
@endsection
