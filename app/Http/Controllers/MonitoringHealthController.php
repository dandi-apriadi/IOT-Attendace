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
        $today = now()->toDateString();

        $devices = Device::query()
            ->orderByDesc('is_active')
            ->orderByDesc('last_seen_at')
            ->get();

        $onlineDevices = $devices->where('is_active', true)->count();
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

        $recentEvents = AuditLog::query()
            ->latest('created_at')
            ->limit(4)
            ->get();

        return view('monitoring.health', [
            'onlineDevices' => $onlineDevices,
            'totalDevices' => $totalDevices,
            'avgLatency' => round($avgLatency, 2),
            'todaysPayloads' => $todaysPayloads,
            'devices' => $devices,
            'recentEvents' => $recentEvents,
            'latencyStats24h' => [
                'sample_count' => $latencyCount,
                'avg_ms' => $avgLatency24h,
                'p95_ms' => $p95Latency24h,
                'latest_ms' => $latestLatencyMs,
            ],
        ]);
    }
}
