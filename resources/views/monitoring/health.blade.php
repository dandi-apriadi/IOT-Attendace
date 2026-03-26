@extends('layouts.app')

@section('content')
<div class="stats-grid">
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">ONLINE DEVICES</div>
        <div style="font-size: 2.5rem; font-weight: 800; color: #1DB173;">{{ $onlineDevices }} <span style="font-size: 1rem; opacity: 0.5;">/ {{ $totalDevices }}</span></div>
        <div style="font-size: 0.8rem; color: #6b7280; margin-top: 0.5rem;">Real-time dari tabel devices</div>
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
</div>

<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Hardware Inventory & Status</h3>
    <table>
        <thead>
            <tr>
                <th>Device ID</th>
                <th>Nama Device</th>
                <th>Status</th>
                <th>Aktif</th>
                <th>Last Seen</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($devices as $device)
                <tr>
                    <td style="font-family: monospace; font-weight: 700;">{{ $device->device_id }}</td>
                    <td>{{ $device->name }}</td>
                    <td><span class="status-pill" style="background: {{ $device->is_active ? '#E6F6EC' : '#FADBD8' }}; color: {{ $device->is_active ? '#1DB173' : '#BA1A1A' }};">
                        {{ $device->is_active ? 'Online' : 'Offline' }}
                    </span></td>
                    <td>
                        <input type="checkbox" {{ $device->is_active ? 'checked' : '' }} disabled style="cursor: not-allowed;">
                    </td>
                    <td style="font-size: 0.85rem; color: #6b7280;">{{ $device->last_seen_at?->diffForHumans() ?? '-' }}</td>
                    <td>
                        <button class="btn-kinetic" style="padding: 0.5rem; background: {{ $device->is_active ? '#F1F3F5' : '#BA1A1A' }}; color: {{ $device->is_active ? '#000' : '#fff' }};">
                            <i class="fas {{ $device->is_active ? 'fa-sync' : 'fa-power-off' }}"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #6b7280; padding: 2rem;">Belum ada device yang terdaftar</td>
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
            </div>
        @empty
            <div style="color: #abb2bf;">Belum ada event audit di database.</div>
        @endforelse
    </div>
</div>
@endsection
