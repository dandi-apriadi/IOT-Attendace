<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesByDosen;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\SemesterAkademik;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    use ScopesByDosen;

    /**
     * GET /api/v1/audit-log -- admin only.
     */
    public function auditLog(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang dapat mengakses audit log.');
        }

        $logs = AuditLog::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->paginate((int) $request->query('per_page', 30));

        $summary = AuditLog::query()
            ->selectRaw('COUNT(*) AS total_events')
            ->selectRaw("SUM(CASE WHEN action = 'login' OR action = 'login_mobile' THEN 1 ELSE 0 END) AS auth_events")
            ->selectRaw("SUM(CASE WHEN action LIKE '%failed%' THEN 1 ELSE 0 END) AS error_events")
            ->first();

        return AuditLogResource::collection($logs)->additional([
            'summary' => [
                'total_events' => (int) $summary->total_events,
                'auth_events' => (int) $summary->auth_events,
                'error_events' => (int) $summary->error_events,
            ],
        ])->response();
    }

    /**
     * GET /api/v1/reports/student-summary -- admin & dosen (dosen di-scope ke
     * mata kuliah miliknya). Rekap persentase kehadiran per mahasiswa, setara
     * dengan menu "Laporan" di web (ReportsController::index) versi ringkas.
     */
    public function studentReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'semester_id' => ['nullable', 'integer'],
            'kelas_id' => ['nullable', 'integer'],
            'mata_kuliah_id' => ['nullable', 'integer'],
            'status_filter' => ['nullable', 'in:present,excused,absent'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $presentStatuses = array_values((array) config('attendance.absensi_present_statuses', ['Hadir']));
        $excusedStatuses = array_values((array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']));
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        $semester = ! empty($validated['semester_id'])
            ? SemesterAkademik::find($validated['semester_id'])
            : (SemesterAkademik::where('is_active', true)->first() ?? SemesterAkademik::orderByDesc('tanggal_mulai')->first());

        $allowedMataKuliahIds = $this->allowedMataKuliahIds($request);

        $query = DB::table('absensi as a')
            ->join('mahasiswa as m', 'm.id', '=', 'a.mahasiswa_id')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->select(['m.id', 'm.nama'])
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                'SUM(CASE WHEN a.status IN (' . implode(',', array_fill(0, count($presentStatuses), '?')) . ') THEN 1 ELSE 0 END) AS hadir',
                $presentStatuses
            )
            ->selectRaw(
                'SUM(CASE WHEN a.status IN (' . implode(',', array_fill(0, count($excusedStatuses), '?')) . ') THEN 1 ELSE 0 END) AS sakit_izin',
                $excusedStatuses
            )
            ->selectRaw('SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS alpa', [$absentStatus]);

        if ($semester) {
            $query->where('j.semester_akademik_id', $semester->id);
        }

        if (! empty($validated['kelas_id'])) {
            $query->where('j.kelas_id', $validated['kelas_id']);
        }

        if (! empty($validated['mata_kuliah_id'])) {
            $query->where('j.mata_kuliah_id', $validated['mata_kuliah_id']);
        }

        if ($allowedMataKuliahIds !== null) {
            if (empty($allowedMataKuliahIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('j.mata_kuliah_id', $allowedMataKuliahIds);
            }
        }

        if (($validated['status_filter'] ?? null) === 'present') {
            $query->whereIn('a.status', $presentStatuses);
        } elseif (($validated['status_filter'] ?? null) === 'excused') {
            $query->whereIn('a.status', $excusedStatuses);
        } elseif (($validated['status_filter'] ?? null) === 'absent') {
            $query->where('a.status', $absentStatus);
        }

        $perPage = (int) ($validated['per_page'] ?? 25);

        $paginated = $query
            ->groupBy('m.id', 'm.nama')
            ->orderByDesc('hadir')
            ->orderBy('m.nama')
            ->paginate($perPage);

        $rows = collect($paginated->items())->map(function ($row): array {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;

            return [
                'mahasiswa_id' => (int) $row->id,
                'nama' => (string) $row->nama,
                'total' => $total,
                'hadir' => $hadir,
                'sakit_izin' => (int) $row->sakit_izin,
                'alpa' => (int) $row->alpa,
                'persentase' => $total > 0 ? round(($hadir / $total) * 100, 2) : 0,
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'semester' => $semester?->only(['id', 'nama_semester', 'tahun_ajaran']),
            ],
        ]);
    }

    /**
     * GET /api/v1/reports/attendance-trend -- admin & dosen (dosen di-scope).
     * Rekap jumlah absensi per hari untuk rentang tanggal yang dipilih, dengan
     * filter kelas/mata kuliah/status -- dipakai untuk chart tren di Dashboard.
     */
    public function attendanceTrend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'kelas_id' => ['nullable', 'integer'],
            'mata_kuliah_id' => ['nullable', 'integer'],
            'status_filter' => ['nullable', 'in:present,excused,absent'],
        ]);

        $endDate = ! empty($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->startOfDay()
            : Carbon::now()->startOfDay();
        $startDate = ! empty($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->startOfDay()
            : $endDate->copy()->subDays(6);

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        // Cap the range so a mobile client can't request an unbounded scan.
        if ($startDate->diffInDays($endDate) > 90) {
            $startDate = $endDate->copy()->subDays(90);
        }

        $presentStatuses = array_values((array) config('attendance.absensi_present_statuses', ['Hadir']));
        $excusedStatuses = array_values((array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']));
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        $query = DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->whereBetween('a.tanggal', [$startDate->toDateString(), $endDate->toDateString()]);

        $allowedMataKuliahIds = $this->allowedMataKuliahIds($request);
        if ($allowedMataKuliahIds !== null) {
            if (empty($allowedMataKuliahIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('j.mata_kuliah_id', $allowedMataKuliahIds);
            }
        }

        if (! empty($validated['kelas_id'])) {
            $query->where('j.kelas_id', $validated['kelas_id']);
        }

        if (! empty($validated['mata_kuliah_id'])) {
            $query->where('j.mata_kuliah_id', $validated['mata_kuliah_id']);
        }

        if (($validated['status_filter'] ?? null) === 'present') {
            $query->whereIn('a.status', $presentStatuses);
        } elseif (($validated['status_filter'] ?? null) === 'excused') {
            $query->whereIn('a.status', $excusedStatuses);
        } elseif (($validated['status_filter'] ?? null) === 'absent') {
            $query->where('a.status', $absentStatus);
        }

        $rows = $query
            ->selectRaw('a.tanggal, COUNT(*) as total')
            ->groupBy('a.tanggal')
            ->pluck('total', 'a.tanggal');

        $labels = [];
        $data = [];
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $key = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $data[] = (int) ($rows[$key] ?? 0);
            $cursor->addDay();
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);
    }
}
