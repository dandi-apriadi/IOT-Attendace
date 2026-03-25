<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Device;
use App\Models\Jadwal;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = Carbon::today();
        $timeNow = Carbon::now()->format('H:i:s');

        $dayVariants = $this->dayVariants(Carbon::now());

        $hadirHariIni = Absensi::whereDate('tanggal', $today)->count();
        $sesiAktif = Jadwal::whereIn('hari', $dayVariants)
            ->where('jam_mulai', '<=', $timeNow)
            ->where('jam_selesai', '>=', $timeNow)
            ->count();

        $totalDeviceAktif = Device::where('is_active', true)->count();

        $latestAbsensi = Absensi::with(['mahasiswa', 'jadwal.mata_kuliah'])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_tap')
            ->limit(10)
            ->get();

        $recentDevices = Device::orderByDesc('last_seen_at')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'hadirHariIni' => $hadirHariIni,
            'sesiAktif' => $sesiAktif,
            'totalDeviceAktif' => $totalDeviceAktif,
            'latestAbsensi' => $latestAbsensi,
            'recentDevices' => $recentDevices,
        ]);
    }

    private function dayVariants(Carbon $date): array
    {
        $english = $date->format('l');

        $map = [
            'Monday' => ['Senin'],
            'Tuesday' => ['Selasa'],
            'Wednesday' => ['Rabu'],
            'Thursday' => ['Kamis'],
            'Friday' => ['Jumat'],
            'Saturday' => ['Sabtu'],
            'Sunday' => ['Minggu'],
        ];

        $variants = [$english, strtolower($english), strtoupper($english)];

        foreach (($map[$english] ?? []) as $id) {
            $variants[] = $id;
            $variants[] = strtolower($id);
            $variants[] = strtoupper($id);
        }

        return array_values(array_unique($variants));
    }
}
