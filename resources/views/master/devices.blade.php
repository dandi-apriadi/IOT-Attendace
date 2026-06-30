@extends('layouts.app')

@section('title', 'Manajemen Perangkat')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Perangkat IoT</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Daftar Seluruh Perangkat</h3>
        <span style="font-size:0.85rem; color:#6b7280;">Total {{ number_format($devicesList->total()) }} perangkat</span>
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

    <!-- Autoscan Panel -->
    <div class="glass-card" style="background: #f0f4ff; padding: 1.25rem; margin-bottom: 2rem; border: 1px solid #c7d2fe;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h4 class="display-font" style="font-size: 0.9rem; color: #3730a3; text-transform: uppercase; margin: 0;">
                    <i class="fas fa-radar" style="margin-right:0.4rem;"></i> Scan Perangkat di Jaringan
                </h4>
                <p style="font-size: 0.78rem; color: #6b7280; margin: 0.25rem 0 0;">
                    Memindai seluruh /24 subnet lokal untuk port ZKTeco (4370) dan Custom IoT (80, 8080).
                </p>
            </div>
            <button id="btnScan" onclick="startScan()" class="btn-kinetic" style="background:#4338ca; color:#fff; white-space:nowrap;">
                <i class="fas fa-search" id="scanIcon"></i> <span id="scanLabel">Mulai Scan</span>
            </button>
            @if (config('agent.role') === 'server')
                <button type="button" onclick="zkScanAgent()" class="btn-kinetic" style="background:#e0f2fe; color:#0369a1; white-space:nowrap;">
                    <i class="fas fa-search"></i> Scan dari Agent
                </button>
            @endif
        </div>

        <!-- Scan status / progress -->
        <div id="scanStatus" style="display:none; font-size:0.82rem; color:#4338ca; padding:0.5rem 0; display:flex; align-items:center; gap:0.5rem;">
            <span class="spinner" style="display:inline-block; width:14px; height:14px; border:2px solid #c7d2fe; border-top-color:#4338ca; border-radius:50%; animation:spin 0.7s linear infinite;"></span>
            <span id="scanStatusText">Mendeteksi IP lokal dan memindai subnet...</span>
        </div>

        <!-- Scan results -->
        <div id="scanResults" style="display:none; margin-top:0.75rem;">
            <div id="scanInfo" style="font-size:0.78rem; color:#6b7280; margin-bottom:0.75rem;"></div>
            <div id="scanGrid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap:0.75rem;"></div>
            <div id="scanEmpty" style="display:none; color:#6b7280; font-size:0.85rem; padding:0.5rem 0;">
                Tidak ada perangkat baru ditemukan di jaringan lokal.
            </div>
        </div>
        @if (config('agent.role') === 'server')
            <div id="zk-result-scan" style="display:none; margin-top:0.9rem; font-size:0.75rem; background:#f8fafc; border:1px solid #dbeafe; border-radius:8px; padding:0.75rem; font-family:monospace; white-space:pre-wrap; color:#334155;"></div>
        @endif
    </div>

    <!-- Registered devices grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
        @forelse ($devicesList as $device)
            <div class="glass-card" style="background: #fff; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; border-left: 4px solid {{ $device->is_active ? '#1DB173' : '#BA1A1A' }};">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                        <div>
                            <h4 class="display-font" style="font-size: 1.1rem; margin: 0;">{{ $device->name ?: $device->device_id }}</h4>
                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; font-family: monospace;">{{ $device->device_id }}</div>
                        </div>
                        <span class="status-pill {{ $device->is_active ? 'status-present' : 'status-absent' }}" style="font-size: 0.65rem;">
                            {{ $device->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div style="font-size: 0.8rem; color: #4b5563; margin-top: 1rem; display: flex; flex-direction: column; gap: 0.3rem;">
                        <div>
                            <i class="fas fa-microchip" style="width: 16px; opacity: 0.5;"></i> Tipe:
                            <strong>{{ $device->type === 'zkteco' ? 'ZKTeco' : 'Custom IoT' }}</strong>
                        </div>
                        <div>
                            <i class="fas fa-network-wired" style="width: 16px; opacity: 0.5;"></i> IP: <strong>{{ $device->ip_address ?: '-' }}</strong>
                            @if ($device->type === 'zkteco') <span style="color:#9ca3af;">:{{ $device->port ?: 4370 }}</span> @endif
                        </div>
                        <div><i class="fas fa-clock" style="width: 16px; opacity: 0.5;"></i> Last Seen: {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : 'Never' }}</div>
                    </div>

                    @if ($device->type === 'zkteco' && $device->ip_address)
                        <div style="margin-top: 1rem; border-top: 1px dashed #e5e7eb; padding-top: 0.75rem;">
                            <div style="display:flex; gap:0.4rem; flex-wrap:wrap;">
                                <button type="button" onclick="zkTest({{ $device->id }})" class="zk-btn" style="background:#eef2ff; color:#4338ca;"><i class="fas fa-plug"></i> Test</button>
                                <button type="button" onclick="zkInfo({{ $device->id }})" class="zk-btn" style="background:#eef2ff; color:#4338ca;"><i class="fas fa-circle-info"></i> Info</button>
                                <form action="{{ route('devices.sync-users', $device->id) }}" method="POST" onsubmit="return confirm('Kirim SEMUA mahasiswa ke perangkat ini?');" style="margin:0;" @if (config('agent.role') === 'server') data-zk-async="{{ $device->id }}" @endif>
                                    @csrf
                                    <button type="submit" class="zk-btn" style="background:#ecfdf5; color:#047857;"><i class="fas fa-upload"></i> Sync Users</button>
                                </form>
                                <form action="{{ route('devices.pull-attendance', $device->id) }}" method="POST" style="margin:0;" @if (config('agent.role') === 'server') data-zk-async="{{ $device->id }}" @endif>
                                    @csrf
                                    <button type="submit" class="zk-btn" style="background:#ecfdf5; color:#047857;"><i class="fas fa-download"></i> Pull Absensi</button>
                                </form>
                                <form action="{{ route('devices.pull-biometrics', $device->id) }}" method="POST" onsubmit="return confirm('Tarik data kartu dan sidik jari dari alat untuk mahasiswa yang sudah terdaftar di sistem?');" style="margin:0;" @if (config('agent.role') === 'server') data-zk-async="{{ $device->id }}" @endif>
                                    @csrf
                                    <button type="submit" class="zk-btn" style="background:#e0f2fe; color:#0369a1;"><i class="fas fa-fingerprint"></i> Pull Biometrik</button>
                                </form>
                                <a href="{{ route('devices.users', $device->id) }}" class="zk-btn" style="background:#f1f5f9; color:#334155; text-decoration:none;"><i class="fas fa-users"></i> Lihat Users</a>
                                <form action="{{ route('devices.sync-time', $device->id) }}" method="POST" onsubmit="return confirm('Sinkronkan waktu perangkat dengan server?');" style="margin:0;" @if (config('agent.role') === 'server') data-zk-async="{{ $device->id }}" @endif>
                                    @csrf
                                    <button type="submit" class="zk-btn" style="background:#f1f5f9; color:#334155;"><i class="fas fa-clock"></i> Sync Waktu</button>
                                </form>
                                <form action="{{ route('devices.clear-attendance', $device->id) }}" method="POST" onsubmit="return confirm('PERINGATAN: Kosongkan SEMUA log absensi di perangkat? Pastikan sudah ditarik ke server.');" style="margin:0;" @if (config('agent.role') === 'server') data-zk-async="{{ $device->id }}" @endif>
                                    @csrf
                                    <button type="submit" class="zk-btn" style="background:#fef2f2; color:#b91c1c;"><i class="fas fa-eraser"></i> Clear Log</button>
                                </form>
                            </div>
                            <div id="zk-result-{{ $device->id }}" style="display:none; margin-top:0.6rem; font-size:0.72rem; background:#f8fafc; border:1px solid #e5e7eb; border-radius:6px; padding:0.5rem; font-family:monospace; white-space:pre-wrap; color:#334155;"></div>
                        </div>
                    @endif
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; border-top: 1px solid #f1f3f5; padding-top: 1rem;">
                    <a href="{{ route('devices.edit', $device->id) }}" class="btn-kinetic" style="flex: 1; padding: 0.5rem; font-size: 0.75rem; background: #F1F3F5; color: var(--text-primary); text-decoration: none; text-align: center; box-shadow: none;">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form action="{{ route('devices.destroy', $device->id) }}" method="POST" onsubmit="return confirm('Hapus perangkat ini?');" style="flex: 1; margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-kinetic" style="width: 100%; padding: 0.5rem; font-size: 0.75rem; background: #FDECEC; color: #BA1A1A; box-shadow: none;">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="grid-column:1/-1; color:#6b7280; text-align: center; padding: 2rem;">Belum ada data perangkat.</div>
        @endforelse
    </div>

    <div class="pagination-container" style="margin-top: 1.5rem;">
        {{ $devicesList->links() }}
    </div>
</div>

<!-- Modal Daftarkan Perangkat -->
<div id="modalRegister" style="display:none; position:fixed; inset:0; z-index:1000; background:rgba(0,0,0,0.4); overflow-y:auto; padding:2rem 1rem;">
    <div style="background:#fff; border-radius:12px; max-width:480px; margin:0 auto; padding:1.5rem; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem;">
            <h3 class="display-font" style="font-size:1rem;">Daftarkan Perangkat</h3>
            <button onclick="closeRegisterModal()" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:#6b7280;">&times;</button>
        </div>

        <div style="background:#f8fafc; border-radius:8px; padding:0.75rem; margin-bottom:1rem; font-size:0.82rem; display:flex; gap:0.75rem; flex-wrap:wrap;">
            <div><i class="fas fa-network-wired" style="opacity:0.5; margin-right:4px;"></i> IP: <strong id="regIp">-</strong></div>
            <div><i class="fas fa-plug" style="opacity:0.5; margin-right:4px;"></i> Port: <strong id="regPort">-</strong></div>
            <div><i class="fas fa-microchip" style="opacity:0.5; margin-right:4px;"></i> Tipe: <strong id="regTypeLabel">-</strong></div>
            <div id="regSerialWrap" style="display:none;"><i class="fas fa-barcode" style="opacity:0.5; margin-right:4px;"></i> Serial: <strong id="regSerial">-</strong></div>
        </div>

        <form id="formRegister" action="{{ route('devices.store') }}" method="POST">
            @csrf
            <input type="hidden" name="ip_address" id="regInputIp">
            <input type="hidden" name="port" id="regInputPort">
            <input type="hidden" name="type" id="regInputType">
            <input type="hidden" name="is_active" value="1">

            <div class="form-group" style="margin-bottom:0.75rem;">
                <label style="font-size:0.82rem;">Device ID <span style="color:#ba1a1a;">*</span></label>
                <input name="device_id" id="regInputDeviceId" type="text" class="form-control" placeholder="mis. ZK-01 atau DEV-01" required>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label style="font-size:0.82rem;">Nama Perangkat</label>
                <input name="name" id="regInputName" type="text" class="form-control" placeholder="Opsional, mis. Fingerprint Lobi">
            </div>

            <div id="regTokenWrap" style="display:none; margin-bottom:1rem;">
                <div class="form-group">
                    <label style="font-size:0.82rem;">Token Custom IoT</label>
                    <input name="token_hash" id="regInputToken" type="text" class="form-control" placeholder="Biarkan kosong untuk generate otomatis">
                </div>
            </div>

            <button class="btn-kinetic" type="submit" style="width:100%;"><i class="fas fa-plus"></i> Daftarkan Perangkat</button>
        </form>
    </div>
</div>

<style>
    .zk-btn {
        border: none;
        border-radius: 6px;
        padding: 0.4rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: filter 0.15s;
    }
    .zk-btn:hover { filter: brightness(0.95); }
    .zk-btn:disabled { opacity: 0.6; cursor: progress; }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

@push('scripts')
<script>
const ZK_CSRF = '{{ csrf_token() }}';
const ZK_POLL_INTERVAL_MS = 1500;
const ZK_POLL_MAX_ATTEMPTS = 80;

async function startScan() {
    const btn = document.getElementById('btnScan');
    const icon = document.getElementById('scanIcon');
    const label = document.getElementById('scanLabel');
    const status = document.getElementById('scanStatus');
    const statusTxt = document.getElementById('scanStatusText');
    const results = document.getElementById('scanResults');
    const grid = document.getElementById('scanGrid');
    const empty = document.getElementById('scanEmpty');
    const info = document.getElementById('scanInfo');

    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';
    label.textContent = 'Memindai...';
    status.style.display = 'flex';
    statusTxt.textContent = 'Mendeteksi IP lokal dan memindai subnet (maks ~15 detik)...';
    results.style.display = 'none';
    grid.innerHTML = '';
    empty.style.display = 'none';

    try {
        const res = await fetch('{{ route('devices.scan') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': ZK_CSRF, 'Accept': 'application/json' },
        });
        const data = await zkReadJson(res);

        status.style.display = 'none';
        results.style.display = 'block';

        if (!data.ok) {
            grid.innerHTML = `<div style="color:#b91c1c; font-size:0.85rem;">${escHtml(data.message || 'Gagal scan.')}</div>`;
        } else {
            const all = data.found || [];
            const newOnes = all.filter(d => !d.already_registered);
            info.textContent = `Subnet: ${data.subnet} - ${all.length} perangkat ditemukan, ${newOnes.length} belum terdaftar.`;

            if (all.length === 0) {
                empty.style.display = 'block';
            } else {
                grid.innerHTML = all.map(d => buildDeviceCard(d)).join('');
            }
        }
    } catch (e) {
        status.style.display = 'none';
        results.style.display = 'block';
        grid.innerHTML = `<div style="color:#b91c1c; font-size:0.85rem;">Gagal: ${escHtml(e.message)}</div>`;
    }

    btn.disabled = false;
    icon.className = 'fas fa-search';
    label.textContent = 'Scan Lagi';
}

function buildDeviceCard(d) {
    const typeLabel = d.type === 'zkteco' ? 'ZKTeco' : 'Custom IoT';
    const badgeColor = d.type === 'zkteco' ? '#4338ca' : '#047857';
    const badgeBg = d.type === 'zkteco' ? '#eef2ff' : '#ecfdf5';
    const registered = d.already_registered;
    const deviceLabel = d.label ? `<div style="font-size:0.75rem; color:#6b7280; margin-top:2px;">${escHtml(d.label)}</div>` : '';
    const serialLine = d.serial ? `<div style="font-size:0.75rem; color:#9ca3af;">S/N: ${escHtml(d.serial)}</div>` : '';

    const registerBtn = registered
        ? `<div style="font-size:0.75rem; color:#047857; padding:0.4rem 0; text-align:center;">
               <i class="fas fa-check-circle"></i> Sudah terdaftar
           </div>`
        : `<button
               class="btn-kinetic"
               style="width:100%; padding:0.45rem; font-size:0.75rem; margin-top:0.75rem;"
               onclick='openRegisterModal(${JSON.stringify(d)})'
           >
               <i class="fas fa-plus"></i> Daftarkan
           </button>`;

    return `
        <div style="background:#fff; border:1px solid ${registered ? '#d1fae5' : '#c7d2fe'}; border-radius:10px; padding:0.9rem;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.5rem;">
                <div>
                    <div style="font-size:1rem; font-weight:700; font-family:monospace;">${escHtml(d.ip)}</div>
                    ${deviceLabel}
                </div>
                <span style="background:${badgeBg}; color:${badgeColor}; font-size:0.65rem; font-weight:700; padding:0.2rem 0.5rem; border-radius:99px;">${typeLabel}</span>
            </div>
            <div style="font-size:0.78rem; color:#6b7280;">Port: <strong>${d.port}</strong></div>
            ${serialLine}
            ${registerBtn}
        </div>`;
}

function openRegisterModal(d) {
    document.getElementById('regIp').textContent = d.ip;
    document.getElementById('regPort').textContent = d.port;
    document.getElementById('regTypeLabel').textContent = d.type === 'zkteco' ? 'ZKTeco' : 'Custom IoT';
    document.getElementById('regInputIp').value = d.ip;
    document.getElementById('regInputPort').value = d.port;
    document.getElementById('regInputType').value = d.type;

    const serialWrap = document.getElementById('regSerialWrap');
    if (d.serial) {
        document.getElementById('regSerial').textContent = d.serial;
        serialWrap.style.display = '';
    } else {
        serialWrap.style.display = 'none';
    }

    const suggestId = d.serial ? d.serial.replace(/\s+/g, '-') : 'DEV-' + d.ip.split('.').pop();
    document.getElementById('regInputDeviceId').value = suggestId;
    document.getElementById('regInputName').value = d.label || '';

    const tokenWrap = document.getElementById('regTokenWrap');
    tokenWrap.style.display = d.type === 'custom_iot' ? '' : 'none';

    document.getElementById('modalRegister').style.display = 'block';
}

function closeRegisterModal() {
    document.getElementById('modalRegister').style.display = 'none';
}

document.getElementById('modalRegister').addEventListener('click', function(e) {
    if (e.target === this) closeRegisterModal();
});

function zkShow(id, text, isError) {
    const box = document.getElementById('zk-result-' + id);
    if (!box) return;
    box.style.display = 'block';
    box.style.color = isError ? '#b91c1c' : '#334155';
    box.textContent = text;
}

function zkResultLines(data) {
    const result = data.result || {};
    if (data.type === 'get_info' && result && Object.keys(result).length) {
        return [
            'Versi      : ' + (result.version || '-'),
            'Serial     : ' + (result.serial_number || '-'),
            'Nama Alat  : ' + (result.device_name || '-'),
            'Platform   : ' + (result.platform || '-'),
            'Waktu Alat : ' + (result.device_time || '-'),
        ].join('\n');
    }

    if (data.type === 'scan_devices' && Array.isArray(result.devices)) {
        if (!result.devices.length) {
            return 'Tidak ada perangkat ZKTeco yang ditemukan oleh agent.';
        }

        return result.devices.map((device, index) => {
            return [
                (index + 1) + '. ' + (device.device_name || 'ZKTeco') + ' - ' + (device.ip_address || '-') + ':' + (device.port || 4370),
                '   Serial   : ' + (device.serial_number || '-'),
                '   Platform : ' + (device.platform || '-'),
                '   Waktu    : ' + (device.device_time || '-'),
            ].join('\n');
        }).join('\n\n');
    }

    if (!result || !Object.keys(result).length) {
        return '';
    }

    return JSON.stringify(result, null, 2);
}

function zkStatusText(data) {
    const icon = data.failed ? '❌ ' : (data.done ? '✅ ' : '⏳ ');
    const lines = [icon + (data.message || 'Menunggu hasil agent...')];
    const resultText = zkResultLines(data);

    if (resultText) {
        lines.push('', resultText);
    }

    return lines.join('\n');
}

async function zkReadJson(res) {
    const text = await res.text();
    try {
        return text ? JSON.parse(text) : {};
    } catch (e) {
        throw new Error('Respons server bukan JSON: ' + text.slice(0, 120));
    }
}

async function zkPollCommand(id, statusUrl) {
    for (let attempt = 0; attempt < ZK_POLL_MAX_ATTEMPTS; attempt++) {
        const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
        const data = await zkReadJson(res);

        zkShow(id, zkStatusText(data), data.failed || !data.ok);

        if (data.done || data.failed) {
            return data;
        }

        await new Promise(resolve => setTimeout(resolve, ZK_POLL_INTERVAL_MS));
    }

    zkShow(id, '⏳ Agent belum mengirim hasil. Perintah masih bisa selesai otomatis; cek lagi beberapa saat.', true);
    return null;
}

async function zkQueueRequest(id, url, options = {}) {
    const res = await fetch(url, {
        ...options,
        headers: {
            'X-CSRF-TOKEN': ZK_CSRF,
            'Accept': 'application/json',
            ...(options.headers || {}),
        },
    });
    const data = await zkReadJson(res);

    if (!res.ok || !data.ok) {
        zkShow(id, '❌ ' + (data.message || data.error || 'Request gagal.'), true);
        return data;
    }

    zkShow(id, '⏳ ' + (data.message || 'Perintah dikirim ke agent lokal.'), false);
    if (data.status_url) {
        return zkPollCommand(id, data.status_url);
    }

    zkShow(id, (data.ok ? '✅ ' : '❌ ') + (data.message || ''), !data.ok);
    return data;
}

async function zkTest(id) {
    zkShow(id, 'Mengirim perintah cek koneksi ke agent...', false);
    try {
        await zkQueueRequest(id, '/master/devices/' + id + '/test', { method: 'POST' });
    } catch (e) {
        zkShow(id, '❌ Gagal: ' + e.message, true);
    }
}

async function zkInfo(id) {
    zkShow(id, 'Mengirim perintah ambil info ke agent...', false);
    try {
        const data = await zkQueueRequest(id, '/master/devices/' + id + '/info', { method: 'GET' });
        if (data && data.info && !data.status_url) {
            const i = data.info;
            zkShow(id,
                'Versi      : ' + (i.version || '-') + '\n' +
                'Serial     : ' + (i.serial_number || '-') + '\n' +
                'Nama Alat  : ' + (i.device_name || '-') + '\n' +
                'Platform   : ' + (i.platform || '-') + '\n' +
                'Waktu Alat : ' + (i.device_time || '-'), false);
        }
    } catch (e) {
        zkShow(id, '❌ Gagal: ' + e.message, true);
    }
}

async function zkScanAgent() {
    zkShow('scan', 'Mengirim perintah scan perangkat ke agent...', false);
    try {
        await zkQueueRequest('scan', '{{ route('devices.scan-agent') }}', { method: 'POST' });
    } catch (e) {
        zkShow('scan', '❌ Gagal: ' + e.message, true);
    }
}

document.querySelectorAll('form[data-zk-async]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (event.defaultPrevented) return;

        event.preventDefault();
        const id = form.getAttribute('data-zk-async');
        const button = form.querySelector('button[type="submit"]');

        if (button) button.disabled = true;
        try {
            await zkQueueRequest(id, form.action, { method: form.method || 'POST' });
        } catch (e) {
            zkShow(id, '❌ Gagal: ' + e.message, true);
        } finally {
            if (button) button.disabled = false;
        }
    });
});

function escHtml(str) {
    return String(str).replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
}
</script>
@endpush
@endsection
