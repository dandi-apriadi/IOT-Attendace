@extends('layouts.app')

@section('title', 'Manajemen Kelas')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Kelas</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Seluruh Kelas</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($kelasList->total()) }} kelas</span>
    </div>

    @if (session('success'))
        <div style="margin-bottom: 1.5rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div style="margin-bottom: 1.5rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            {{ session('error') }}
        </div>
    @endif

    <!-- Add Class Form -->
    <div class="glass-card" style="background: #f8fafc; padding: 1.25rem; margin-bottom: 2rem;">
        <h4 class="display-font" style="font-size: 0.9rem; margin-bottom: 1rem; color: var(--text-muted); text-transform: uppercase;">Tambah Kelas Baru</h4>
        <form action="{{ route('kelas.store') }}" method="POST" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            @csrf
            <input name="nama_kelas" type="text" placeholder="Nama Kelas (ex: IK-3B)" class="form-control" style="flex: 1; min-width: 200px;" required>
            <input name="semester_level" type="number" min="1" max="14" placeholder="Semester" class="form-control" style="width: 130px;">
            <select name="next_kelas_id" class="form-control" style="min-width: 220px;">
                <option value="">Kelas berikutnya</option>
                @foreach ($allKelas as $nextKelas)
                    <option value="{{ $nextKelas->id }}">{{ $nextKelas->nama_kelas }}</option>
                @endforeach
            </select>
            <button class="btn-kinetic" type="submit"><i class="fas fa-plus"></i> Simpan</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem;">
        @forelse ($kelasList as $kelas)
            <div class="glass-card" style="background: #fff; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <h4 class="display-font" style="font-size: 1.2rem;">{{ $kelas->nama_kelas }}</h4>
                        <span class="status-pill status-present" style="font-size: 0.65rem;">{{ number_format($kelas->mahasiswa_count) }} Mahasiswa</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom:0.35rem;">Semester: {{ $kelas->semester_level ? 'S' . $kelas->semester_level : '-' }}</p>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Berikutnya: {{ $kelas->nextKelas?->nama_kelas ?? 'Belum diatur' }}</p>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; border-top: 1px solid #f1f3f5; padding-top: 1rem; flex-wrap: wrap;">
                    <button
                        class="btn-kinetic btn-mahasiswa"
                        style="flex: 2; padding: 0.5rem; font-size: 0.75rem; background: #EEF2FF; color: #3730A3; box-shadow: none;"
                        data-kelas-id="{{ $kelas->id }}"
                        data-kelas-nama="{{ $kelas->nama_kelas }}"
                        onclick="openMahasiswaModal(this)"
                    >
                        <i class="fas fa-users"></i> Mahasiswa
                    </button>
                    <a href="{{ route('kelas.edit', $kelas->id) }}" class="btn-kinetic" style="flex: 1; padding: 0.5rem; font-size: 0.75rem; background: #F1F3F5; color: var(--text-primary); text-decoration: none; text-align: center; box-shadow: none;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('kelas.destroy', $kelas->id) }}" method="POST" onsubmit="return confirm('Hapus kelas ini?');" style="flex: 1; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-kinetic" style="width: 100%; padding: 0.5rem; font-size: 0.75rem; background: #FDECEC; color: #BA1A1A; box-shadow: none;">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1; color:#6b7280;">Belum ada data kelas.</div>
        @endforelse
    </div>

    <div class="pagination-container">
        {{ $kelasList->links() }}
    </div>
</div>

<!-- Modal Mahasiswa -->
<div id="modalMahasiswa" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,0.4); overflow-y:auto; padding: 2rem 1rem;">
    <div style="background:#fff; border-radius:12px; max-width:560px; margin:0 auto; padding:1.5rem; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
            <h3 class="display-font" style="font-size:1rem;" id="modalKelasNama">Mahasiswa Kelas</h3>
            <button onclick="closeMahasiswaModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#6b7280;">&times;</button>
        </div>

        <!-- Tambah Mahasiswa Form -->
        <div class="glass-card" style="background:#f8fafc; padding:1rem; margin-bottom:1.25rem;">
            <h4 class="display-font" style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.75rem;">Tambah Mahasiswa Baru</h4>
            <form id="formTambahMahasiswa" method="POST" style="display:flex; flex-direction:column; gap:0.6rem;">
                @csrf
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                    <input id="inputNim" name="nim" type="text" placeholder="NIM" class="form-control" style="flex:1; min-width:120px;" required>
                    <input id="inputNama" name="nama" type="text" placeholder="Nama Lengkap" class="form-control" style="flex:2; min-width:160px;" required>
                </div>
                <div id="formError" style="display:none; color:#BA1A1A; font-size:0.8rem;"></div>
                <button class="btn-kinetic" type="submit" style="align-self:flex-start;"><i class="fas fa-plus"></i> Tambahkan</button>
            </form>
        </div>

        <!-- Daftar Mahasiswa -->
        <div>
            <h4 class="display-font" style="font-size:0.8rem; text-transform:uppercase; color:var(--text-muted); margin-bottom:0.75rem;">
                Daftar Mahasiswa <span id="mahasiswaCount" style="font-weight:400;"></span>
            </h4>
            <div id="mahasiswaList" style="display:flex; flex-direction:column; gap:0.5rem; max-height:300px; overflow-y:auto;">
                <div style="color:#6b7280; font-size:0.85rem;">Memuat data...</div>
            </div>
        </div>
    </div>
</div>

<script>
let currentKelasId = null;

function openMahasiswaModal(btn) {
    currentKelasId = btn.dataset.kelasId;
    document.getElementById('modalKelasNama').textContent = 'Mahasiswa Kelas ' + btn.dataset.kelasNama;
    document.getElementById('formTambahMahasiswa').action = '/master/kelas/' + currentKelasId + '/mahasiswa';
    document.getElementById('inputNim').value = '';
    document.getElementById('inputNama').value = '';
    document.getElementById('formError').style.display = 'none';
    document.getElementById('modalMahasiswa').style.display = 'block';
    loadMahasiswa(currentKelasId);
}

function closeMahasiswaModal() {
    document.getElementById('modalMahasiswa').style.display = 'none';
    currentKelasId = null;
}

function loadMahasiswa(kelasId) {
    const list = document.getElementById('mahasiswaList');
    const count = document.getElementById('mahasiswaCount');
    list.innerHTML = '<div style="color:#6b7280; font-size:0.85rem;">Memuat data...</div>';

    fetch('/master/kelas/' + kelasId + '/mahasiswa', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        count.textContent = '(' + data.mahasiswa.length + ')';
        if (data.mahasiswa.length === 0) {
            list.innerHTML = '<div style="color:#6b7280; font-size:0.85rem;">Belum ada mahasiswa di kelas ini.</div>';
            return;
        }
        list.innerHTML = data.mahasiswa.map(m => `
            <div style="display:flex; justify-content:space-between; align-items:center; padding:0.5rem 0.75rem; background:#f8fafc; border-radius:8px; font-size:0.85rem;">
                <span style="font-weight:600; color:var(--text-primary);">${escHtml(m.nim)}</span>
                <span style="flex:1; margin:0 0.75rem; color:var(--text-primary);">${escHtml(m.nama)}</span>
                <a href="/master/mahasiswa/${m.id}/edit" style="color:#3730A3; font-size:0.75rem; text-decoration:none;"><i class="fas fa-edit"></i></a>
            </div>
        `).join('');
    })
    .catch(() => {
        list.innerHTML = '<div style="color:#BA1A1A; font-size:0.85rem;">Gagal memuat data.</div>';
    });
}

function escHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
}

document.getElementById('formTambahMahasiswa').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const errBox = document.getElementById('formError');
    errBox.style.display = 'none';

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': form.querySelector('[name=_token]').value,
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(new FormData(form)),
    })
    .then(async r => {
        if (r.ok || r.status === 302) {
            // Server redirects on success — refresh page to update counts
            window.location.reload();
            return;
        }
        const json = await r.json().catch(() => null);
        if (json && json.errors) {
            const msgs = Object.values(json.errors).flat().join(' ');
            errBox.textContent = msgs;
            errBox.style.display = 'block';
        } else {
            errBox.textContent = 'Terjadi kesalahan. Coba lagi.';
            errBox.style.display = 'block';
        }
    })
    .catch(() => {
        // Network / redirect — just reload
        window.location.reload();
    });
});

// Close modal when clicking backdrop
document.getElementById('modalMahasiswa').addEventListener('click', function(e) {
    if (e.target === this) closeMahasiswaModal();
});
</script>
@endsection
