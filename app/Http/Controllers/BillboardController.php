<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Absensi;
use Illuminate\View\View;

class BillboardController extends Controller
{
    public function index(): View
    {
        // Get today's attendance records
        $todayAbsensi = Absensi::whereDate('created_at', today())
            ->with('mahasiswa', 'jadwal')
            ->orderByDesc('created_at')
            ->get();

        // Count by status today
        $hariIni = today()->format('Y-m-d');
        $hadirCount = Absensi::whereDate('created_at', $hariIni)->where('status', 'hadir')->count();
        $sabitIzinCount = Absensi::whereDate('created_at', $hariIni)->where('status', 'sakit_izin')->count();
        $alpaCount = Absensi::whereDate('created_at', $hariIni)->where('status', 'alpa')->count();

        // Stats for display
        $totalMahasiswa = Mahasiswa::count();
        $persentaseHadir = $totalMahasiswa > 0 ? round(($hadirCount / $totalMahasiswa) * 100, 1) : 0;

        // Get top active mahasiswa today
        $topMahasiswa = Absensi::whereDate('created_at', $hariIni)
            ->with('mahasiswa')
            ->select('mahasiswa_id')
            ->selectRaw('COUNT(*) as tap_count')
            ->groupBy('mahasiswa_id')
            ->orderByDesc('tap_count')
            ->limit(10)
            ->get();

        return view('public.billboard', [
            'todayAbsensi' => $todayAbsensi,
            'hadirCount' => $hadirCount,
            'sabitIzinCount' => $sabitIzinCount,
            'alpaCount' => $alpaCount,
            'totalMahasiswa' => $totalMahasiswa,
            'persentaseHadir' => $persentaseHadir,
            'topMahasiswa' => $topMahasiswa,
        ]);
    }
}
