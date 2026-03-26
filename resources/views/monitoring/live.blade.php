@extends('layouts.app')

@section('content')
<div class="glass-card">
    @if (session('success'))
        <div style="margin-bottom: 1rem; background: #E6F6EC; color: #1DB173; border: 1px solid #b8e7cd; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.9rem; font-weight: 600;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 class="display-font" style="margin: 0;">Live Attendance Stream</h3>
            @if (isset($activeSession))
                <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: {{ $activeSession['source'] === 'MANUAL' ? '#F59E0B' : '#0066CC' }}; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.65rem; font-weight: 800; letter-spacing: 0.05em;">
                        {{ $activeSession['source'] }}
                    </div>
                    <span style="font-size: 0.85rem; font-weight: 600; color: var(--primary-dark);">
                        {{ $activeSession['mk_name'] }} ({{ $activeSession['mk_kode'] }}) - {{ $activeSession['kelas_name'] }}
                    </span>
                </div>
            @else
                <div style="margin-top: 0.5rem; color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i> Menunggu sesi otomatis atau manual diaktifkan...
                </div>
            @endif
        </div>
        
        @if (isset($activeSession) && $activeSession['source'] === 'MANUAL')
            <form action="{{ route('dosen-session.stop') }}" method="POST" style="margin: 0;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-kinetic" style="background: #BA1A1A; padding: 0.6rem 1.2rem; font-size: 0.8rem;">
                    <i class="fas fa-stop-circle"></i> TUTUP SESI MANUAL
                </button>
            </form>
        @endif
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-bottom: 2rem;">
        <div style="background: #E6F6EC; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700; text-transform: uppercase;">Hari Ini Total</div>
            <div id="today-total" style="font-size: 2.5rem; font-weight: 800; color: #1DB173; margin: 0.5rem 0;">{{ $todayTotal }}</div>
            <div style="font-size: 0.75rem; color: #6b7280;">Tap presensi</div>
        </div>
        <div style="background: #FEF3C7; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 0.8rem; color: #F59E0B; font-weight: 700; text-transform: uppercase;">Jam Ini</div>
            <div id="this-hour-total" style="font-size: 2.5rem; font-weight: 800; color: #F59E0B; margin: 0.5rem 0;">{{ $thisHourTotal }}</div>
            <div style="font-size: 0.75rem; color: #6b7280;">Tap dalam 1 jam terakhir</div>
        </div>
        <div style="background: #F0F5FF; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 0.8rem; color: #0066CC; font-weight: 700; text-transform: uppercase;">Live Status</div>
            <div style="font-size: 2rem; font-weight: 800; color: #0066CC; margin: 0.5rem 0;">
                <span style="display: inline-block; width: 12px; height: 12px; background: #1DB173; border-radius: 50%; margin-right: 0.5rem; animation: pulse 1.5s infinite;"></span>
                ACTIVE
            </div>
            <div style="font-size: 0.75rem; color: #6b7280;">Real-time updates</div>
        </div>
    </div>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>

    <h4 class="display-font" style="margin-bottom: 1rem; font-size: 1.2rem;">Latest Attendance Records</h4>
    
    <table style="width: 100%; font-size: 0.85rem;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Waktu</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Mahasiswa</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">NIM</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Jadwal</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Status</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Aksi</th>
            </tr>
        </thead>
        <tbody id="live-stream-body">
            @forelse ($records as $record)
                <tr style="border-bottom: 1px solid #f3f4f6; animation: slideIn 0.3s ease-out;">
                    <td style="padding: 0.75rem; font-weight: 600; color: #6b7280;">{{ $record['time'] }}</td>
                    <td style="padding: 0.75rem;">{{ $record['name'] }}</td>
                    <td style="padding: 0.75rem; font-family: monospace; color: #0066CC;">{{ $record['nim'] }}</td>
                    <td style="padding: 0.75rem; font-size: 0.8rem;">{{ $record['schedule'] }}</td>
                    <td style="padding: 0.75rem; text-align: center;">
                        @php
                            $statusBadge = \App\Support\StatusBadge::forAbsensi((string) ($record['status'] ?? ''));
                        @endphp
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['text'] }};">
                            {{ $record['status'] }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        @if (!($record['editable'] ?? true))
                            <span style="font-size: 0.75rem; color: #9ca3af; font-weight: 700;">Belum bisa edit</span>
                        @else
                            <button
                               type="button"
                               class="btn-kinetic edit-live-btn"
                               data-id="{{ $record['id'] }}"
                               data-status="{{ $record['status'] }}"
                               data-metode="{{ $record['metode_absensi'] ?? '' }}"
                               data-waktu="{{ substr((string) ($record['waktu_tap'] ?? '00:00:00'), 0, 5) }}"
                               data-name="{{ $record['name'] }}"
                               data-nim="{{ $record['nim'] }}"
                               style="padding: 0.45rem 0.7rem; font-size: 0.75rem; border: none;">
                                Edit
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada data</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <style>
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div style="margin-top: 1rem; font-size: 0.8rem; color: #6b7280; text-align: center;">
        <i class="fas fa-sync-alt"></i> Live update setiap 10 detik | terakhir sinkron: <span id="last-updated-at">{{ $lastUpdatedAt }}</span>
    </div>
</div>

<div id="live-edit-modal" style="display: none; position: fixed; inset: 0; background: rgba(17, 24, 39, 0.55); z-index: 60; align-items: center; justify-content: center; padding: 1rem;">
    <div style="width: 100%; max-width: 540px; background: #fff; border-radius: 14px; padding: 1rem 1rem 1.25rem; box-shadow: 0 25px 55px rgba(0,0,0,0.22);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 0.85rem;">
            <div>
                <div style="font-size:1rem; font-weight:800; color:#111827;">Edit Data Live</div>
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

<script>
    const statusBadgeMap = @json(\App\Support\StatusBadge::absensiMap());
    const selectedDate = @json($selectedDate ?? now()->toDateString());
    const selectedJadwalId = @json($selectedJadwalId ?? null);
    const updateUrlBase = @json(url('/monitoring/live'));
    const modal = document.getElementById('live-edit-modal');
    const modalForm = document.getElementById('live-edit-form');
    const modalStatus = document.getElementById('modal-status');
    const modalMetode = document.getElementById('modal-metode');
    const modalWaktu = document.getElementById('modal-waktu');
    const modalStudent = document.getElementById('modal-student');

    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function statusColors(status) {
        const value = String(status || '').toLowerCase();
        const mapped = statusBadgeMap[value] || statusBadgeMap.default;
        return { bg: mapped.bg, color: mapped.text };
    }

    function openEditModal(payload) {
        modalForm.action = `${updateUrlBase}/${payload.id}`;
        modalStatus.value = payload.status || 'Hadir';
        modalMetode.value = payload.metode || 'RFID';
        modalWaktu.value = payload.waktu || '08:00';
        modalStudent.textContent = `${payload.name || 'N/A'} (${payload.nim || '-'})`;
        modal.style.display = 'flex';
    }

    function closeEditModal() {
        modal.style.display = 'none';
    }

    document.getElementById('modal-close').addEventListener('click', closeEditModal);
    document.getElementById('modal-cancel').addEventListener('click', closeEditModal);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeEditModal();
        }
    });

    document.addEventListener('click', (event) => {
        const btn = event.target.closest('.edit-live-btn');
        if (!btn) {
            return;
        }

        openEditModal({
            id: btn.dataset.id,
            status: btn.dataset.status,
            metode: btn.dataset.metode,
            waktu: btn.dataset.waktu,
            name: btn.dataset.name,
            nim: btn.dataset.nim,
        });
    });

    async function refreshLiveStream() {
        try {
            const query = new URLSearchParams({ date: selectedDate });
            if (selectedJadwalId) {
                query.append('jadwal_id', selectedJadwalId);
            }

            const response = await fetch(`{{ route('monitoring.live.data') }}?${query.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            document.getElementById('today-total').textContent = payload.today_total ?? 0;
            document.getElementById('this-hour-total').textContent = payload.this_hour_total ?? 0;
            document.getElementById('last-updated-at').textContent = payload.last_updated_at ?? '-';

            const tbody = document.getElementById('live-stream-body');
            const rows = (payload.records || []).map((record) => {
                const colors = statusColors(record.status);

                return `
                    <tr style="border-bottom: 1px solid #f3f4f6; animation: slideIn 0.3s ease-out;">
                        <td style="padding: 0.75rem; font-weight: 600; color: #6b7280;">${escapeHtml(record.time ?? '-')}</td>
                        <td style="padding: 0.75rem;">${escapeHtml(record.name ?? 'N/A')}</td>
                        <td style="padding: 0.75rem; font-family: monospace; color: #0066CC;">${escapeHtml(record.nim ?? 'N/A')}</td>
                        <td style="padding: 0.75rem; font-size: 0.8rem;">${escapeHtml(record.schedule ?? 'N/A')}</td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: ${colors.bg}; color: ${colors.color};">
                                ${escapeHtml(record.status ?? '-')}
                            </span>
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            ${record.editable === false
                                ? '<span style="font-size: 0.75rem; color: #9ca3af; font-weight: 700;">Belum bisa edit</span>'
                                : `<button
                                       type="button"
                                       class="btn-kinetic edit-live-btn"
                                       data-id="${escapeHtml(record.id ?? '')}"
                                       data-status="${escapeHtml(record.status ?? '')}"
                                       data-metode="${escapeHtml(record.metode_absensi ?? '')}"
                                       data-waktu="${escapeHtml(String(record.waktu_tap || '00:00').slice(0, 5))}"
                                       data-name="${escapeHtml(record.name ?? 'N/A')}"
                                       data-nim="${escapeHtml(record.nim ?? '-') }"
                                       style="padding: 0.45rem 0.7rem; font-size: 0.75rem; border: none;">
                                        Edit
                                    </button>`}
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = rows.length
                ? rows.join('')
                : '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada data</td></tr>';
        } catch (error) {
            // Keep silent in UI to avoid interrupting monitoring screen.
        }
    }

    setInterval(refreshLiveStream, 10000);
</script>
@endsection
