@extends('layouts.app')

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h3 class="display-font">Laporan Koreksi Data</h3>
            <div style="font-size: 0.85rem; color: #6b7280; margin-top: 0.5rem;">Kelola permintaan koreksi data absensi</div>
        </div>
        <a href="{{ route('correction.create') }}" class="btn-kinetic" style="text-decoration: none; padding: 0.75rem 1.5rem;">+ Buat Permintaan Baru</a>
    </div>

    <!-- Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #E6F6EC; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700;">Total Permintaan</div>
            <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ count($corrections) }}</div>
        </div>
        <div style="background: #FEF3C7; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #F59E0B; font-weight: 700;">Pending</div>
            <div style="font-size: 2rem; font-weight: 800; color: #F59E0B;">{{ collect($corrections)->where('status', 'pending')->count() }}</div>
        </div>
        <div style="background: #E0E7FF; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #0066CC; font-weight: 700;">Disetujui</div>
            <div style="font-size: 2rem; font-weight: 800; color: #0066CC;">{{ collect($corrections)->where('status', 'approved')->count() }}</div>
        </div>
        <div style="background: #FADBD8; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #BA1A1A; font-weight: 700;">Ditolak</div>
            <div style="font-size: 2rem; font-weight: 800; color: #BA1A1A;">{{ collect($corrections)->where('status', 'rejected')->count() }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <select onchange="window.location.href='{{ route('correction') }}?status='+this.value" style="padding: 0.5rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; cursor: pointer;">
            <option value="">Semua Status</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
        </select>
    </div>

    <!-- Corrections Table -->
    <table style="width: 100%; font-size: 0.85rem;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Mahasiswa</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Tanggal</th>
                <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Alasan</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Status</th>
                <th style="text-align: center; padding: 0.75rem; border-bottom: 2px solid #e5e7eb;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($corrections as $correction)
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 0.75rem;">
                        <div style="font-weight: 600;">{{ $correction->mahasiswa?->nama ?? 'N/A' }}</div>
                        <div style="font-size: 0.8rem; color: #6b7280;">{{ $correction->mahasiswa?->nim ?? 'N/A' }}</div>
                    </td>
                    <td style="padding: 0.75rem; font-size: 0.85rem;">{{ $correction->created_at->format('d M Y H:i') }}</td>
                    <td style="padding: 0.75rem;">
                        <div style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $correction->alasan }}</div>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
                            background: {{ $correction->status === 'pending' ? '#FEF3C7' : ($correction->status === 'approved' ? '#E0E7FF' : '#FADBD8') }};
                            color: {{ $correction->status === 'pending' ? '#F59E0B' : ($correction->status === 'approved' ? '#0066CC' : '#BA1A1A') }};">
                            {{ ucfirst(__('correction.status.'.$correction->status)) }}
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <a href="{{ route('correction.edit', $correction->id) }}" class="btn-secondary" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.85rem; display: inline-block;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada permintaan koreksi</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Pagination -->
    <div style="margin-top: 2rem;">
        {{ $corrections->links() }}
    </div>
</div>
@endsection
