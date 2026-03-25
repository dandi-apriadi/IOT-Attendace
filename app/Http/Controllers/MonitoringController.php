<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Device;
use App\Models\Jadwal;
use Carbon\Carbon;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function live(): View
    {
        $now = Carbon::now();
        $timeNow = $now->format('H:i:s');
        $dayName = $now->format('l');

        // Hypothetical: Find the currently active session for IK-2A (as fallback or example)
        $activeSession = Jadwal::with(['kelas', 'mata_kuliah'])
            ->where('hari', $dayName)
            ->where('jam_mulai', '<=', $timeNow)
            ->where('jam_selesai', '>=', $timeNow)
            ->first();

        $latestAbsensi = Absensi::with(['mahasiswa', 'jadwal.mata_kuliah'])
            ->whereDate('tanggal', Carbon::today())
            ->orderByDesc('waktu_tap')
            ->limit(50)
            ->get();

        $totalHadir = Absensi::whereDate('tanggal', Carbon::today())->count();

        return view('monitoring.live', [
            'activeSession' => $activeSession,
            'latestAbsensi' => $latestAbsensi,
            'totalHadir' => $totalHadir,
        ]);
    }

    public function health(): View
    {
        $devices = Device::orderByDesc('last_seen_at')->get();
        $onlineCount = $devices->where('is_active', true)->count();
        
        return view('monitoring.health', [
            'devices' => $devices,
            'onlineCount' => $onlineCount,
            'totalCount' => $devices->count(),
        ]);
    }
}
