@extends('layouts.app')

@section('content')
<div class="stats-grid">
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">ONLINE DEVICES</div>
        <div style="font-size: 2.5rem; font-weight: 800; color: #1DB173;">18 <span style="font-size: 1rem; opacity: 0.5;">/ 20</span></div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">AVG. LATENCY</div>
        <div style="font-size: 2.5rem; font-weight: 800;">45ms</div>
    </div>
    <div class="glass-card">
        <div style="font-size: 0.8rem; color: var(--text-muted);">TODAY'S PAYLOADS</div>
        <div style="font-size: 2.5rem; font-weight: 800;">4,502</div>
    </div>
</div>

<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Hardware Inventory & Status</h3>
    <table>
        <thead>
            <tr>
                <th>Device ID</th>
                <th>Location</th>
                <th>Type</th>
                <th>Firmware</th>
                <th>Status</th>
                <th>Last Ping</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-family: monospace; font-weight: 700;">ESP32-R402</td>
                <td>Gedung Elektro Lt. 4</td>
                <td>RFID + Camera</td>
                <td>v2.1.0</td>
                <td><span class="status-pill status-present">Healthy</span></td>
                <td>2s ago</td>
                <td><button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-sync"></i></button></td>
            </tr>
            <tr>
                <td style="font-family: monospace; font-weight: 700;">ESP32-R102</td>
                <td>Lab. Jaringan Lt. 1</td>
                <td>RFID Reader</td>
                <td>v2.0.5</td>
                <td><span class="status-pill status-late" style="background: #FFF4E6; color: #E6A23C;">Low Signal</span></td>
                <td>15s ago</td>
                <td><button class="btn-kinetic" style="padding: 0.5rem; background: #F1F3F5;"><i class="fas fa-sync"></i></button></td>
            </tr>
            <tr>
                <td style="font-family: monospace; font-weight: 700;">READER-L001</td>
                <td>Lobi Utama</td>
                <td>Barcode Scanner</td>
                <td>v1.8.0</td>
                <td><span class="status-pill status-absent">Offline</span></td>
                <td>1h ago</td>
                <td><button class="btn-kinetic" style="padding: 0.5rem; background: #BA1A1A; color: #fff;"><i class="fas fa-power-off"></i></button></td>
            </tr>
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
