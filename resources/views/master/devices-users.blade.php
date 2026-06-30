@extends('layouts.app')

@section('title', 'User Perangkat')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('devices.index') }}" style="color: inherit; text-decoration: none;">Perangkat IoT</a>
    <span class="breadcrumb-sep">/</span>
    <span>User Perangkat</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <div>
            <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container); margin:0;">
                User di {{ $device->name ?: $device->device_id }}
            </h3>
            <div style="font-size: 0.78rem; color: #6b7280; margin-top: 2px;">
                {{ $device->ip_address }}:{{ $device->port ?: 4370 }}
            </div>
        </div>
        <div style="display:flex; gap:0.5rem; align-items:center;">
            <button id="btnRefresh" onclick="loadUsers()" class="btn-kinetic"
                style="background:#eef2ff; color:#4338ca; box-shadow:none; font-size:0.8rem;">
                <i class="fas fa-rotate-right" id="refreshIcon"></i> Refresh
            </button>
            <a href="{{ route('devices.index') }}" class="btn-kinetic"
                style="background:#F1F3F5; color:var(--text-primary); text-decoration:none; box-shadow:none; font-size:0.8rem;">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if (session('success'))
        <div style="margin-bottom: 1rem; background: #e6f6ec; color: #1d6f42; padding: 0.75rem 1rem; border-radius: 8px;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div style="margin-bottom: 1rem; background: #fdecec; color: #ba1a1a; padding: 0.75rem 1rem; border-radius: 8px;">
            <ul style="margin:0; padding-left:1.2rem;">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- Import form (hidden until data loaded) -->
    <div id="importBar" style="display:none; margin-bottom:1rem;">
        <form action="{{ route('devices.import-users', $device->id) }}" method="POST"
              style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap; margin:0;"
              onsubmit="return confirm('Import user yang belum terdaftar sebagai mahasiswa baru?');">
            @csrf
            <span id="importCount" style="font-size:0.85rem; color:#b45309;"></span>
            <select name="kelas_id" class="form-control" style="min-width:180px; font-size:0.8rem;" required>
                <option value="">-- Pilih Kelas Tujuan --</option>
                @foreach ($kelasList as $k)
                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-kinetic" style="font-size:0.8rem;">
                <i class="fas fa-file-import"></i> Import yang belum terdaftar
            </button>
        </form>
    </div>

    <!-- Loading state -->
    <div id="stateLoading" style="padding:2rem; text-align:center; color:#4338ca;">
        <span style="display:inline-block; width:20px; height:20px; border:2px solid #c7d2fe; border-top-color:#4338ca; border-radius:50%; animation:spin 0.7s linear infinite; vertical-align:middle; margin-right:0.5rem;"></span>
        Menghubungkan ke perangkat...
    </div>

    <!-- Error state -->
    <div id="stateError" style="display:none; background:#fdecec; color:#ba1a1a; padding:1rem 1.25rem; border-radius:8px; display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
        <div>
            <strong>Gagal membaca user dari perangkat:</strong><br>
            <span id="errorMsg" style="font-size:0.85rem;"></span>
        </div>
        <button onclick="loadUsers()" class="btn-kinetic"
            style="background:#ba1a1a; color:#fff; white-space:nowrap; font-size:0.8rem; box-shadow:none; border:none; cursor:pointer;">
            <i class="fas fa-rotate-right"></i> Coba Lagi
        </button>
    </div>

    <!-- Data table -->
    <div id="stateData" style="display:none;">
        <div style="font-size:0.85rem; color:#6b7280; margin-bottom:1rem;">
            Total <strong id="totalCount">0</strong> user di perangkat
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                <thead>
                    <tr style="text-align:left; border-bottom:2px solid #e5e7eb; color:#6b7280;">
                        <th style="padding:0.6rem;">UID</th>
                        <th style="padding:0.6rem;">User ID (NIM)</th>
                        <th style="padding:0.6rem;">Nama di Alat</th>
                        <th style="padding:0.6rem;">Role</th>
                        <th style="padding:0.6rem;">Status</th>
                        <th style="padding:0.6rem; text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>

@push('scripts')
<script>
const DEVICE_ID   = {{ $device->id }};
const DATA_URL    = '{{ route('devices.users-data', $device->id) }}';
const REMOVE_URL  = '{{ route('devices.remove-user', $device->id) }}';
const ZK_CSRF     = '{{ csrf_token() }}';

function show(id) {
    ['stateLoading','stateError','stateData'].forEach(s => {
        document.getElementById(s).style.display = s === id ? (s === 'stateError' ? 'flex' : 'block') : 'none';
    });
}

async function loadUsers() {
    show('stateLoading');
    document.getElementById('importBar').style.display = 'none';

    const btn  = document.getElementById('btnRefresh');
    const icon = document.getElementById('refreshIcon');
    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';

    try {
        const res  = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.ok) {
            document.getElementById('errorMsg').textContent = data.message || 'Terjadi kesalahan.';
            show('stateError');
        } else {
            renderUsers(data.users || []);
            show('stateData');
        }
    } catch (e) {
        document.getElementById('errorMsg').textContent = 'Tidak dapat menghubungi server: ' + e.message;
        show('stateError');
    }

    btn.disabled = false;
    icon.className = 'fas fa-rotate-right';
}

function renderUsers(users) {
    const tbody    = document.getElementById('usersTableBody');
    const unmatched = users.filter(u => !u.matched).length;

    document.getElementById('totalCount').textContent = users.length;

    if (unmatched > 0) {
        document.getElementById('importCount').textContent = unmatched + ' user belum terdaftar';
        document.getElementById('importBar').style.display = 'flex';
    }

    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="padding:2rem; text-align:center; color:#6b7280;">Tidak ada user di perangkat.</td></tr>';
        return;
    }

    tbody.innerHTML = users.map(u => `
        <tr style="border-bottom:1px solid #f1f3f5;">
            <td style="padding:0.6rem; font-family:monospace;">${u.uid}</td>
            <td style="padding:0.6rem; font-family:monospace;">${escHtml(u.userid)}</td>
            <td style="padding:0.6rem;">${escHtml(u.name) || '-'}</td>
            <td style="padding:0.6rem;">${u.role === 14 ? 'Admin' : 'User'}</td>
            <td style="padding:0.6rem;">
                ${u.matched
                    ? `<span class="status-pill status-present" style="font-size:0.65rem;">Terdaftar</span>
                       <div style="font-size:0.7rem; color:#6b7280;">${escHtml(u.mahasiswa_nama || '')}</div>`
                    : `<span class="status-pill status-absent" style="font-size:0.65rem;">Belum terdaftar</span>`
                }
            </td>
            <td style="padding:0.6rem; text-align:right;">
                <button onclick="removeUser(${u.uid}, '${escAttr(u.name)}')"
                    class="btn-kinetic"
                    style="padding:0.35rem 0.6rem; font-size:0.7rem; background:#FDECEC; color:#BA1A1A; box-shadow:none; border:none; cursor:pointer;">
                    <i class="fas fa-trash"></i> Hapus
                </button>
            </td>
        </tr>
    `).join('');
}

async function removeUser(uid, name) {
    if (!confirm('Hapus user uid ' + uid + ' (' + name + ') dari perangkat?')) return;

    try {
        const res = await fetch(REMOVE_URL, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': ZK_CSRF,
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
            },
            body: 'uid=' + encodeURIComponent(uid),
        });

        if (res.ok || res.redirected) {
            loadUsers();
        } else {
            alert('Gagal menghapus user.');
        }
    } catch (e) {
        alert('Gagal: ' + e.message);
    }
}

function escHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
}
function escAttr(str) {
    return String(str ?? '').replace(/'/g, "\\'");
}

// Auto-load on page open
loadUsers();
</script>
@endpush
@endsection
