@extends('layouts.app')

@section('content')
<div class="glass-card">
    @php
        $pendingBadge = \App\Support\StatusBadge::forApproval('pending');
        $approvedBadge = \App\Support\StatusBadge::forApproval('approved');
        $rejectedBadge = \App\Support\StatusBadge::forApproval('rejected');
    @endphp

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
            <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ (int) ($summaryCounts['total'] ?? 0) }}</div>
        </div>
        <div style="background: {{ $pendingBadge['bg'] }}; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: {{ $pendingBadge['text'] }}; font-weight: 700;">{{ $approvalStatusOptions['pending'] ?? 'Pending' }}</div>
            <div style="font-size: 2rem; font-weight: 800; color: {{ $pendingBadge['text'] }};">{{ (int) ($summaryCounts['pending'] ?? 0) }}</div>
        </div>
        <div style="background: {{ $approvedBadge['bg'] }}; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: {{ $approvedBadge['text'] }}; font-weight: 700;">{{ $approvalStatusOptions['approved'] ?? 'Disetujui' }}</div>
            <div style="font-size: 2rem; font-weight: 800; color: {{ $approvedBadge['text'] }};">{{ (int) ($summaryCounts['approved'] ?? 0) }}</div>
        </div>
        <div style="background: {{ $rejectedBadge['bg'] }}; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: {{ $rejectedBadge['text'] }}; font-weight: 700;">{{ $approvalStatusOptions['rejected'] ?? 'Ditolak' }}</div>
            <div style="font-size: 2rem; font-weight: 800; color: {{ $rejectedBadge['text'] }};">{{ (int) ($summaryCounts['rejected'] ?? 0) }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <select onchange="window.location.href='{{ route('correction') }}?status='+this.value" style="padding: 0.5rem 1rem; border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; cursor: pointer;">
            <option value="">Semua Status</option>
            @foreach(($approvalStatusOptions ?? []) as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" {{ ($selectedStatus ?? '') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
            @endforeach
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
                        @php
                            $statusValue = (string) $correction->status;
                            $statusLabel = $approvalStatusOptions[$statusValue] ?? ucfirst($statusValue);
                            $badgeColor = \App\Support\StatusBadge::forApproval($statusValue);
                        @endphp
                        <span style="display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700;
                            background: {{ $badgeColor['bg'] }};
                            color: {{ $badgeColor['text'] }};">
                            {{ $statusLabel }}
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
