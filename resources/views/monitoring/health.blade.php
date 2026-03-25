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
        <div style="color: #61afef;">[13:22:01] <span style="color: #98c379;">SUCCESS</span>: Payload received from ESP32-R402</div>
        <div style="color: #61afef;">[13:22:05] <span style="color: #e06c75;">ERROR</span>: Authentication Failure (Invalid Token) - READER-L001</div>
        <div style="color: #61afef;">[13:22:15] <span style="color: #d19a66;">WARN</span>: High Latency detected on Segment B - ESP32-R102</div>
        <div style="color: #abb2bf;">[13:22:45] <span style="color: #61afef;">INFO</span>: System audit log rotated.</div>
    </div>
</div>
@endsection
