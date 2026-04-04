@extends('layouts.app')

@section('title', 'Mata Kuliah Saya')
@section('breadcrumb')
    <span>Operational</span>
    <span class="breadcrumb-sep">/</span>
    <span>Mata Kuliah Saya</span>
@endsection

@section('content')
<div style="max-width: 1200px; margin: 0 auto;">
    <div class="glass-card" style="margin-bottom: 1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; flex-wrap:wrap;">
            <div>
                <div style="font-size:0.75rem; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:0.35rem;">Kelola per semester</div>
                <h3 class="display-font" style="margin:0;">Daftar Mata Kuliah yang Anda Ampu</h3>
                <div style="margin-top:0.4rem; color:#6b7280; font-size:0.88rem;">Pilih kelas dan mata kuliah dari kelompok semester untuk membuka detail atau memulai sesi presensi.</div>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ route('reports.index') }}" class="btn-kinetic" style="text-decoration:none; padding:0.62rem 0.9rem; font-size:0.82rem; background:#0066CC; box-shadow:none;">
                    <i class="fas fa-file-invoice"></i> Laporan
                </a>
            </div>
        </div>
    </div>

    @forelse ($groupedSchedules as $group)
        <div class="glass-card" style="margin-bottom: 1rem;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                <div>
                    <h4 class="display-font" style="margin:0; font-size:1.05rem;">{{ $group['semester'] }}</h4>
                    <div style="font-size:0.85rem; color:#6b7280;">{{ number_format($group['total']) }} sesi jadwal terdaftar</div>
                </div>
                <div style="font-size:0.75rem; color:#6b7280; text-transform:uppercase; letter-spacing:0.08em; font-weight:700;">Semester</div>
            </div>

            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:0.9rem;">
                @foreach ($group['items'] as $jadwal)
                    <div style="border:1px solid #e5e7eb; border-radius:16px; padding:1rem; background:#fff; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                        <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; margin-bottom:0.75rem;">
                            <div>
                                <div style="font-size:0.72rem; font-weight:700; color:#0066CC; text-transform:uppercase; letter-spacing:0.08em;">{{ $jadwal->hari }}</div>
                                <div style="font-size:1rem; font-weight:800; color:var(--primary-dark); margin-top:0.25rem;">{{ $jadwal->mata_kuliah?->nama_mk ?? '-' }}</div>
                                <div style="font-size:0.85rem; color:#6b7280; margin-top:0.15rem;">{{ $jadwal->mata_kuliah?->kode_mk ?? '-' }} · {{ $jadwal->kelas?->nama_kelas ?? '-' }}</div>
                            </div>
                            <div style="text-align:right; white-space:nowrap;">
                                <div style="font-size:0.72rem; color:#6b7280; text-transform:uppercase; font-weight:700;">Waktu</div>
                                <div style="font-size:0.95rem; font-weight:700;">{{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}</div>
                            </div>
                        </div>

                        @php
                            // Check if this specific jadwal is the active session
                            $isActiveSession = $activeSession
                                && (
                                    // Primary: match by jadwal_id if available
                                    (($activeSession['jadwal_id'] ?? null) && (int) ($activeSession['jadwal_id'] ?? 0) === (int) $jadwal->id)
                                    // Fallback: match by mata_kuliah_id and kelas_id for legacy sessions
                                    || (
                                        !($activeSession['jadwal_id'] ?? null)
                                        && ($activeSession['mata_kuliah_id'] ?? null) == $jadwal->mata_kuliah_id
                                        && ($activeSession['kelas_id'] ?? null) == $jadwal->kelas_id
                                    )
                                );

                            $now = now();
                            $jamMulai = \Carbon\Carbon::parse($jadwal->jam_mulai);
                            $jamSelesai = \Carbon\Carbon::parse($jadwal->jam_selesai);
                            $isWithinScheduleTime = $now->gte($jamMulai) && $now->lte($jamSelesai);
                            $isToday = strtolower($now->format('l')) === strtolower($jadwal->hari)
                                || $now->dayOfWeekIso === match($jadwal->hari) {
                                    'Senin', 'Monday' => 1,
                                    'Selasa', 'Tuesday' => 2,
                                    'Rabu', 'Wednesday' => 3,
                                    'Kamis', 'Thursday' => 4,
                                    'Jumat', 'Friday' => 5,
                                    'Sabtu', 'Saturday' => 6,
                                    'Minggu', 'Sunday' => 7,
                                    default => 0,
                                };
                            $shouldAutoOpen = $isToday && $isWithinScheduleTime && ! $isActiveSession;
                        @endphp

                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.9rem;">
                            <a href="{{ route('dosen-schedule.detail', ['date' => $todayDate, 'mata_kuliah_id' => $jadwal->mata_kuliah_id, 'kelas_id' => $jadwal->kelas_id]) }}" class="btn-kinetic" style="text-decoration:none; padding:0.55rem 0.8rem; font-size:0.8rem; box-shadow:none;">
                                <i class="fas fa-list-check"></i> Detail Hari Ini
                            </a>

                            @if ($isActiveSession)
                                <form action="{{ route('dosen-schedule.stop') }}" method="POST" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-kinetic" style="padding:0.55rem 0.8rem; font-size:0.8rem; background:#BA1A1A; color:#fff; box-shadow:none; border:none; cursor:pointer;">
                                        <i class="fas fa-stop-circle"></i> Tutup Sesi
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('dosen-schedule.start') }}" method="POST" style="margin:0;">
                                    @csrf
                                    <input type="hidden" name="mata_kuliah_id" value="{{ $jadwal->mata_kuliah_id }}">
                                    <input type="hidden" name="kelas_id" value="{{ $jadwal->kelas_id }}">
                                    <button type="submit" class="btn-kinetic" style="padding:0.55rem 0.8rem; font-size:0.8rem; background:{{ $shouldAutoOpen ? '#F59E0B' : '#F1F5F9' }}; color:{{ $shouldAutoOpen ? '#fff' : 'var(--primary-dark)' }}; box-shadow:none; border:none; cursor:pointer;">
                                        <i class="fas fa-bolt"></i> Mulai Sesi
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="glass-card" style="text-align:center; padding:3rem 2rem;">
            <div style="font-size:3rem; color:#d1d5db; margin-bottom:1rem;"><i class="fas fa-layer-group"></i></div>
            <h3 class="display-font" style="margin-bottom:0.5rem;">Belum ada mata kuliah yang ditetapkan</h3>
            <p style="color:#6b7280; margin:0 auto 1.5rem; max-width:520px;">Jika Anda seorang dosen, pastikan jadwal perkuliahan sudah diberi dosen pengampu dan semester akademiknya sudah diisi.</p>
            <a href="{{ route('dashboard') }}" class="btn-kinetic" style="text-decoration:none; padding:0.8rem 1rem; font-size:0.85rem; box-shadow:none;">Kembali ke Dashboard</a>
        </div>
    @endforelse
</div>
@endsection