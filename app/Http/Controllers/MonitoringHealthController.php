<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AuditLog;
use App\Models\Device;
use App\Models\PerformanceMetric;
use Illuminate\View\View;

class MonitoringHealthController extends Controller
{
    public function index(): View
    {
        $now = now();
        $today = $now->toDateString();
        $onlineThreshold = $now->copy()->subMinutes(5);

        $devices = Device::query()
            ->orderByDesc('is_active')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(function (Device $device) use ($onlineThreshold): Device {
                $status = 'unknown';
                $statusLabel = 'Unknown';

                if (! $device->is_active) {
                    $status = 'disabled';
                    $statusLabel = 'Disabled';
                } elseif ($device->last_seen_at && $device->last_seen_at->gte($onlineThreshold)) {
                    $status = 'online';
                    $statusLabel = 'Online';
                } elseif ($device->last_seen_at) {
                    $status = 'stale';
                    $statusLabel = 'Stale';
                }

                $device->computed_status = $status;
                $device->computed_status_label = $statusLabel;

                return $device;
            });

        $onlineDevices = $devices->where('computed_status', 'online')->count();
        $activeDevices = $devices->where('is_active', true)->count();
        $staleDevices = $devices->where('computed_status', 'stale')->count();
        $totalDevices = $devices->count();

        $avgLatency = (float) (PerformanceMetric::query()
            ->where('endpoint', 'api.absensi')
            ->whereDate('created_at', $today)
            ->avg('total_duration_ms') ?? 0);

        if ($avgLatency <= 0) {
            $avgLatency = (float) (PerformanceMetric::query()
                ->where('endpoint', 'api.absensi')
                ->whereDate('created_at', $today)
                ->avg('query_duration_ms') ?? 0);
        }

        $todaysPayloads = Absensi::query()
            ->whereDate('tanggal', $today)
            ->count();

        $latencyRows = PerformanceMetric::query()
            ->where('endpoint', 'api.absensi')
            ->where('created_at', '>=', now()->subHours(24))
            ->orderByDesc('created_at')
            ->limit(300)
            ->get(['total_duration_ms', 'query_duration_ms', 'created_at']);

        $latencies = $latencyRows
            ->map(function ($row): float {
                $total = (float) ($row->total_duration_ms ?? 0);
                if ($total > 0) {
                    return $total;
                }

                return (float) ($row->query_duration_ms ?? 0);
            })
            ->filter(fn (float $value): bool => $value > 0)
            ->sort()
            ->values();

        $latencyCount = $latencies->count();
        $avgLatency24h = $latencyCount > 0 ? round($latencies->sum() / $latencyCount, 2) : 0.0;
        $p95Latency24h = $latencyCount > 0 ? round((float) $latencies[(int) floor(($latencyCount - 1) * 0.95)], 2) : 0.0;
        $latestLatencyMs = $latencyRows->isNotEmpty()
            ? round((float) (($latencyRows->first()->total_duration_ms ?? 0) ?: ($latencyRows->first()->query_duration_ms ?? 0)), 2)
            : 0.0;

        $audit24h = AuditLog::query()
            ->where('created_at', '>=', $now->copy()->subDay());

        $errorEvents24h = (clone $audit24h)
            ->where(function ($query) {
                $query->where('action', 'like', '%failed%')
                    ->orWhere('description', 'like', '%failed%')
                    ->orWhere('description', 'like', '%error%');
            })
            ->count();

        $successEvents24h = (clone $audit24h)
            ->where(function ($query) {
                $query->where('action', 'like', '%success%')
                    ->orWhere('action', 'login');
            })
            ->count();

        $recentEvents = AuditLog::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(4)
            ->get();

        return view('monitoring.health', [
            'onlineDevices' => $onlineDevices,
            'activeDevices' => $activeDevices,
            'staleDevices' => $staleDevices,
            'totalDevices' => $totalDevices,
            'avgLatency' => round($avgLatency, 2),
            'todaysPayloads' => $todaysPayloads,
            'devices' => $devices,
            'recentEvents' => $recentEvents,
            'eventsSummary24h' => [
                'errors' => $errorEvents24h,
                'success' => $successEvents24h,
                'total' => (clone $audit24h)->count(),
            ],
            'latencyStats24h' => [
                'sample_count' => $latencyCount,
                'avg_ms' => $avgLatency24h,
                'p95_ms' => $p95Latency24h,
                'latest_ms' => $latestLatencyMs,
            ],
        ]);
    }
}
