@extends('layouts.app')

@section('content')
<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 1.5rem;">Pusat Koreksi Kehadiran</h3>
    <p style="color: var(--text-muted); margin-bottom: 2rem;">Gunakan fitur ini jika terjadi kesalahan hardware atau mahasiswa lupa melakukan tap.</p>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
        <div>
            <div class="form-group">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">Cari Mahasiswa</label>
                <input type="text" style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px;" placeholder="Nama atau NIM...">
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">Pilih Tanggal & Jam</label>
                <input type="datetime-local" style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px;">
            </div>
        </div>
        <div>
            <div class="form-group">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">Ubah Status Ke</label>
                <select style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px;">
                    <option>Hadir</option>
                    <option>Sakit</option>
                    <option>Izin</option>
                    <option>Alpa</option>
                </select>
            </div>
            <div class="form-group" style="margin-top: 1.5rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem;">Alasan Koreksi</label>
                <textarea style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px; height: 100px;" placeholder="Contoh: Alat RFID Rusak, Mahasiswa sakit melampirkan surat..."></textarea>
            </div>
        </div>
    </div>
    <button class="btn-kinetic" style="margin-top: 2rem; width: 100%;">SIMPAN PERUBAHAN</button>
</div>
@endsection
