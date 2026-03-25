<?php

namespace App\Http\Controllers;

use App\Models\PerformanceMetric;
use Illuminate\View\View;

class MonitoringViewController extends Controller
{
    public function performanceReports(): View
    {
        $rows = PerformanceMetric::where('endpoint', 'reports.index')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get();

        $durations = $rows->pluck('query_duration_ms')
            ->map(fn ($v) => (float) $v)
            ->sort()
            ->values();

        $count = $durations->count();

        $avg = $count > 0 ? round($durations->sum() / $count, 3) : 0.0;
        $p50 = $count > 0 ? (float) $durations[(int) floor(($count - 1) * 0.50)] : 0.0;
        $p95 = $count > 0 ? (float) $durations[(int) floor(($count - 1) * 0.95)] : 0.0;

        $latest = $rows->take(25)->map(function ($row) {
            return [
                'query_duration_ms' => (float) $row->query_duration_ms,
                'total_duration_ms' => (float) ($row->total_duration_ms ?? 0),
                'result_count' => (int) $row->result_count,
                'page' => (int) $row->page,
                'period_month' => $row->period_month,
                'kelas_id' => $row->kelas_id,
                'mata_kuliah_id' => $row->mata_kuliah_id,
                'user_id' => $row->user_id,
                'created_at' => $row->created_at?->toIso8601String(),
            ];
        })->values();

        return view('monitoring.performance-reports', [
            'metrics' => [
                'count' => $count,
                'avg_ms' => $avg,
                'p50_ms' => round($p50, 3),
                'p95_ms' => round($p95, 3),
                'latest' => $latest,
            ],
        ]);
    }
}
