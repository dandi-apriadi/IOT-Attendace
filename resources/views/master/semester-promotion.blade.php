@extends('layouts.app')

@section('title', 'Review Kenaikan Semester')
@section('breadcrumb')
    <span>Master Data</span>
    <span class="breadcrumb-sep">/</span>
    <span>Kenaikan Semester</span>
@endsection

@section('content')
<div class="glass-card" style="margin-bottom:1.5rem;">
    <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start; flex-wrap:wrap; margin-bottom:1rem;">
        <div>
            <h3 class="display-font" style="font-size:1.1rem; color:var(--primary-blue-container);">Review Kenaikan Semester</h3>
            <p style="font-size:0.84rem; color:#64748b; margin-top:0.35rem;">Preview kandidat sebelum admin menjalankan kenaikan. Mahasiswa nonaktif, cuti, lulus, atau ditahan tidak akan dipromosikan.</p>
        </div>
        <a href="{{ route('kelas') }}" class="btn-kinetic" style="background:#f1f5f9; color:#0f172a; text-decoration:none;"><i class="fas fa-sitemap"></i> Atur Mapping Kelas</a>
    </div>

    @if (session('success'))
        <div style="margin-bottom:1rem; background:#e6f6ec; color:#1d6f42; padding:0.75rem 1rem; border-radius:8px;">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div style="margin-bottom:1rem; background:#fdecec; color:#ba1a1a; padding:0.75rem 1rem; border-radius:8px;">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('semester.promotion') }}" style="display:flex; gap:0.75rem; flex-wrap:wrap; margin-bottom:1rem;">
        <select name="kelas_id" class="form-control" style="min-width:240px;">
            <option value="">Semua kelas</option>
            @foreach ($kelasList as $kelas)
                <option value="{{ $kelas->id }}" {{ (string) $selectedKelasId === (string) $kelas->id ? 'selected' : '' }}>
                    {{ $kelas->nama_kelas }} -> {{ $kelas->nextKelas?->nama_kelas ?? 'belum diatur' }}
                </option>
            @endforeach
        </select>
        <button class="btn-kinetic" type="submit">Preview</button>
    </form>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:0.75rem; margin-bottom:1rem;">
        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:0.8rem;">
            <div style="font-size:0.75rem; color:#166534; font-weight:700;">Siap Naik</div>
            <div style="font-size:1.6rem; font-weight:800; color:#166534;">{{ $result->eligible->count() }}</div>
        </div>
        <div style="background:#fff7ed; border:1px solid #fed7aa; border-radius:8px; padding:0.8rem;">
            <div style="font-size:0.75rem; color:#9a3412; font-weight:700;">Perlu Dicek</div>
            <div style="font-size:1.6rem; font-weight:800; color:#9a3412;">{{ $result->blocked->count() }}</div>
        </div>
    </div>

    <form action="{{ route('semester.promotion.run') }}" method="POST" onsubmit="return confirm('Jalankan kenaikan semester untuk kandidat yang siap? Pastikan daftar sudah dicek.');" style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:end;">
        @csrf
        @if ($selectedKelasId)
            <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">
        @endif
        <div style="flex:1 1 260px;">
            <label style="font-size:0.75rem; color:#64748b; display:block; margin-bottom:0.35rem;">Catatan Histori</label>
            <input name="note" class="form-control" value="{{ old('note', 'Kenaikan semester dari panel admin') }}" maxlength="255">
        </div>
        <button type="submit" class="btn-kinetic" style="background:#16a34a;" {{ $result->eligible->isEmpty() ? 'disabled' : '' }}>
            <i class="fas fa-arrow-up"></i> Jalankan Kenaikan
        </button>
    </form>
</div>

<div class="glass-card" style="margin-bottom:1.5rem;">
    <h4 class="display-font" style="font-size:0.95rem; margin-bottom:1rem;">Kandidat Siap Naik</h4>
    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Kelas Saat Ini</th>
                <th>Kelas Berikutnya</th>
                <th>Semester Baru</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($result->eligible as $item)
                <tr>
                    <td style="font-family:monospace; font-weight:700;">{{ $item['mahasiswa']->nim }}</td>
                    <td>{{ $item['mahasiswa']->nama }}</td>
                    <td>{{ $item['mahasiswa']->kelas?->nama_kelas ?? '-' }}</td>
                    <td>{{ $item['target_kelas']->nama_kelas ?? '-' }}</td>
                    <td>S{{ $item['target_semester_level'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#64748b;">Belum ada kandidat siap naik.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="glass-card">
    <h4 class="display-font" style="font-size:0.95rem; margin-bottom:1rem;">Perlu Dicek Admin</h4>
    <table>
        <thead>
            <tr>
                <th>NIM</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Alasan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($result->blocked as $item)
                <tr>
                    <td style="font-family:monospace; font-weight:700;">{{ $item['mahasiswa']->nim }}</td>
                    <td>{{ $item['mahasiswa']->nama }}</td>
                    <td>{{ $item['mahasiswa']->kelas?->nama_kelas ?? '-' }}</td>
                    <td>{{ $item['reason'] }}</td>
                    <td><a href="{{ route('mahasiswa.edit', $item['mahasiswa']) }}" class="btn-kinetic" style="padding:0.45rem 0.65rem; text-decoration:none;"><i class="fas fa-edit"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#64748b;">Tidak ada kendala.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
