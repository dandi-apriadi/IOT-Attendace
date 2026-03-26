@extends('layouts.app')

@section('content')
<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Live Attendance Stream</h3>

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
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada data</td>
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

<script>
    const statusBadgeMap = @json(\App\Support\StatusBadge::absensiMap());

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

    async function refreshLiveStream() {
        try {
            const response = await fetch("{{ route('monitoring.live.data') }}", {
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
                    </tr>
                `;
            });

            tbody.innerHTML = rows.length
                ? rows.join('')
                : '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada data</td></tr>';
        } catch (error) {
            // Keep silent in UI to avoid interrupting monitoring screen.
        }
    }

    setInterval(refreshLiveStream, 10000);
</script>
@endsection
