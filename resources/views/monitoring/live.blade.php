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

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 class="display-font" style="margin: 0;">Live Attendance Stream</h3>
            @if (isset($activeSession))
                <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <div style="background: {{ $activeSession['source'] === 'AUTO' ? '#0066CC' : '#F59E0B' }}; color: white; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.65rem; font-weight: 800; letter-spacing: 0.05em;">
                        {{ $activeSession['source'] === 'AUTO' ? 'AUTO' : 'MANUAL' }}
                    </div>
                    <span style="font-size: 0.9rem; font-weight: 700; color: var(--primary-dark);">
                        <i class="fas fa-book-open" style="color: #0066CC;"></i>
                        {{ $activeSession['mk_name'] }}
                        <span style="color: #6b7280; font-weight: 400;">({{ $activeSession['mk_kode'] }})</span>
                        <span style="color: #6b7280;">·</span>
                        <i class="fas fa-users" style="color: #1DB173;"></i>
                        {{ $activeSession['kelas_name'] }}
                    </span>
                    @if ($activeSession['started_at'])
                        <span style="font-size: 0.75rem; color: #6b7280;">
                            <i class="fas fa-clock"></i> Mulai: {{ \Carbon\Carbon::parse($activeSession['started_at'])->format('H:i') }}
                        </span>
                    @endif
                </div>
            @else
                <div style="margin-top: 0.5rem; color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i> Belum ada sesi presensi yang aktif. Buka sesi dari <a href="{{ route('dosen-courses') }}" style="color: #0066CC; font-weight: 600;">Mata Kuliah Saya</a>.
                </div>
            @endif
        </div>

        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            @if (isset($activeSession))
                <form action="{{ route('dosen-schedule.stop') }}" method="POST" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-kinetic" style="background: #BA1A1A; color: #fff; padding: 0.6rem 1.2rem; font-size: 0.8rem; border: none; cursor: pointer;">
                        <i class="fas fa-stop-circle"></i> Tutup Sesi
                    </button>
                </form>
            @else
                <a href="{{ route('dosen-courses') }}" class="btn-kinetic" style="text-decoration: none; padding: 0.6rem 1.2rem; font-size: 0.8rem; background: #1DB173; color: #fff; box-shadow: none;">
                    <i class="fas fa-play-circle"></i> Buka Sesi
                </a>
            @endif
        </div>
    </div>

    @if (isset($activeSession))
        <div style="margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(0,102,204,0.06), rgba(29,177,115,0.06)); border: 2px solid #0066CC; border-radius: 16px; padding: 1.25rem 1.5rem;">
            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <div style="width: 48px; height: 48px; background: #0066CC; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-chalkboard-teacher" style="font-size: 1.3rem; color: #fff;"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                    <div style="font-size: 0.7rem; font-weight: 700; color: #0066CC; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.2rem;">
                        <span style="display: inline-block; width: 8px; height: 8px; background: #1DB173; border-radius: 50%; margin-right: 0.35rem; animation: pulse 1.5s infinite;"></span>
                        Sesi Aktif — {{ $activeSession['source'] === 'AUTO' ? 'Otomatis' : 'Manual' }}
                    </div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: var(--primary-dark);">
                        {{ $activeSession['mk_name'] }}
                        <span style="color: #6b7280; font-weight: 400; font-size: 0.95rem;">({{ $activeSession['mk_kode'] }})</span>
                    </div>
                    <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.15rem;">
                        <i class="fas fa-users" style="color: #1DB173;"></i> Kelas {{ $activeSession['kelas_name'] }}
                        @if ($activeSession['started_at'])
                            <span style="margin-left: 0.75rem;"><i class="fas fa-clock" style="color: #F59E0B;"></i> Mulai: {{ \Carbon\Carbon::parse($activeSession['started_at'])->format('H:i') }}</span>
                        @endif
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                    <a href="{{ route('dosen-schedule.detail', ['date' => now()->toDateString(), 'mata_kuliah_id' => $activeSession['mata_kuliah_id'], 'kelas_id' => $activeSession['kelas_id']]) }}" class="btn-kinetic" style="text-decoration: none; padding: 0.55rem 0.9rem; font-size: 0.78rem; background: #0066CC; color: #fff; box-shadow: none;">
                        <i class="fas fa-list-check"></i> Detail
                    </a>
                    <form action="{{ route('dosen-schedule.stop') }}" method="POST" style="margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-kinetic" style="background: #BA1A1A; color: #fff; padding: 0.55rem 0.9rem; font-size: 0.78rem; border: none; cursor: pointer;">
                            <i class="fas fa-stop-circle"></i> Tutup Sesi
                        </button>
                    </form>
                </div>
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
        <div style="background: {{ isset($activeSession) ? '#F0F5FF' : '#f8fafc' }}; padding: 1.5rem; border-radius: 12px;">
            <div style="font-size: 0.8rem; color: {{ isset($activeSession) ? '#0066CC' : '#9ca3af' }}; font-weight: 700; text-transform: uppercase;">Live Status</div>
            <div style="font-size: 2rem; font-weight: 800; color: {{ isset($activeSession) ? '#0066CC' : '#9ca3af' }}; margin: 0.5rem 0;">
                @if (isset($activeSession))
                    <span style="display: inline-block; width: 12px; height: 12px; background: #1DB173; border-radius: 50%; margin-right: 0.5rem; animation: pulse 1.5s infinite;"></span>
                    ACTIVE
                @else
                    <span style="display: inline-block; width: 12px; height: 12px; background: #d1d5db; border-radius: 50%; margin-right: 0.5rem;"></span>
                    IDLE
                @endif
            </div>
            <div style="font-size: 0.75rem; color: #6b7280;">{{ isset($activeSession) ? 'Real-time updates' : 'Tidak ada sesi aktif' }}</div>
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
