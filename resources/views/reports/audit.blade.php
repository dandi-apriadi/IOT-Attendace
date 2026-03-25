@extends('layouts.app')

@section('content')
<div class="glass-card">
    <h3 class="display-font" style="margin-bottom: 2rem;">Audit Log Events</h3>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: #E6F6EC; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #1DB173; font-weight: 700;">Total Events</div>
            <div style="font-size: 2rem; font-weight: 800; color: #1DB173;">{{ count($logs) }}</div>
        </div>
        <div style="background: #E0E7FF; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #0066CC; font-weight: 700;">Auth Events</div>
            <div style="font-size: 2rem; font-weight: 800; color: #0066CC;">{{ collect($logs)->where('type', 'auth')->count() }}</div>
        </div>
        <div style="background: #FEF3C7; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #F59E0B; font-weight: 700;">Warnings</div>
            <div style="font-size: 2rem; font-weight: 800; color: #F59E0B;">{{ collect($logs)->where('type', 'unauthorized')->count() }}</div>
        </div>
        <div style="background: #FADBD8; padding: 1rem; border-radius: 12px; text-align: center;">
            <div style="font-size: 0.8rem; color: #BA1A1A; font-weight: 700;">Errors</div>
            <div style="font-size: 2rem; font-weight: 800; color: #BA1A1A;">{{ collect($logs)->where('type', 'error')->count() }}</div>
        </div>
    </div>

    <div style="background: rgba(0, 30, 64, 0.02); padding: 1.5rem; border-radius: 12px; overflow-y: auto; max-height: 400px;">
        @forelse ($logs as $log)
            <div style="padding: 0.75rem 1rem; margin-bottom: 0.5rem; border-left: 3px solid
                {{ $log['type'] === 'error' ? '#BA1A1A' : ($log['type'] === 'unauthorized' ? '#F59E0B' : ($log['type'] === 'auth' ? '#0066CC' : '#6b7280')) }};
                background: rgba(0,0,0,0.02); border-radius: 8px; font-size: 0.75rem; font-family: monospace; word-wrap: break-word;">
                <span style="color: {{ $log['type'] === 'error' ? '#BA1A1A' : ($log['type'] === 'unauthorized' ? '#F59E0B' : ($log['type'] === 'auth' ? '#0066CC' : '#6b7280')) }}; font-weight: 700;">
                    [{{ strtoupper($log['type']) }}]
                </span>
                {{ $log['message'] }}
            </div>
        @empty
            <div style="text-align: center; padding: 2rem; color: #6b7280;">Belum ada log events</div>
        @endforelse
    </div>

    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #6b7280;">
        <div>
            <i class="fas fa-info-circle"></i> Logs diambil dari storage/logs/laravel.log
        </div>
        <button onclick="location.reload()" class="btn-kinetic" style="padding: 0.5rem 1rem; border: none; cursor: pointer;">
            <i class="fas fa-refresh"></i> Refresh
        </button>
    </div>
</div>
@endsection
