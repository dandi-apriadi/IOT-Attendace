@extends('layouts.app')

@section('title', 'Buka Sesi Presensi')
@section('breadcrumb')
    <span>Operational</span>
    <span class="breadcrumb-sep">/</span>
    <span>Buka Sesi</span>
@endsection

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="glass-card" style="text-align: center; padding: 4rem 2rem;">
        @if (session('error'))
            <div style="margin-bottom: 1rem; background: #FADBD8; color: #BA1A1A; border: 1px solid #efb9b2; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.9rem; font-weight: 600; text-align:left;">
                {{ session('error') }}
            </div>
        @endif

        <div style="font-size: 4rem; color: var(--kinetic-yellow); margin-bottom: 2rem;"><i class="fas fa-play-circle"></i></div>
        <h2 class="display-font" style="font-size: 2rem; margin-bottom: 1rem;">Aktivasi Sesi Presensi</h2>
        <p style="color: var(--text-muted); margin-bottom: 3rem;">Pilih mata kuliah dan kelas untuk mulai menerima data dari terminal IoT.</p>
        
        @if ($activeSession)
            <div style="background: #FFF9DB; border: 1px solid #FAB005; padding: 2rem; border-radius: var(--radius-xl); margin-bottom: 3rem; text-align: left;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; color: #F59E0B; text-transform: uppercase; margin-bottom: 0.5rem;">Sesi Manual Sedang Aktif</div>
                        <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary-dark);">
                            {{ \App\Models\MataKuliah::find($activeSession['mata_kuliah_id'])?->nama_mk ?? 'N/A' }}
                        </div>
                        <div style="font-size: 0.9rem; color: var(--text-muted);">
                            Kelas: {{ \App\Models\Kelas::find($activeSession['kelas_id'])?->nama_kelas ?? 'N/A' }} | 
                            Mulai: {{ \Carbon\Carbon::parse($activeSession['started_at'])->format('H:i') }}
                        </div>
                    </div>
                    <form action="{{ route('dosen-session.stop') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-kinetic" style="background: #BA1A1A; padding: 0.75rem 1.5rem; font-size: 0.9rem;">TUTUP SESI</button>
                    </form>
                </div>
                <div style="display:flex; justify-content:flex-end; margin-top: 1rem;">
                    <a href="{{ route('dosen-session.detail', ['mata_kuliah_id' => $activeSession['mata_kuliah_id'], 'kelas_id' => $activeSession['kelas_id']]) }}" class="btn-kinetic" style="text-decoration:none; padding: 0.65rem 1rem; font-size: 0.82rem;">
                        <i class="fas fa-list-check"></i> DETAIL SESI
                    </a>
                </div>
            </div>
        @endif

        <form action="{{ route('dosen-session.start') }}" method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; text-align: left; margin-bottom: 3rem;">
            @csrf
            <div class="form-group">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-muted);">Mata Kuliah</label>
                <select name="mata_kuliah_id" required style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px; font-weight: 600;">
                    <option value="">-- Pilih Mata Kuliah --</option>
                    @forelse ($mataKuliahOptions as $mk)
                        <option value="{{ $mk['id'] }}">{{ $mk['label'] }}</option>
                    @empty
                        <option disabled>Tidak ada mata kuliah</option>
                    @endforelse
                </select>
                @if ($mataKuliahOptions->isEmpty())
                    <div style="font-size: 0.75rem; color: #BA1A1A; margin-top: 0.5rem;">⚠️ Belum ada data mata kuliah di database</div>
                @endif
            </div>
            <div class="form-group">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text-muted);">Kelas</label>
                <select name="kelas_id" required style="width: 100%; padding: 1rem; border: none; background: #F1F3F5; border-radius: 12px; font-weight: 600;">
                    <option value="">-- Pilih Kelas --</option>
                    @forelse ($kelasOptions as $k)
                        <option value="{{ $k['id'] }}">{{ $k['label'] }}</option>
                    @empty
                        <option disabled>Tidak ada kelas</option>
                    @endforelse
                </select>
                @if ($kelasOptions->isEmpty())
                    <div style="font-size: 0.75rem; color: #BA1A1A; margin-top: 0.5rem;">⚠️ Belum ada data kelas di database</div>
                @endif
            </div>
            
            <div style="grid-column: 1 / -1; margin-top: 1rem;">
                <div style="background: rgba(0, 30, 64, 0.05); padding: 1.5rem; border-radius: var(--radius-xl); margin-bottom: 2rem; text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                        <i class="fas fa-database" style="color: #1DB173;"></i>
                        <span style="font-weight: 700; font-size: 0.9rem;">Data Dari Database</span>
                    </div>
                    <p style="font-size: 0.8rem; opacity: 0.7;">Mata kuliah dan kelas dimuat dari database realtime. Pilih salah satu untuk memulai sesi presensi.</p>
                </div>

                <button type="submit" class="btn-kinetic" style="padding: 1.25rem 3rem; font-size: 1.1rem; width: 100%; border: none; cursor: pointer;">
                    <i class="fas fa-bolt"></i> MULAI SESI SEKARANG
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
