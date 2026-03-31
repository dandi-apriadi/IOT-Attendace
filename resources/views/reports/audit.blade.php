@extends('layouts.app')

@section('title', 'Audit Log Sistem')
@section('breadcrumb')
    <span>Admin & Reports</span>
    <span class="breadcrumb-sep">/</span>
    <span>Audit Log</span>
@endsection

@section('content')
<div class="glass-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h3 class="display-font" style="font-size: 1.1rem; color: var(--primary-blue-container);">Riwayat Aktivitas</h3>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #E6F6EC; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700;">Total Events</div>
            <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ (int) ($summary->total_events ?? 0) }}</div>
        </div>
        <div style="background: #E0E7FF; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #0066CC; font-weight: 700;">Auth Events</div>
            <div style="font-size: 2rem; font-weight: 800; color: #0066CC;">{{ (int) ($summary->auth_events ?? 0) }}</div>
        </div>
        <div style="background: #FEF3C7; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #F59E0B; font-weight: 700;">Warnings</div>
            <div style="font-size: 2rem; font-weight: 800; color: #F59E0B;">{{ (int) ($summary->warning_events ?? 0) }}</div>
        </div>
        <div style="background: #FADBD8; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #BA1A1A; font-weight: 700;">Errors</div>
            <div style="font-size: 2rem; font-weight: 800; color: #BA1A1A;">{{ (int) ($summary->error_events ?? 0) }}</div>
        </div>
    </div>

    <div style="background: rgba(0, 30, 64, 0.02); padding: 1.5rem; border-radius: 12px; overflow-y: auto; max-height: 400px;">
        @forelse ($logs as $log)
            @php
                $type = str_contains($log->action, 'failed') ? 'error' : ($log->action === 'login' ? 'auth' : 'event');
                $accent = $type === 'error' ? '#BA1A1A' : ($type === 'auth' ? '#0066CC' : '#6b7280');
            @endphp
            <div style="padding: 0.75rem 1rem; margin-bottom: 0.5rem; border-left: 3px solid
                {{ $accent }};
                background: rgba(0,0,0,0.02); border-radius: 8px; font-size: 0.75rem; font-family: monospace; word-wrap: break-word;">
                <span style="color: {{ $accent }}; font-weight: 700;">
                    [{{ strtoupper($log->action) }}]
                </span>
                {{ $log->description }}
                <div style="font-size: 0.7rem; color: #6b7280; margin-top: 0.35rem;">
                    {{ $log->created_at?->format('d M Y H:i:s') }}
                    @if($log->user)
                        | by {{ $log->user->name }}
                    @endif
                    @if($log->ip_address)
                        | IP {{ $log->ip_address }}
                    @endif
                </div>
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada log events</div>
        @endforelse
    </div>

    <div class="pagination-container">
        {{ $logs->links() }}
    </div>

    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #6b7280;">
        <div>
            <i class="fas fa-info-circle"></i> Logs diambil dari database audit_logs
        </div>
        <button onclick="location.reload()" class="btn-kinetic" style="padding: 0.5rem 1rem; border: none; cursor: pointer;">
            <i class="fas fa-refresh"></i> Refresh
        </button>
    </div>
</div>
@endsection
