@extends('layouts.app')

@section('title', 'Live Monitoring Kehadiran')
@section('breadcrumb')
    <span>Operational</span>
    <span class="breadcrumb-sep">/</span>
    <span>Live Monitoring</span>
@endsection

@section('content')
<div class="glass-card">
    @if (session('success'))
        <div style="margin-bottom: 1rem; background: #E6F6EC; color: #1DB173; border: 1px solid #b8e7cd; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.9rem; font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    {{-- Header --}}
    @php $hasActiveSessions = !empty($activeSessions); @endphp
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 class="display-font" style="margin: 0;">Live Attendance Stream</h3>
            @if ($hasActiveSessions)
                <div style="margin-top: 0.4rem; font-size: 0.82rem; color: #6b7280;">
                    <span style="display: inline-block; width: 8px; height: 8px; background: #1DB173; border-radius: 50%; margin-right: 0.35rem; animation: pulse 1.5s infinite;"></span>
                    <strong style="color: #1DB173;">{{ count($activeSessions) }} sesi aktif</strong>
                    — {{ collect($activeSessions)->map(fn($s) => $s['mk_kode'] . ' (' . $s['kelas_name'] . ')')->implode(', ') }}
                </div>
            @else
                <div style="margin-top: 0.4rem; color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i> Belum ada sesi aktif. Buka dari <a href="{{ route('dosen-courses') }}" style="color: #0066CC; font-weight: 600;">Mata Kuliah Saya</a>.
                </div>
            @endif
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if ($hasActiveSessions)
                <form action="{{ route('dosen-schedule.stop') }}" method="POST" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-kinetic" style="background: #BA1A1A; color: #fff; padding: 0.6rem 1.2rem; font-size: 0.8rem; border: none; cursor: pointer;">
                        <i class="fas fa-stop-circle"></i> Tutup Semua Sesi
                    </button>
                </form>
            @else
                <a href="{{ route('dosen-courses') }}" class="btn-kinetic" style="text-decoration: none; padding: 0.6rem 1.2rem; font-size: 0.8rem; background: #1DB173; color: #fff; box-shadow: none;">
                    <i class="fas fa-play-circle"></i> Buka Sesi
                </a>
            @endif
        </div>
    </div>

    {{-- Active sessions banner: one card per session --}}
    @if ($hasActiveSessions)
        <div style="margin-bottom: 1.5rem;">
            <div style="font-size: 0.72rem; font-weight: 700; color: #0066CC; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.65rem;">
                <span style="display: inline-block; width: 8px; height: 8px; background: #1DB173; border-radius: 50%; margin-right: 0.35rem; animation: pulse 1.5s infinite;"></span>
                Sesi Presensi Aktif ({{ count($activeSessions) }})
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem;">
                @foreach ($activeSessions as $sess)
                    <div style="background: linear-gradient(135deg, rgba(0,102,204,0.05), rgba(29,177,115,0.05)); border: 1.5px solid #0066CC; border-radius: 14px; padding: 1rem 1.1rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.55rem;">
                            <span style="background: {{ $sess['source'] === 'AUTO' ? '#0066CC' : '#F59E0B' }}; color: #fff; padding: 0.18rem 0.55rem; border-radius: 4px; font-size: 0.6rem; font-weight: 800; letter-spacing: 0.06em; flex-shrink: 0;">
                                {{ $sess['source'] }}
                            </span>
                            <span style="font-size: 0.95rem; font-weight: 800; color: var(--primary-dark); line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $sess['mk_name'] }}">
                                {{ $sess['mk_name'] }}
                            </span>
                        </div>
                        <div style="font-size: 0.78rem; color: #6b7280;">
                            <span style="color: #374151; font-weight: 600;">{{ $sess['mk_kode'] }}</span>
                            &nbsp;·&nbsp;
                            <i class="fas fa-users" style="color: #1DB173;"></i> Kelas {{ $sess['kelas_name'] }}
                            @if ($sess['started_at'])
                                &nbsp;·&nbsp;
                                <i class="fas fa-clock" style="color: #F59E0B;"></i>
                                {{ \Carbon\Carbon::parse($sess['started_at'])->format('H:i') }}
                            @endif
                        </div>
                        <div style="display: flex; gap: 0.4rem; margin-top: 0.15rem;">
                            <a href="{{ route('dosen-schedule.detail', ['date' => now()->toDateString(), 'mata_kuliah_id' => $sess['mata_kuliah_id'], 'kelas_id' => $sess['kelas_id']]) }}"
                                class="btn-kinetic" style="text-decoration: none; padding: 0.38rem 0.7rem; font-size: 0.73rem; background: #0066CC; color: #fff; box-shadow: none; flex: 1; text-align: center;">
                                <i class="fas fa-list-check"></i> Detail
                            </a>
                            <a href="{{ route('monitoring', ['date' => now()->toDateString(), 'jadwal_id' => $sess['jadwal_id']]) }}"
                                class="btn-kinetic" style="text-decoration: none; padding: 0.38rem 0.7rem; font-size: 0.73rem; background: #1DB173; color: #fff; box-shadow: none; flex: 1; text-align: center;">
                                <i class="fas fa-eye"></i> Monitor
                            </a>
                            <form action="{{ route('dosen-schedule.stop') }}" method="POST" style="margin: 0; flex: 1;">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="jadwal_id" value="{{ $sess['jadwal_id'] }}">
                                <button type="submit" class="btn-kinetic" style="width: 100%; background: #fee2e2; color: #BA1A1A; padding: 0.38rem 0.7rem; font-size: 0.73rem; border: none; cursor: pointer;">
                                    <i class="fas fa-stop-circle"></i> Tutup
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div style="margin-bottom: 1.5rem; background: #f8fafc; border: 2px dashed #d1d5db; border-radius: 16px; padding: 1.25rem 1.5rem; text-align: center;">
            <div style="font-size: 2rem; color: #d1d5db; margin-bottom: 0.5rem;"><i class="fas fa-play-circle"></i></div>
            <div style="font-size: 0.95rem; font-weight: 700; color: #6b7280; margin-bottom: 0.25rem;">Belum Ada Sesi Aktif</div>
            <div style="font-size: 0.82rem; color: #9ca3af; margin-bottom: 0.75rem;">Buka sesi presensi untuk mulai menerima data kehadiran dari perangkat IoT.</div>
            <a href="{{ route('dosen-courses') }}" class="btn-kinetic" style="text-decoration: none; padding: 0.6rem 1.2rem; font-size: 0.82rem; background: #1DB173; color: #fff; box-shadow: none;">
                <i class="fas fa-play-circle"></i> Buka Sesi Presensi
            </a>
        </div>
    @endif

    {{-- Stats --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
        <div style="background: #E6F6EC; padding: 1.25rem; border-radius: 12px;">
            <div style="font-size: 0.78rem; color: #1DB173; font-weight: 700; text-transform: uppercase;">Hari Ini Total</div>
            <div id="today-total" style="font-size: 2.2rem; font-weight: 800; color: #1DB173; margin: 0.4rem 0;">{{ $todayTotal }}</div>
            <div style="font-size: 0.72rem; color: #6b7280;">Tap presensi</div>
        </div>
        <div style="background: #FEF3C7; padding: 1.25rem; border-radius: 12px;">
            <div style="font-size: 0.78rem; color: #F59E0B; font-weight: 700; text-transform: uppercase;">Jam Ini</div>
            <div id="this-hour-total" style="font-size: 2.2rem; font-weight: 800; color: #F59E0B; margin: 0.4rem 0;">{{ $thisHourTotal }}</div>
            <div style="font-size: 0.72rem; color: #6b7280;">Tap dalam 1 jam terakhir</div>
        </div>
        <div style="background: {{ $hasActiveSessions ? '#F0F5FF' : '#f8fafc' }}; padding: 1.25rem; border-radius: 12px;">
            <div style="font-size: 0.78rem; color: {{ $hasActiveSessions ? '#0066CC' : '#9ca3af' }}; font-weight: 700; text-transform: uppercase;">Live Status</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: {{ $hasActiveSessions ? '#0066CC' : '#9ca3af' }}; margin: 0.4rem 0;">
                @if ($hasActiveSessions)
                    <span style="display: inline-block; width: 11px; height: 11px; background: #1DB173; border-radius: 50%; margin-right: 0.4rem; animation: pulse 1.5s infinite;"></span>{{ count($activeSessions) }} AKTIF
                @else
                    <span style="display: inline-block; width: 11px; height: 11px; background: #d1d5db; border-radius: 50%; margin-right: 0.4rem;"></span>IDLE
                @endif
            </div>
            <div style="font-size: 0.72rem; color: #6b7280;">{{ $hasActiveSessions ? count($activeSessions) . ' sesi berjalan' : 'Tidak ada sesi aktif' }}</div>
        </div>
    </div>

    {{-- Filter bar --}}
    <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap; margin-bottom: 1.25rem; padding: 1rem 1.1rem; background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px;">
        <i class="fas fa-filter" style="color: #6b7280; font-size: 0.85rem;"></i>

        {{-- Kelas dropdown --}}
        <select id="filter-kelas" onchange="applyKelasFilter(this.value)"
            style="padding: 0.45rem 0.7rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.82rem; background: #fff; cursor: pointer; min-width: 150px;">
            <option value="">Semua Kelas</option>
            @foreach ($kelases as $k)
                <option value="{{ $k['id'] }}" {{ (string) ($selectedKelasId ?? '') === (string) $k['id'] ? 'selected' : '' }}>
                    Kelas {{ $k['name'] }}
                </option>
            @endforeach
        </select>

        {{-- Mata kuliah dropdown --}}
        <select id="filter-jadwal" onchange="applyJadwalFilter(this.value)"
            style="padding: 0.45rem 0.7rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.82rem; background: #fff; cursor: pointer; min-width: 200px;">
            <option value="">Semua Mata Kuliah</option>
            @foreach ($jadwalList as $j)
                <option value="{{ $j['id'] }}" {{ (string) ($selectedJadwalId ?? '') === (string) $j['id'] ? 'selected' : '' }}>
                    {{ $j['name'] }}
                </option>
            @endforeach
        </select>

        {{-- Search --}}
        <div style="position: relative; flex: 1; min-width: 180px;">
            <i class="fas fa-search" style="position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.78rem; pointer-events: none;"></i>
            <input type="text" id="filter-search" placeholder="Cari nama / NIM…" oninput="filterRows()"
                style="width: 100%; padding: 0.45rem 0.7rem 0.45rem 2rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.82rem; box-sizing: border-box;">
        </div>

        @if ($selectedKelasId || $selectedJadwalId)
            <a href="{{ route('monitoring', ['date' => $selectedDate]) }}"
                style="padding: 0.45rem 0.8rem; background: #fee2e2; color: #BA1A1A; border-radius: 8px; font-size: 0.8rem; font-weight: 700; text-decoration: none; white-space: nowrap;">
                <i class="fas fa-times"></i> Reset
            </a>
        @endif

        <span id="filter-count" style="margin-left: auto; font-size: 0.78rem; color: #6b7280; white-space: nowrap;"></span>
    </div>

    {{-- Table --}}
    <div style="overflow-x: auto;">
        <table style="width: 100%; font-size: 0.84rem; border-collapse: collapse;">
            <thead>
                <tr style="background: #f9fafb;">
                    <th style="text-align: left; padding: 0.7rem 0.75rem; border-bottom: 2px solid #e5e7eb; white-space: nowrap;">Waktu</th>
                    <th style="text-align: left; padding: 0.7rem 0.75rem; border-bottom: 2px solid #e5e7eb;">Mahasiswa</th>
                    <th style="text-align: left; padding: 0.7rem 0.75rem; border-bottom: 2px solid #e5e7eb;">NIM</th>
                    <th style="text-align: left; padding: 0.7rem 0.75rem; border-bottom: 2px solid #e5e7eb;">Mata Kuliah</th>
                    <th style="text-align: left; padding: 0.7rem 0.75rem; border-bottom: 2px solid #e5e7eb; white-space: nowrap;">Kelas</th>
                    <th style="text-align: center; padding: 0.7rem 0.75rem; border-bottom: 2px solid #e5e7eb;">Status</th>
                    <th style="text-align: center; padding: 0.7rem 0.75rem; border-bottom: 2px solid #e5e7eb;">Aksi</th>
                </tr>
            </thead>
            <tbody id="live-stream-body">
                @forelse ($records as $record)
                    @php $statusBadge = \App\Support\StatusBadge::forAbsensi((string) ($record['status'] ?? '')); @endphp
                    <tr data-name="{{ strtolower($record['name'] ?? '') }}" data-nim="{{ strtolower($record['nim'] ?? '') }}"
                        style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 0.7rem 0.75rem; font-weight: 600; color: #6b7280; white-space: nowrap;">
                            {{ $record['time'] }}
                        </td>
                        <td style="padding: 0.7rem 0.75rem; font-weight: 600;">{{ $record['name'] }}</td>
                        <td style="padding: 0.7rem 0.75rem; font-family: monospace; color: #0066CC;">{{ $record['nim'] }}</td>
                        <td style="padding: 0.7rem 0.75rem;">
                            <div style="font-weight: 600; line-height: 1.3;">{{ $record['course_name'] ?? 'N/A' }}</div>
                            <div style="font-size: 0.75rem; color: #9ca3af;">{{ $record['course_code'] ?? '' }}</div>
                        </td>
                        <td style="padding: 0.7rem 0.75rem; font-size: 0.82rem; color: #374151; white-space: nowrap;">{{ $record['kelas_name'] ?? 'N/A' }}</td>
                        <td style="padding: 0.7rem 0.75rem; text-align: center;">
                            <span style="display: inline-block; padding: 0.22rem 0.65rem; border-radius: 999px; font-size: 0.73rem; font-weight: 700; background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['text'] }};">
                                {{ $record['status'] }}
                            </span>
                        </td>
                        <td style="padding: 0.7rem 0.75rem; text-align: center;">
                            @if (!($record['editable'] ?? true))
                                <span style="font-size: 0.73rem; color: #9ca3af;">—</span>
                            @else
                                <button type="button" class="btn-kinetic edit-live-btn"
                                    data-id="{{ $record['id'] }}"
                                    data-status="{{ $record['status'] }}"
                                    data-metode="{{ $record['metode_absensi'] ?? '' }}"
                                    data-waktu="{{ substr((string) ($record['waktu_tap'] ?? '00:00:00'), 0, 5) }}"
                                    data-name="{{ $record['name'] }}"
                                    data-nim="{{ $record['nim'] }}"
                                    style="padding: 0.38rem 0.65rem; font-size: 0.73rem; border: none;">
                                    Edit
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr id="empty-row">
                        <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                            Belum ada data. Pilih kelas atau mata kuliah untuk melihat rekap lengkap.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 0.85rem; font-size: 0.78rem; color: #6b7280; text-align: center;">
        <i class="fas fa-sync-alt"></i> Auto-refresh setiap 4 detik &nbsp;|&nbsp; terakhir sinkron: <span id="last-updated-at">{{ $lastUpdatedAt }}</span>
    </div>
</div>

{{-- Edit Modal --}}
<div id="live-edit-modal" style="display: none; position: fixed; inset: 0; background: rgba(17, 24, 39, 0.55); z-index: 60; align-items: center; justify-content: center; padding: 1rem;">
    <div style="width: 100%; max-width: 540px; background: #fff; border-radius: 14px; padding: 1rem 1rem 1.25rem; box-shadow: 0 25px 55px rgba(0,0,0,0.22);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.85rem;">
            <div>
                <div style="font-size:1rem; font-weight:800; color:#111827;">Edit Data Presensi</div>
                <div id="modal-student" style="font-size:0.8rem; color:#6b7280;"></div>
            </div>
            <button type="button" id="modal-close" style="border:none; background:#eef2f7; color:#374151; border-radius:8px; padding:0.35rem 0.55rem; cursor:pointer;">Tutup</button>
        </div>
        <form id="live-edit-form" method="POST" action="{{ route('monitoring.live.update', ['absensi' => 0]) }}" style="display:grid; gap:0.75rem;">
            @csrf
            @method('PUT')
            <input type="hidden" name="return_date" value="{{ $selectedDate ?? now()->toDateString() }}">
            <input type="hidden" name="return_jadwal_id" value="{{ $selectedJadwalId ?? '' }}">
            <div>
                <label style="display:block; margin-bottom:0.3rem; font-weight:700; font-size:0.82rem;">Status</label>
                <select id="modal-status" name="status" required style="width:100%; padding:0.62rem 0.68rem; border:1px solid #e5e7eb; border-radius:10px;">
                    @foreach (array_values(config('attendance.absensi_statuses', [])) as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:0.3rem; font-weight:700; font-size:0.82rem;">Metode Absensi</label>
                <select id="modal-metode" name="metode_absensi" required style="width:100%; padding:0.62rem 0.68rem; border:1px solid #e5e7eb; border-radius:10px;">
                    @foreach (['RFID', 'Fingerprint', 'Face Recognition', 'Barcode'] as $method)
                        <option value="{{ $method }}">{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block; margin-bottom:0.3rem; font-weight:700; font-size:0.82rem;">Waktu Tap</label>
                <input id="modal-waktu" type="time" name="waktu_tap" required style="width:100%; padding:0.62rem 0.68rem; border:1px solid #e5e7eb; border-radius:10px;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.25rem;">
                <button type="button" id="modal-cancel" style="border:1px solid #d1d5db; background:#fff; border-radius:10px; padding:0.52rem 0.82rem; cursor:pointer;">Batal</button>
                <button type="submit" class="btn-kinetic" style="border:none; padding:0.52rem 0.82rem;">Simpan</button>
            </div>
        </form>
    </div>
</div>

<style>
    @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    @keyframes slideIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
    #live-stream-body tr { transition: background 0.15s; }
    #live-stream-body tr:hover { background: #f9fafb; }
    #live-stream-body tr[data-filtered="hidden"] { display: none !important; }
</style>

<script>
    const statusBadgeMap = @json(\App\Support\StatusBadge::absensiMap());
    const selectedDate   = @json($selectedDate ?? now()->toDateString());
    const selectedJadwalId  = @json($selectedJadwalId ?? null);
    const selectedKelasId   = @json($selectedKelasId ?? null);
    const updateUrlBase  = @json(url('/monitoring/live'));
    const routeBase      = @json(url('/monitoring/live'));

    // ---------- Modal ----------
    const modal       = document.getElementById('live-edit-modal');
    const modalForm   = document.getElementById('live-edit-form');
    const modalStatus = document.getElementById('modal-status');
    const modalMetode = document.getElementById('modal-metode');
    const modalWaktu  = document.getElementById('modal-waktu');
    const modalStudent = document.getElementById('modal-student');

    function escapeHtml(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function statusColors(status) {
        const mapped = statusBadgeMap[String(status || '').toLowerCase()] || statusBadgeMap.default;
        return { bg: mapped.bg, color: mapped.text };
    }

    function openEditModal(payload) {
        modalForm.action = `${updateUrlBase}/${payload.id}`;
        modalStatus.value = payload.status || 'Hadir';
        modalMetode.value = payload.metode || 'RFID';
        modalWaktu.value  = payload.waktu  || '08:00';
        modalStudent.textContent = `${payload.name || 'N/A'} (${payload.nim || '-'})`;
        modal.style.display = 'flex';
    }

    function closeEditModal() { modal.style.display = 'none'; }

    document.getElementById('modal-close').addEventListener('click', closeEditModal);
    document.getElementById('modal-cancel').addEventListener('click', closeEditModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeEditModal(); });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.edit-live-btn');
        if (!btn) return;
        openEditModal({
            id: btn.dataset.id, status: btn.dataset.status,
            metode: btn.dataset.metode, waktu: btn.dataset.waktu,
            name: btn.dataset.name, nim: btn.dataset.nim,
        });
    });

    // ---------- Client-side search ----------
    function filterRows() {
        const search = (document.getElementById('filter-search').value || '').toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('#live-stream-body tr[data-name]').forEach(tr => {
            const nameMatch = (tr.dataset.name || '').includes(search);
            const nimMatch  = (tr.dataset.nim || '').includes(search);
            const show = !search || nameMatch || nimMatch;
            tr.dataset.filtered = show ? '' : 'hidden';
            if (show) visible++;
        });
        const countEl = document.getElementById('filter-count');
        if (countEl) {
            const total = document.querySelectorAll('#live-stream-body tr[data-name]').length;
            countEl.textContent = search ? `${visible} dari ${total} ditampilkan` : `${total} record`;
        }
    }

    // ---------- Filter navigation ----------
    function buildFilterUrl(kelasId, jadwalId) {
        const url = new URL(routeBase);
        url.searchParams.set('date', selectedDate);
        if (jadwalId) url.searchParams.set('jadwal_id', jadwalId);
        if (kelasId)  url.searchParams.set('kelas_id', kelasId);
        return url.toString();
    }

    function applyKelasFilter(kelasId) {
        window.location = buildFilterUrl(kelasId || null, null);
    }

    function applyJadwalFilter(jadwalId) {
        // Keep kelas context when narrowing to a specific jadwal.
        const kelasSelect = document.getElementById('filter-kelas');
        const currentKelas = kelasSelect ? kelasSelect.value : (selectedKelasId || null);
        window.location = buildFilterUrl(currentKelas || null, jadwalId || null);
    }

    // ---------- Live AJAX refresh ----------
    let liveStreamInFlight = false;

    function buildRow(record) {
        const colors = statusColors(record.status);
        const courseLine = `<div style="font-weight:600;line-height:1.3;">${escapeHtml(record.course_name ?? 'N/A')}</div>`
            + (record.course_code ? `<div style="font-size:0.75rem;color:#9ca3af;">${escapeHtml(record.course_code)}</div>` : '');

        const actionCell = record.editable === false
            ? '<span style="font-size:0.73rem;color:#9ca3af;">—</span>'
            : `<button type="button" class="btn-kinetic edit-live-btn"
                   data-id="${escapeHtml(record.id)}"
                   data-status="${escapeHtml(record.status)}"
                   data-metode="${escapeHtml(record.metode_absensi ?? '')}"
                   data-waktu="${escapeHtml(String(record.waktu_tap || '00:00').slice(0,5))}"
                   data-name="${escapeHtml(record.name)}"
                   data-nim="${escapeHtml(record.nim)}"
                   style="padding:0.38rem 0.65rem;font-size:0.73rem;border:none;">Edit</button>`;

        return `<tr data-name="${escapeHtml((record.name ?? '').toLowerCase())}"
                    data-nim="${escapeHtml((record.nim ?? '').toLowerCase())}"
                    style="border-bottom:1px solid #f3f4f6;">
            <td style="padding:0.7rem 0.75rem;font-weight:600;color:#6b7280;white-space:nowrap;">${escapeHtml(record.time ?? '-')}</td>
            <td style="padding:0.7rem 0.75rem;font-weight:600;">${escapeHtml(record.name ?? 'N/A')}</td>
            <td style="padding:0.7rem 0.75rem;font-family:monospace;color:#0066CC;">${escapeHtml(record.nim ?? 'N/A')}</td>
            <td style="padding:0.7rem 0.75rem;">${courseLine}</td>
            <td style="padding:0.7rem 0.75rem;font-size:0.82rem;color:#374151;white-space:nowrap;">${escapeHtml(record.kelas_name ?? 'N/A')}</td>
            <td style="padding:0.7rem 0.75rem;text-align:center;">
                <span style="display:inline-block;padding:0.22rem 0.65rem;border-radius:999px;font-size:0.73rem;font-weight:700;background:${colors.bg};color:${colors.color};">
                    ${escapeHtml(record.status ?? '-')}
                </span>
            </td>
            <td style="padding:0.7rem 0.75rem;text-align:center;">${actionCell}</td>
        </tr>`;
    }

    async function refreshLiveStream() {
        if (liveStreamInFlight) return;
        liveStreamInFlight = true;
        try {
            const query = new URLSearchParams({ date: selectedDate });
            if (selectedJadwalId) query.append('jadwal_id', selectedJadwalId);
            if (selectedKelasId)  query.append('kelas_id',  selectedKelasId);

            const response = await fetch(`{{ route('monitoring.live.data') }}?${query.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) return;

            const payload = await response.json();

            document.getElementById('today-total').textContent     = payload.today_total ?? 0;
            document.getElementById('this-hour-total').textContent = payload.this_hour_total ?? 0;
            document.getElementById('last-updated-at').textContent = payload.last_updated_at ?? '-';

            const tbody = document.getElementById('live-stream-body');
            const rows  = (payload.records || []).map(buildRow);
            tbody.innerHTML = rows.length
                ? rows.join('')
                : '<tr id="empty-row"><td colspan="7" style="text-align:center;padding:2rem;color:#6b7280;">Belum ada data.</td></tr>';

            // Re-apply active search filter after DOM refresh.
            filterRows();

            // Update record count badge.
            const countEl = document.getElementById('filter-count');
            const searchEl = document.getElementById('filter-search');
            if (countEl && searchEl && !searchEl.value) {
                countEl.textContent = `${payload.records?.length ?? 0} record`;
            }
        } catch (_) {
            // Keep silent to not interrupt monitoring display.
        } finally {
            liveStreamInFlight = false;
        }
    }

    // Init: count + immediate fetch.
    filterRows();
    refreshLiveStream();
    setInterval(refreshLiveStream, 4000);
</script>
@endsection
