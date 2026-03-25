@extends('layouts.app')

@section('content')
<div class="glass-card" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font">Data Master Mahasiswa</h3>
        <button class="btn-kinetic"><i class="fas fa-plus"></i> Tambah Mahasiswa</button>
    </div>
    
    <div style="margin-bottom: 2rem; display: flex; gap: 1rem;">
        <input type="text" style="flex-grow: 1; padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;" placeholder="Cari berdasarkan Nama atau NIM...">
        <select style="padding: 0.75rem; border: none; background: #F1F3F5; border-radius: 8px;">
            <option>Semua Kelas</option>
            <option>IK-2A</option>
            <option>IK-2B</option>
        </select>
    </div>

    <table>
        <thead>
            <tr>
                <th>Foto</th>
                <th>NIM</th>
                <th>Nama Lengkap</th>
                <th>Kelas</th>
                <th>Identitas IoT</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><img src="https://i.pravatar.cc/32?u=1" style="border-radius: 8px;"></td>
                <td style="font-family: monospace;">22041010</td>
                <td style="font-weight: 700;">Dandi Apriadi</td>
                <td>IK-2A</td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <span style="font-size: 0.6rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">RFID</span>
                        <span style="font-size: 0.6rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">FACE</span>
                    </div>
                </td>
                <td>
                    <a href="{{ route('student-detail', ['id' => '22041010']) }}" class="btn-kinetic" style="padding: 0.5rem; font-size: 0.8rem; text-decoration: none;"><i class="fas fa-eye"></i></a>
                    <button class="btn-kinetic" style="padding: 0.5rem; font-size: 0.8rem; background: #F1F3F5;"><i class="fas fa-fingerprint"></i></button>
                </td>
            </tr>
            <tr>
                <td><img src="https://i.pravatar.cc/32?u=2" style="border-radius: 8px;"></td>
                <td style="font-family: monospace;">22041011</td>
                <td style="font-weight: 700;">Aisyah Putri</td>
                <td>IK-2B</td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <span style="font-size: 0.6rem; background: #E6F6EC; color: #1DB173; padding: 2px 6px; border-radius: 4px; font-weight: 700;">RFID</span>
                    </div>
                </td>
                <td>
                    <a href="#" class="btn-kinetic" style="padding: 0.5rem; font-size: 0.8rem; text-decoration: none;"><i class="fas fa-eye"></i></a>
                    <button class="btn-kinetic" style="padding: 0.5rem; font-size: 0.8rem; background: #F1F3F5;"><i class="fas fa-fingerprint"></i></button>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="glass-card" style="background: var(--primary-blue-container); color: #fff;">
    <h3 class="display-font" style="margin-bottom: 1rem;">Registrasi Cepat Hardware</h3>
    <p style="font-size: 0.9rem; opacity: 0.7; margin-bottom: 1.5rem;">Gunakan perintah API untuk mendaftarkan UID RFID mahasiswa langsung dari perangkat ESP32 di lab.</p>
    <div style="display: flex; align-items: center; gap: 1rem;">
        <code style="background: rgba(255, 255, 255, 0.1); padding: 0.75rem 1.5rem; border-radius: 8px; flex-grow: 1;">POST /api/register-tag { "nim": "...", "uid": "..." }</code>
        <button class="btn-kinetic" style="white-space: nowrap;">KLIK UNTUK COPY</button>
    </div>
</div>
@endsection
