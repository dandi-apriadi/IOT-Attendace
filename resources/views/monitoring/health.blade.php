@extends('layouts.app')

@section('title', 'IoT Status & Health')
@section('breadcrumb')
    <span>Operational</span>
    <span class="breadcrumb-sep">/</span>
    <span>IoT Status</span>
@endsection

@section('content')
<div class="stats-grid">
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">ONLINE DEVICES</div>
        <div style="font-size: 2.5rem; font-weight: 800; color: #1DB173;">{{ $onlineDevices }} <span style="font-size: 1rem; opacity: 0.5;">/ {{ $totalDevices }}</span></div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">Aktif {{ $activeDevices }} · Stale {{ $staleDevices }}</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">AVG. LATENCY</div>
        <div style="font-size: 2.5rem; font-weight: 800;">{{ $avgLatency }}ms</div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">Dari payload terakhir</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">TODAY'S PAYLOADS</div>
        <div style="font-size: 2.5rem; font-weight: 800;">{{ number_format($todaysPayloads) }}</div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">Data absensi hari ini</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">LATENCY TREND (24H)</div>
        <div style="font-size: 1.15rem; font-weight: 700; margin-top: 0.35rem;">AVG {{ number_format($latencyStats24h['avg_ms'] ?? 0, 2) }} ms</div>
        <div style="font-size: 0.9rem; color: #6b7280; margin-top: 0.25rem;">P95 {{ number_format($latencyStats24h['p95_ms'] ?? 0, 2) }} ms</div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.4rem;">Latest {{ number_format($latencyStats24h['latest_ms'] ?? 0, 2) }} ms · Samples {{ number_format($latencyStats24h['sample_count'] ?? 0) }}</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">AUDIT EVENTS (24H)</div>
        <div style="font-size: 1.15rem; font-weight: 700; margin-top: 0.35rem; color: #BA1A1A;">ERROR {{ number_format($eventsSummary24h['errors'] ?? 0) }}</div>
        <div style="font-size: 0.9rem; color: #1DB173; margin-top: 0.25rem;">SUCCESS {{ number_format($eventsSummary24h['success'] ?? 0) }}</div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.4rem;">Total {{ number_format($eventsSummary24h['total'] ?? 0) }} event</div>
    </div>
</div>

<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Hardware Inventory & Status</h3>
    <table>
        <thead>
            <tr>
                <th>Device ID</th>
                <th>Nama Device</th>
                <th>Tipe</th>
                <th>Status</th>
                <th>Last Seen</th>
                <th>Update Time</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($devices as $device)
                <tr id="device-row-{{ $device->id }}">
                    <td style="font-family: monospace; font-weight: 700;">{{ $device->device_id }}</td>
                    <td>{{ $device->name }}</td>
                    <td style="font-size:0.8rem; color:#6b7280;">
                        {{ $device->type === 'zkteco' ? 'ZKTeco' : 'Custom IoT' }}
                        @if ($device->type === 'zkteco' && $device->ip_address)
                            <div style="font-family:monospace; font-size:0.72rem;">{{ $device->ip_address }}:{{ $device->port ?: 4370 }}</div>
                        @endif
                    </td>
                    @php
                        $bg = '#EEF0F3'; $text = '#6b7280';
                        if ($device->computed_status === 'online')    { $bg = '#E6F6EC'; $text = '#1DB173'; }
                        elseif ($device->computed_status === 'stale') { $bg = '#FEF3C7'; $text = '#B45309'; }
                        elseif ($device->computed_status === 'offline'){ $bg = '#FDECEC'; $text = '#BA1A1A'; }
                        elseif ($device->computed_status === 'disabled'){ $bg = '#FADBD8'; $text = '#BA1A1A'; }
                    @endphp
                    <td>
                        <span class="status-pill device-status-pill-{{ $device->id }}" style="background: {{ $bg }}; color: {{ $text }};">
                            {{ $device->computed_status_label }}
                        </span>
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $device->updated_at?->format('d M Y H:i:s') ?? '-' }}</td>
                    <td>
                        @if ($device->type === 'zkteco' && $device->ip_address && $device->is_active)
                            <button
                                class="zk-ping-btn"
                                onclick="pingDevice({{ $device->id }})"
                                style="border:none; background:#eef2ff; color:#4338ca; border-radius:6px; padding:0.3rem 0.6rem; font-size:0.72rem; font-weight:600; cursor:pointer; white-space:nowrap;">
                                <i class="fas fa-circle-dot" id="ping-icon-{{ $device->id }}"></i> Cek
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #6b7280; padding: 2rem;">Belum ada device yang terdaftar</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="glass-card" style="margin-top: 2rem; background: #191C1E; color: #fff;">
    <h3 class="display-font" style="margin-bottom: 1.5rem; color: var(--kinetic-yellow);">Technical API Debug Console</h3>
    <div style="background: #000; padding: 1.5rem; border-radius: 12px; font-family: 'Courier New', Courier, monospace; font-size: 0.85rem; overflow-x: auto;">
        @forelse ($recentEvents as $event)
            @php
                $level = str_contains($event->action, 'failed') ? 'ERROR' : ($event->action === 'login' ? 'SUCCESS' : 'INFO');
                $levelColor = $level === 'ERROR' ? '#e06c75' : ($level === 'SUCCESS' ? '#98c379' : '#61afef');
                $time = $event->created_at?->format('H:i:s') ?? '--:--:--';
            @endphp
            <div style="color: #abb2bf;">
                [{{ $time }}]
                <span style="color: {{ $levelColor }};">{{ $level }}</span>:
                {{ $event->description ?? $event->action }}
                @if ($event->user)
                    <span style="color: #7f848e;">(user: {{ $event->user->name }})</span>
                @endif
            </div>
        @empty
            <div style="color: #abb2bf;">Belum ada event audit di database.</div>
        @endforelse
    </div>
</div>

@endsection

@push('scripts')
<script>
const HEALTH_CSRF = '{{ csrf_token() }}';

const STATUS_STYLE = {
    online:   { bg: '#E6F6EC', text: '#1DB173' },
    offline:  { bg: '#FDECEC', text: '#BA1A1A' },
    stale:    { bg: '#FEF3C7', text: '#B45309' },
    disabled: { bg: '#FADBD8', text: '#BA1A1A' },
    unknown:  { bg: '#EEF0F3', text: '#6b7280' },
};

async function pingDevice(deviceId) {
    const icon = document.getElementById('ping-icon-' + deviceId);
    const pill = document.querySelector('.device-status-pill-' + deviceId);
    if (!icon || !pill) return;

    icon.className = 'fas fa-spinner fa-spin';

    try {
        const res  = await fetch('/monitoring/health/ping/' + deviceId, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': HEALTH_CSRF, 'Accept': 'application/json' },
        });
        const data = await res.json();

        const s = STATUS_STYLE[data.status] || STATUS_STYLE.unknown;
        pill.style.background = s.bg;
        pill.style.color      = s.text;
        pill.textContent      = data.label;
    } catch (e) {
        pill.textContent = 'Error';
    }

    icon.className = 'fas fa-circle-dot';
}
</script>
@endpush
