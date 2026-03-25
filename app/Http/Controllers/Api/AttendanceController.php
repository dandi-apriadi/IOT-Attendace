<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Jadwal;
use App\Models\Absensi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Handle IoT Attendance Tap
     */
    public function store(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'type' => 'required|in:RFID,Fingerprint,Face Recognition,Barcode',
        ]);

        $now = Carbon::now();
        $date = $now->toDateString();
        $time = $now->toTimeString();

        // 1. Find Mahasiswa by identifier
        $mahasiswa = Mahasiswa::where(function($query) use ($request) {
            $column = match($request->type) {
                'RFID' => 'rfid_uid',
                'Fingerprint' => 'fingerprint_data',
                'Face Recognition' => 'face_model_data',
                'Barcode' => 'barcode_id',
            };
            $query->where($column, $request->identifier);
        })->first();

        if (!$mahasiswa) {
            return response()->json(['message' => 'Mahasiswa tidak terdaftar'], 404);
        }

        // 2. Find Active Schedule for this student's class
        $jadwal = Jadwal::where('kelas_id', $mahasiswa->kelas_id)
            ->where('hari', $now->isoFormat('dddd')) // Logic might need adjustment for local lang
            ->where('jam_mulai', '<=', $time)
            ->where('jam_selesai', '>=', $time)
            ->first();

        if (!$jadwal) {
            return response()->json(['message' => 'Tidak ada jadwal aktif saat ini'], 400);
        }

        // 3. Mark Attendance
        $status = $this->calculateStatus($time, $jadwal->jam_mulai);

        $absensi = Absensi::updateOrCreate(
            [
                'mahasiswa_id' => $mahasiswa->id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $date,
            ],
            [
                'waktu_tap' => $time,
                'metode_absensi' => $request->type,
                'status' => $status,
            ]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'nama' => $mahasiswa->nama,
                'mata_kuliah' => $jadwal->mata_kuliah->nama_mk,
                'waktu' => $time,
                'keterangan' => $status
            ]
        ]);
    }

    private function calculateStatus($tapTime, $startTime)
    {
        $tap = Carbon::parse($tapTime);
        $start = Carbon::parse($startTime);

        if ($tap->diffInMinutes($start, false) > 15) {
            return 'Telat';
        }
        return 'Hadir';
    }
}
