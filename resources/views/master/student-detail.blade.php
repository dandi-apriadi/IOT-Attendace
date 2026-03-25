@extends('layouts.app')

@section('content')
<div style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem;">
    <!-- Profile Card -->
    <div class="glass-card" style="text-align: center;">
        <img src="https://i.pravatar.cc/120?u=1" style="width: 120px; height: 120px; border-radius: 30px; margin-bottom: 1.5rem;">
        <h2 class="display-font">Dandi Apriadi</h2>
        <p style="color: var(--text-muted); font-size: 0.9rem;">22041010 | IK-2A</p>
        
        <div style="margin: 2rem 0; text-align: left;">
            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem;">INFO IOT</div>
            <div style="background: rgba(0,30,64,0.05); padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                <div style="font-size: 0.7rem; opacity: 0.6;">RFID UID</div>
                <div style="font-family: monospace; font-weight: 700;">83:A1:4B:29</div>
            </div>
            <div style="background: rgba(0,30,64,0.05); padding: 1rem; border-radius: 12px;">
                <div style="font-size: 0.7rem; opacity: 0.6;">Face Pattern ID</div>
                <div style="font-family: monospace; font-weight: 700;">FACE_001_DND</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div style="background: #E6F6EC; padding: 1rem; border-radius: 12px;">
                <div style="font-size: 1.5rem; font-weight: 800; color: #1DB173;">100%</div>
                <div style="font-size: 0.65rem; color: #1DB173; font-weight: 700;">HADIR</div>
            </div>
            <div style="background: #F1F3F5; padding: 1rem; border-radius: 12px;">
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--text-muted);">0</div>
                <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 700;">ALPA</div>
            </div>
        </div>
    </div>

    <!-- Attendance History -->
    <div class="glass-card">
        <h3 class="display-font" style="margin-bottom: 2rem;">Histori Presensi Semester Ini</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Mata Kuliah</th>
                    <th>Metode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>25 Mar 2026</td>
                    <td>08:15:12</td>
                    <td>Pemrograman Web</td>
                    <td>RFID Tap</td>
                    <td><span class="status-pill status-present">Hadir</span></td>
                </tr>
                <tr>
                    <td>24 Mar 2026</td>
                    <td>13:02:45</td>
                    <td>Hardware fundamental</td>
                    <td>Face Rec.</td>
                    <td><span class="status-pill status-present">Hadir</span></td>
                </tr>
                <tr>
                    <td>23 Mar 2026</td>
                    <td>10:05:10</td>
                    <td>Basis Data</td>
                    <td>RFID Tap</td>
                    <td><span class="status-pill status-present">Hadir</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
