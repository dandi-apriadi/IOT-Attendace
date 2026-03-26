<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MonitoringLiveController extends Controller
{
    public function index(): View
    {
        $payload = $this->buildLivePayload();

        return view('monitoring.live', [
            'records' => $payload['records'],
            'todayTotal' => $payload['today_total'],
            'thisHourTotal' => $payload['this_hour_total'],
            'lastUpdatedAt' => $payload['last_updated_at'],
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->buildLivePayload());
    }

    private function buildLivePayload(): array
    {
        $cacheKey = 'monitoring.live.payload';

        $payload = Cache::remember($cacheKey, now()->addSeconds(5), function (): array {
            $now = now();
            $startOfToday = $now->copy()->startOfDay();
            $startOfHour = $now->copy()->startOfHour();

            $liveStream = Absensi::query()
                ->with([
                    'mahasiswa:id,nama,nim',
                    'jadwal:id,mata_kuliah_id,hari',
                    'jadwal.mata_kuliah:id,kode_mk',
                ])
                ->select(['id', 'mahasiswa_id', 'jadwal_id', 'status', 'created_at'])
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();

            $todayTotal = Absensi::query()
                ->where('tanggal', $startOfToday->toDateString())
                ->count();

            $thisHourTotal = Absensi::query()
                ->where('created_at', '>=', $startOfHour)
                ->count();

            $records = $liveStream->map(function (Absensi $item): array {
                return [
                    'time' => optional($item->created_at)->format('H:i:s') ?? '-',
                    'name' => $item->mahasiswa?->nama ?? 'N/A',
                    'nim' => $item->mahasiswa?->nim ?? 'N/A',
                    'schedule' => trim(($item->jadwal?->mata_kuliah?->kode_mk ?? 'N/A') . ' - ' . ($item->jadwal?->hari ?? 'N/A')),
                    'status' => (string) ($item->status ?? '-'),
                ];
            })->values()->all();

            return [
                'today_total' => $todayTotal,
                'this_hour_total' => $thisHourTotal,
                'last_updated_at' => $now->format('H:i:s'),
                'records' => $records,
            ];
        });

        return $payload;
    }
}
