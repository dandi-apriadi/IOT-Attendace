<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Absensi;
use Illuminate\View\View;

class StudentDetailController extends Controller
{
    public function show($id): View
    {
        $mahasiswa = Mahasiswa::find($id);
        
        if (!$mahasiswa) {
            abort(404, 'Mahasiswa tidak ditemukan');
        }

        // Get attendance history
        $absensiHistory = $mahasiswa->absensi()
            ->with('jadwal.mataKuliah')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Calculate attendance statistics
        $totalAbsensi = $mahasiswa->absensi()->count();
        $hadirCount = $mahasiswa->absensi()->where('status', 'hadir')->count();
        $sabitIzinCount = $mahasiswa->absensi()->where('status', 'sakit_izin')->count();
        $alpaCount = $mahasiswa->absensi()->where('status', 'alpa')->count();
        $persentaseHadir = $totalAbsensi > 0 ? round(($hadirCount / $totalAbsensi) * 100, 1) : 0;

        // This month stats
        $thisMonthAbsensi = $mahasiswa->absensi()->whereMonth('created_at', now()->month)->count();
        $thisMonthHadir = $mahasiswa->absensi()->whereMonth('created_at', now()->month)->where('status', 'hadir')->count();

        return view('master.student-detail', [
            'mahasiswa' => $mahasiswa,
            'absensiHistory' => $absensiHistory,
            'totalAbsensi' => $totalAbsensi,
            'hadirCount' => $hadirCount,
            'sabitIzinCount' => $sabitIzinCount,
            'alpaCount' => $alpaCount,
            'persentaseHadir' => $persentaseHadir,
            'thisMonthAbsensi' => $thisMonthAbsensi,
            'thisMonthHadir' => $thisMonthHadir,
        ]);
    }
}
