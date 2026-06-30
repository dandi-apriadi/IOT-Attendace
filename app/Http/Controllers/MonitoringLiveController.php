<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use App\Services\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MonitoringLiveController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $selectedJadwalId = $request->query('jadwal_id');
        $payload = $this->buildLivePayload($selectedDate, $selectedJadwalId);

        // Get active attendance session from cache
        $activeSessions = $this->attendanceSessions()->activeSessions();
        $activeSession = collect($activeSessions)
            ->when($selectedJadwalId, fn ($sessions) => $sessions->filter(
                fn ($session) => (int) ($session['jadwal_id'] ?? 0) === (int) $selectedJadwalId
            ))
            ->first() ?: collect($activeSessions)->first();
        $activeSessionInfo = null;

        if ($activeSession) {
            $jadwalQuery = Jadwal::query()
                ->with(['mata_kuliah:id,kode_mk,nama_mk', 'kelas:id,nama_kelas'])
                ->where('mata_kuliah_id', $activeSession['mata_kuliah_id'])
                ->where('kelas_id', $activeSession['kelas_id']);

            if (! empty($activeSession['jadwal_id'])) {
                $jadwalQuery->whereKey((int) $activeSession['jadwal_id']);
            }

            $jadwal = $jadwalQuery->first();

            if ($jadwal) {
                $activeSessionInfo = [
                    'mata_kuliah_id' => $jadwal->mata_kuliah_id,
                    'kelas_id' => $jadwal->kelas_id,
                    'mk_name' => $jadwal->mata_kuliah?->nama_mk ?? 'N/A',
                    'mk_kode' => $jadwal->mata_kuliah?->kode_mk ?? 'N/A',
                    'kelas_name' => $jadwal->kelas?->nama_kelas ?? 'N/A',
                    'source' => ($activeSession['source'] ?? 'manual') === 'auto_schedule' ? 'AUTO' : 'MANUAL',
                    'started_at' => $activeSession['started_at'] ?? null,
                    'jadwal_id' => $jadwal->id,
                ];
            }
        }

        return view('monitoring.live', [
            'records' => $payload['records'],
            'todayTotal' => $payload['today_total'],
            'thisHourTotal' => $payload['this_hour_total'],
            'lastUpdatedAt' => $payload['last_updated_at'],
            'selectedDate' => $payload['selected_date'],
            'selectedJadwalId' => $payload['selected_jadwal_id'],
            'sessions' => $payload['sessions'],
            'sessionSummary' => $payload['session_summary'],
            'selectedSession' => $payload['selected_session'],
            'activeSession' => $activeSessionInfo,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $selectedJadwalId = $request->query('jadwal_id');

        return response()->json($this->buildLivePayload($selectedDate, $selectedJadwalId));
    }

    public function edit(Request $request, Absensi $absensi): View
    {
        $absensi->load([
            'mahasiswa:id,nama,nim',
            'jadwal:id,mata_kuliah_id,kelas_id,hari,jam_mulai,jam_selesai',
            'jadwal.mata_kuliah:id,kode_mk,nama_mk',
            'jadwal.kelas:id,nama_kelas',
        ]);

        return view('monitoring.live-edit', [
            'absensi' => $absensi,
            'statusOptions' => array_values(config('attendance.absensi_statuses', [])),
            'methodOptions' => ['RFID', 'Fingerprint', 'Face Recognition', 'Barcode'],
            'returnDate' => $this->normalizeDate((string) $request->query('date', (string) $absensi->tanggal)),
            'returnJadwalId' => $request->query('jadwal_id', $absensi->jadwal_id),
        ]);
    }

    public function update(Request $request, Absensi $absensi): RedirectResponse
    {
        $statusOptions = array_values(config('attendance.absensi_statuses', []));
        $methodOptions = ['RFID', 'Fingerprint', 'Face Recognition', 'Barcode'];

        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', $statusOptions)],
            'metode_absensi' => ['required', 'in:' . implode(',', $methodOptions)],
            'waktu_tap' => ['required', 'date_format:H:i'],
            'return_date' => ['required', 'date'],
            'return_jadwal_id' => ['nullable', 'integer'],
        ]);

        $normalizedDate = $this->normalizeDate((string) $validated['return_date']);
        $normalizedTime = Carbon::createFromFormat('H:i', (string) $validated['waktu_tap'])->format('H:i:s');

        $absensi->update([
            'status' => $validated['status'],
            'metode_absensi' => $validated['metode_absensi'],
            'waktu_tap' => $normalizedTime,
        ]);

        $this->forgetLiveCache($normalizedDate, (int) $absensi->jadwal_id);

        return redirect()
            ->route('monitoring', [
                'date' => $normalizedDate,
                'jadwal_id' => $validated['return_jadwal_id'] ?: null,
            ])
            ->with('success', 'Data live monitoring berhasil diperbarui.');
    }

    private function normalizeDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function buildLivePayload(string $selectedDate, mixed $selectedJadwalId): array
    {
        $attendanceSessions = $this->attendanceSessions();
        $cacheKey = $attendanceSessions->livePayloadCacheKey($selectedDate, $selectedJadwalId);

        return Cache::remember($cacheKey, now()->addSeconds(2), function () use ($selectedDate, $selectedJadwalId, $attendanceSessions): array {
            $now = now();
            $selectedDateCarbon = Carbon::parse($selectedDate);
            $dayNames = $attendanceSessions->dayNames($selectedDateCarbon);
            $normalizedJadwalId = $selectedJadwalId ? (int) $selectedJadwalId : null;

            $attendancePerSession = Absensi::query()
                ->selectRaw('jadwal_id, COUNT(*) as total')
                ->whereDate('tanggal', $selectedDate)
                ->groupBy('jadwal_id')
                ->pluck('total', 'jadwal_id');

            $sessions = Jadwal::query()
                ->with(['mata_kuliah:id,nama_mk,kode_mk', 'kelas:id,nama_kelas', 'dosen:id,name'])
                ->whereIn('hari', $dayNames)
                ->orderBy('jam_mulai')
                ->get()
                ->map(function (Jadwal $jadwal) use ($attendancePerSession, $selectedDateCarbon, $now, $attendanceSessions): array {
                    $phase = $attendanceSessions->sessionPhase(
                        $selectedDateCarbon,
                        (string) $jadwal->jam_mulai,
                        (string) $jadwal->jam_selesai,
                        $now
                    );

                    return [
                        'id' => $jadwal->id,
                        'kelas_id' => $jadwal->kelas_id,
                        'course_name' => $jadwal->mata_kuliah?->nama_mk ?? 'N/A',
                        'course_code' => $jadwal->mata_kuliah?->kode_mk ?? 'N/A',
                        'class_name' => $jadwal->kelas?->nama_kelas ?? 'N/A',
                        'lecturer_name' => $jadwal->dosen?->name ?? 'Belum ditetapkan',
                        'start_time' => substr((string) $jadwal->jam_mulai, 0, 5),
                        'end_time' => substr((string) $jadwal->jam_selesai, 0, 5),
                        'attendance_count' => (int) ($attendancePerSession[$jadwal->id] ?? 0),
                        'phase' => $phase,
                        'phase_label' => $attendanceSessions->sessionPhaseLabel($phase),
                    ];
                })
                ->values()
                ->all();

            $selectedSession = null;
            if ($normalizedJadwalId) {
                foreach ($sessions as $session) {
                    if ((int) $session['id'] === $normalizedJadwalId) {
                        $selectedSession = $session;
                        break;
                    }
                }
            }

            $recordsQuery = Absensi::query()
                ->with([
                    'mahasiswa:id,nama,nim',
                    'jadwal:id,mata_kuliah_id,kelas_id,hari',
                    'jadwal.mata_kuliah:id,kode_mk',
                    'jadwal.kelas:id,nama_kelas',
                ])
                ->select(['id', 'mahasiswa_id', 'jadwal_id', 'tanggal', 'waktu_tap', 'metode_absensi', 'status', 'created_at', 'updated_at'])
                ->whereDate('tanggal', $selectedDate);

            if ($normalizedJadwalId) {
                $recordsQuery->where('jadwal_id', $normalizedJadwalId);
            }

            $liveStream = $recordsQuery
                ->orderByDesc('updated_at')
                ->orderByDesc('waktu_tap')
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();

            // attendancePerSession sudah aggregasi semua absensi hari ini per jadwal;
            // sum-nya setara dengan COUNT(*) WHERE tanggal = $selectedDate.
            $todayTotal = $attendancePerSession->sum();

            $thisHourTotal = 0;
            if ($selectedDateCarbon->isSameDay($now)) {
                $thisHourTotal = Absensi::query()
                    ->whereDate('tanggal', $selectedDate)
                    ->where('created_at', '>=', $now->copy()->startOfHour())
                    ->count();
            }

            $records = $liveStream->map(function (Absensi $item): array {
                return [
                    'id' => $item->id,
                    'jadwal_id' => $item->jadwal_id,
                    'date' => (string) ($item->tanggal ?? ''),
                    'time' => $item->waktu_tap ? substr((string) $item->waktu_tap, 0, 8) : (optional($item->updated_at)->format('H:i:s') ?? '-'),
                    'waktu_tap' => (string) ($item->waktu_tap ?? '-'),
                    'name' => $item->mahasiswa?->nama ?? 'N/A',
                    'nim' => $item->mahasiswa?->nim ?? 'N/A',
                    'schedule' => trim(
                        ($item->jadwal?->mata_kuliah?->kode_mk ?? 'N/A') . 
                        ' - ' . 
                        ($item->jadwal?->kelas?->nama_kelas ?? 'N/A')
                    ),
                    'metode_absensi' => (string) ($item->metode_absensi ?? '-'),
                    'status' => (string) ($item->status ?? '-'),
                    'is_pending' => false,
                    'editable' => true,
                ];
            })->values()->all();

            if ($normalizedJadwalId && $selectedSession) {
                $attendedMahasiswaIds = Absensi::query()
                    ->whereDate('tanggal', $selectedDate)
                    ->where('jadwal_id', $normalizedJadwalId)
                    ->whereNotNull('mahasiswa_id')
                    ->distinct()
                    ->pluck('mahasiswa_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->values()
                    ->all();

                $pendingRows = Mahasiswa::query()
                    ->select(['id', 'nama', 'nim'])
                    ->where('kelas_id', (int) $selectedSession['kelas_id'])
                    ->when(!empty($attendedMahasiswaIds), function ($query) use ($attendedMahasiswaIds) {
                        $query->whereNotIn('id', $attendedMahasiswaIds);
                    })
                    ->orderBy('nama')
                    ->get()
                    ->map(function (Mahasiswa $mahasiswa) use ($selectedSession, $selectedDate, $normalizedJadwalId, $attendanceSessions): array {
                        $status = $attendanceSessions->missingAttendanceStatus((string) ($selectedSession['phase'] ?? ''));
                        $isAbsent = $status !== 'Pending';

                        return [
                            'id' => null,
                            'jadwal_id' => $normalizedJadwalId,
                            'date' => $selectedDate,
                            'time' => '-',
                            'waktu_tap' => '-',
                            'name' => $mahasiswa->nama,
                            'nim' => $mahasiswa->nim,
                            'schedule' => trim(($selectedSession['course_code'] ?? 'N/A') . ' - ' . ($selectedSession['class_name'] ?? 'N/A')),
                            'metode_absensi' => '-',
                            'status' => $status,
                            'is_pending' => ! $isAbsent,
                            'editable' => false,
                        ];
                    })
                    ->values()
                    ->all();

                $records = array_merge($records, $pendingRows);
            }

            $sessionSummary = [
                'completed' => 0,
                'ongoing' => 0,
                'upcoming' => 0,
            ];

            foreach ($sessions as $session) {
                $phase = $session['phase'];
                if (isset($sessionSummary[$phase])) {
                    $sessionSummary[$phase]++;
                }
            }

            return [
                'selected_date' => $selectedDate,
                'selected_jadwal_id' => $normalizedJadwalId,
                'sessions' => $sessions,
                'selected_session' => $selectedSession,
                'session_summary' => $sessionSummary,
                'today_total' => $todayTotal,
                'this_hour_total' => $thisHourTotal,
                'last_updated_at' => $now->format('H:i:s'),
                'records' => $records,
            ];
        });
    }

    private function forgetLiveCache(string $selectedDate, int $jadwalId): void
    {
        $this->attendanceSessions()->forgetLiveMonitoringCache($selectedDate, $jadwalId);
    }

    private function attendanceSessions(): AttendanceSessionService
    {
        return app(AttendanceSessionService::class);
    }
}
