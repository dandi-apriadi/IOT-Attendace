<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Absensi;
use Illuminate\View\View;

class BillboardController extends Controller
{
    public function index(): View
    {
        $presentStatuses = (array) config('attendance.absensi_present_statuses', ['Hadir']);
        $excusedStatuses = (array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']);
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        // Get today's attendance records
        $today = today()->toDateString();

        $todayAbsensi = Absensi::whereDate('tanggal', $today)
            ->with('mahasiswa', 'jadwal')
            ->orderByDesc('created_at')
            ->get();

        // Count by status today
        $hadirCount = Absensi::whereDate('tanggal', $today)->whereIn('status', $presentStatuses)->count();
        $sabitIzinCount = Absensi::whereDate('tanggal', $today)->whereIn('status', $excusedStatuses)->count();
        $alpaCount = Absensi::whereDate('tanggal', $today)->where('status', $absentStatus)->count();

        // Stats for display
        $totalMahasiswa = Mahasiswa::count();
        $persentaseHadir = $totalMahasiswa > 0 ? round(($hadirCount / $totalMahasiswa) * 100, 1) : 0;

        // Get top active mahasiswa today
        $topMahasiswa = Absensi::whereDate('tanggal', $today)
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
