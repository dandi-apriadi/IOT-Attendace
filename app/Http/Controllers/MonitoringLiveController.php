<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\View\View;

class MonitoringLiveController extends Controller
{
    public function index(): View
    {
        // Get latest 50 attendance records (live stream)
        $liveStream = Absensi::with('mahasiswa', 'jadwal')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Count payloads by hour today
        $hourelyStats = Absensi::whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('hour')
            ->limit(24)
            ->get();

        // Current summary
        $todayTotal = Absensi::whereDate('created_at', today())->count();
        $thisHourTotal = Absensi::whereDate('created_at', today())
            ->where('created_at', '>=', now()->startOfHour())
            ->count();

        return view('monitoring.live', [
            'liveStream' => $liveStream,
            'hourelyStats' => $hourelyStats,
            'todayTotal' => $todayTotal,
            'thisHourTotal' => $thisHourTotal,
        ]);
    }
}
