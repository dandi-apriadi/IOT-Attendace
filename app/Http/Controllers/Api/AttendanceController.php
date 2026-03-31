<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mahasiswa;
use App\Models\Jadwal;
use App\Models\Absensi;
use App\Models\PerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Throwable;

class AttendanceController extends Controller
{
    /**
     * Standard grace period for attendance in minutes.
     */
    const GRACE_PERIOD_MINUTES = 15;

    /**
     * Handle IoT Attendance Tap
     */
    public function store(Request $request)
    {
        $requestStartedAt = microtime(true);
        $queryDurationMs = 0.0;
        $resultCount = 0;

        $request->validate([
            'identifier' => 'required|string',
            'type' => 'required|in:RFID,Fingerprint,Face Recognition,Barcode',
        ]);

        $now = Carbon::now();
        $date = $now->toDateString();
        $time = $now->toTimeString();

        $queryStartedAt = microtime(true);

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
            $queryDurationMs = (microtime(true) - $queryStartedAt) * 1000;
            $this->recordApiPerformanceMetric($queryDurationMs, $requestStartedAt, $resultCount, $request);
            return response()->json(['message' => 'Mahasiswa tidak terdaftar'], 404);
        }

        // 2. Determine Active Schedule (PRIORITY: Manual Cache > Automatic Schedule)
        $jadwal = null;
        $manualSession = Cache::get('active_attendance_session');
        $baselineTime = null;

        if ($manualSession) {
            $jadwal = Jadwal::where('mata_kuliah_id', $manualSession['mata_kuliah_id'])
                ->where('kelas_id', $manualSession['kelas_id'])
                ->first();
            
            if ($jadwal) {
               // For manual sessions, the grace period is based on when the session actually started.
               $baselineTime = $manualSession['started_at'] ?? $jadwal->jam_mulai;
            }
        }

        if (!$jadwal) {
            $dayNames = $this->getDayNames($now);
            $jadwal = Jadwal::where('kelas_id', $mahasiswa->kelas_id)
                ->whereIn('hari', $dayNames)
                ->where('jam_mulai', '<=', $time)
                ->where('jam_selesai', '>=', $time)
                ->first();
            
            $baselineTime = $jadwal?->jam_mulai;
        }

        if (!$jadwal) {
            $queryDurationMs = (microtime(true) - $queryStartedAt) * 1000;
            $this->recordApiPerformanceMetric($queryDurationMs, $requestStartedAt, $resultCount, $request);
            return response()->json(['message' => 'Tidak ada jadwal/sesi aktif saat ini'], 400);
        }

        // 3. Mark Attendance with transaction and lock to reduce race conditions.
        $status = $this->calculateStatus($time, $baselineTime);

        DB::transaction(function () use ($mahasiswa, $jadwal, $date, $time, $request, $status): void {
            $existingAttendance = Absensi::where('mahasiswa_id', $mahasiswa->id)
                ->where('jadwal_id', $jadwal->id)
                ->where('tanggal', $date)
                ->lockForUpdate()
                ->first();

            if ($existingAttendance) {
                $existingAttendance->update([
                    'waktu_tap' => $time,
                    'metode_absensi' => $request->type,
                    'status' => $status,
                ]);

                return;
            }

            Absensi::create([
                'mahasiswa_id' => $mahasiswa->id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $date,
                'waktu_tap' => $time,
                'metode_absensi' => $request->type,
                'status' => $status,
            ]);
        });

        $queryDurationMs = (microtime(true) - $queryStartedAt) * 1000;
        $resultCount = 1;
        $this->recordApiPerformanceMetric($queryDurationMs, $requestStartedAt, $resultCount, $request);

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

    private function recordApiPerformanceMetric(float $queryDurationMs, float $requestStartedAt, int $resultCount, Request $request): void
    {
        $totalDurationMs = (microtime(true) - $requestStartedAt) * 1000;

        try {
            PerformanceMetric::create([
                'endpoint' => 'api.absensi',
                'query_duration_ms' => round($queryDurationMs, 3),
                'total_duration_ms' => round($totalDurationMs, 3),
                'result_count' => $resultCount,
                'page' => 1,
                'user_id' => $request->user()?->id,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Failed to record api.absensi performance metric', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function calculateStatus($tapTime, $baselineTime)
    {
        $tap = Carbon::parse($tapTime);
        $baseline = Carbon::parse($baselineTime);

        // If tap is more than GRACE_PERIOD_MINUTES after baseline, mark as Late (Telat).
        if ($tap->diffInMinutes($baseline, false) > self::GRACE_PERIOD_MINUTES) {
            return 'Telat';
        }
        return 'Hadir';
    }

    private function getDayNames($date): array
    {
        $map = [
            1 => ['Senin', 'Monday'],
            2 => ['Selasa', 'Tuesday'],
            3 => ['Rabu', 'Wednesday'],
            4 => ['Kamis', 'Thursday'],
            5 => ['Jumat', 'Friday'],
            6 => ['Sabtu', 'Saturday'],
            7 => ['Minggu', 'Sunday'],
        ];

        return $map[$date->dayOfWeekIso] ?? [$date->format('l')];
    }
}
