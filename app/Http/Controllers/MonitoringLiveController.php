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
        $selectedKelasId = $request->query('kelas_id');
        $payload = $this->buildLivePayload($selectedDate, $selectedJadwalId, $selectedKelasId);

        // Resolve ALL active sessions from cache into enriched info arrays.
        $rawActiveSessions = $this->attendanceSessions()->activeSessions();
        $activeSessionsInfo = [];

        foreach ($rawActiveSessions as $rawSession) {
            $jadwalQuery = Jadwal::query()
                ->with(['mata_kuliah:id,kode_mk,nama_mk', 'kelas:id,nama_kelas'])
                ->where('mata_kuliah_id', $rawSession['mata_kuliah_id'] ?? null)
                ->where('kelas_id', $rawSession['kelas_id'] ?? null);

            if (! empty($rawSession['jadwal_id'])) {
                $jadwalQuery->whereKey((int) $rawSession['jadwal_id']);
            }

            $jadwal = $jadwalQuery->first();
            if (! $jadwal) {
                continue;
            }

            $activeSessionsInfo[] = [
                'mata_kuliah_id' => $jadwal->mata_kuliah_id,
                'kelas_id' => $jadwal->kelas_id,
                'mk_name' => $jadwal->mata_kuliah?->nama_mk ?? 'N/A',
                'mk_kode' => $jadwal->mata_kuliah?->kode_mk ?? 'N/A',
                'kelas_name' => $jadwal->kelas?->nama_kelas ?? 'N/A',
                'source' => ($rawSession['source'] ?? 'manual') === 'auto_schedule' ? 'AUTO' : 'MANUAL',
                'started_at' => $rawSession['started_at'] ?? null,
                'jadwal_id' => $jadwal->id,
            ];
        }

        return view('monitoring.live', [
            'records' => $payload['records'],
            'todayTotal' => $payload['today_total'],
            'thisHourTotal' => $payload['this_hour_total'],
            'lastUpdatedAt' => $payload['last_updated_at'],
            'selectedDate' => $payload['selected_date'],
            'selectedJadwalId' => $payload['selected_jadwal_id'],
            'selectedKelasId' => $payload['selected_kelas_id'],
            'sessions' => $payload['sessions'],
            'sessionSummary' => $payload['session_summary'],
            'selectedSession' => $payload['selected_session'],
            'activeSessions' => $activeSessionsInfo,
            'kelases' => $payload['kelases'],
            'jadwalList' => $payload['jadwal_list'],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $selectedJadwalId = $request->query('jadwal_id');
        $selectedKelasId = $request->query('kelas_id');

        return response()->json($this->buildLivePayload($selectedDate, $selectedJadwalId, $selectedKelasId));
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

    private function buildLivePayload(string $selectedDate, mixed $selectedJadwalId, mixed $selectedKelasId = null): array
    {
        $attendanceSessions = $this->attendanceSessions();
        $normalizedJadwalId = $selectedJadwalId ? (int) $selectedJadwalId : null;
        $normalizedKelasId = $selectedKelasId ? (int) $selectedKelasId : null;

        $cacheKey = $attendanceSessions->livePayloadCacheKey($selectedDate, $selectedJadwalId)
            . ($normalizedKelasId ? ':kelas:' . $normalizedKelasId : '');

        return Cache::remember($cacheKey, now()->addSeconds(2), function () use ($selectedDate, $normalizedJadwalId, $normalizedKelasId, $attendanceSessions): array {
            $now = now();
            $selectedDateCarbon = Carbon::parse($selectedDate);
            $dayNames = $attendanceSessions->dayNames($selectedDateCarbon);

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

            // Derive dropdown lists from today's sessions.
            $kelases = collect($sessions)
                ->unique('kelas_id')
                ->map(fn ($s) => ['id' => $s['kelas_id'], 'name' => $s['class_name']])
                ->sortBy('name')
                ->values()
                ->all();

            // If a kelas is active, cascade: jadwal list shows only that kelas's courses.
            $jadwalList = collect($sessions)
                ->when($normalizedKelasId, fn ($c) => $c->filter(fn ($s) => (int) $s['kelas_id'] === $normalizedKelasId))
                ->map(fn ($s) => [
                    'id' => $s['id'],
                    'name' => $s['course_code'] . ' — ' . $s['course_name'] . ' (' . $s['start_time'] . ')',
                    'kelas_id' => $s['kelas_id'],
                ])
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

            // If jadwal selected, derive kelas from it so the dropdown shows correctly.
            $effectiveKelasId = $normalizedKelasId ?? ($selectedSession ? (int) $selectedSession['kelas_id'] : null);

            $recordsQuery = Absensi::query()
                ->with([
                    'mahasiswa:id,nama,nim',
                    'jadwal:id,mata_kuliah_id,kelas_id',
                    'jadwal.mata_kuliah:id,kode_mk,nama_mk',
                    'jadwal.kelas:id,nama_kelas',
                ])
                ->select(['id', 'mahasiswa_id', 'jadwal_id', 'tanggal', 'waktu_tap', 'metode_absensi', 'status', 'created_at', 'updated_at'])
                ->whereDate('tanggal', $selectedDate);

            $hasFilter = $normalizedJadwalId || $normalizedKelasId;

            if ($normalizedJadwalId) {
                $recordsQuery->where('jadwal_id', $normalizedJadwalId);
            } elseif ($normalizedKelasId) {
                $jadwalIdsForKelas = collect($sessions)
                    ->filter(fn ($s) => (int) $s['kelas_id'] === $normalizedKelasId)
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->all();

                $jadwalIdsForKelas
                    ? $recordsQuery->whereIn('jadwal_id', $jadwalIdsForKelas)
                    : $recordsQuery->whereRaw('1 = 0');
            }

            $liveStream = $recordsQuery
                ->orderByDesc('updated_at')
                ->orderByDesc('waktu_tap')
                ->orderByDesc('created_at')
                ->when(! $hasFilter, fn ($q) => $q->limit(50))
                ->get();

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
                    'kelas_id' => $item->jadwal?->kelas_id,
                    'date' => (string) ($item->tanggal ?? ''),
                    'time' => $item->waktu_tap ? substr((string) $item->waktu_tap, 0, 5) : (optional($item->updated_at)->format('H:i') ?? '-'),
                    'waktu_tap' => (string) ($item->waktu_tap ?? '-'),
                    'name' => $item->mahasiswa?->nama ?? 'N/A',
                    'nim' => $item->mahasiswa?->nim ?? 'N/A',
                    'course_name' => $item->jadwal?->mata_kuliah?->nama_mk ?? 'N/A',
                    'course_code' => $item->jadwal?->mata_kuliah?->kode_mk ?? 'N/A',
                    'kelas_name' => $item->jadwal?->kelas?->nama_kelas ?? 'N/A',
                    'metode_absensi' => (string) ($item->metode_absensi ?? '-'),
                    'status' => (string) ($item->status ?? '-'),
                    'is_pending' => false,
                    'editable' => true,
                ];
            })->values()->all();

            // Build pending (not-yet-attended) rows.
            if ($normalizedJadwalId && $selectedSession) {
                // Single-jadwal view: show students who haven't tapped.
                $attendedIds = collect($liveStream)->pluck('mahasiswa_id')->filter()->map(fn ($id) => (int) $id)->all();

                $pending = Mahasiswa::query()
                    ->select(['id', 'nama', 'nim'])
                    ->where('kelas_id', (int) $selectedSession['kelas_id'])
                    ->when(! empty($attendedIds), fn ($q) => $q->whereNotIn('id', $attendedIds))
                    ->orderBy('nama')
                    ->get();

                $status = $attendanceSessions->missingAttendanceStatus((string) ($selectedSession['phase'] ?? ''));

                foreach ($pending as $mhs) {
                    $records[] = [
                        'id' => null,
                        'jadwal_id' => $normalizedJadwalId,
                        'kelas_id' => $selectedSession['kelas_id'],
                        'date' => $selectedDate,
                        'time' => '-',
                        'waktu_tap' => '-',
                        'name' => $mhs->nama,
                        'nim' => $mhs->nim,
                        'course_name' => $selectedSession['course_name'],
                        'course_code' => $selectedSession['course_code'],
                        'kelas_name' => $selectedSession['class_name'],
                        'metode_absensi' => '-',
                        'status' => $status,
                        'is_pending' => $status === 'Pending',
                        'editable' => false,
                    ];
                }
            } elseif ($normalizedKelasId) {
                // Kelas view: for each jadwal in this kelas, show students who haven't tapped.
                $kelasJadwals = array_filter($sessions, fn ($s) => (int) $s['kelas_id'] === $normalizedKelasId);

                $attendedMap = [];
                foreach ($liveStream as $ab) {
                    $attendedMap[(int) $ab->mahasiswa_id][(int) $ab->jadwal_id] = true;
                }

                $kelasMahasiswa = Mahasiswa::query()
                    ->select(['id', 'nama', 'nim'])
                    ->where('kelas_id', $normalizedKelasId)
                    ->orderBy('nama')
                    ->get();

                foreach ($kelasJadwals as $jd) {
                    $missingStatus = $attendanceSessions->missingAttendanceStatus((string) ($jd['phase'] ?? ''));
                    foreach ($kelasMahasiswa as $mhs) {
                        if (! isset($attendedMap[(int) $mhs->id][$jd['id']])) {
                            $records[] = [
                                'id' => null,
                                'jadwal_id' => $jd['id'],
                                'kelas_id' => $jd['kelas_id'],
                                'date' => $selectedDate,
                                'time' => '-',
                                'waktu_tap' => '-',
                                'name' => $mhs->nama,
                                'nim' => $mhs->nim,
                                'course_name' => $jd['course_name'],
                                'course_code' => $jd['course_code'],
                                'kelas_name' => $jd['class_name'],
                                'metode_absensi' => '-',
                                'status' => $missingStatus,
                                'is_pending' => $missingStatus === 'Pending',
                                'editable' => false,
                            ];
                        }
                    }
                }

                // Sort: by jadwal start_time, then attended before pending, then by name.
                $startTimes = [];
                foreach ($sessions as $s) {
                    $startTimes[$s['id']] = $s['start_time'];
                }
                usort($records, function (array $a, array $b) use ($startTimes): int {
                    $tA = $startTimes[$a['jadwal_id']] ?? '99:99';
                    $tB = $startTimes[$b['jadwal_id']] ?? '99:99';
                    if ($tA !== $tB) {
                        return strcmp($tA, $tB);
                    }
                    if ($a['is_pending'] !== $b['is_pending']) {
                        return $a['is_pending'] ? 1 : -1;
                    }

                    return strcmp((string) $a['name'], (string) $b['name']);
                });
            }

            $sessionSummary = ['completed' => 0, 'ongoing' => 0, 'upcoming' => 0];
            foreach ($sessions as $session) {
                $phase = $session['phase'];
                if (isset($sessionSummary[$phase])) {
                    $sessionSummary[$phase]++;
                }
            }

            return [
                'selected_date' => $selectedDate,
                'selected_jadwal_id' => $normalizedJadwalId,
                'selected_kelas_id' => $effectiveKelasId,
                'sessions' => $sessions,
                'selected_session' => $selectedSession,
                'session_summary' => $sessionSummary,
                'today_total' => $todayTotal,
                'this_hour_total' => $thisHourTotal,
                'last_updated_at' => $now->format('H:i:s'),
                'records' => $records,
                'kelases' => $kelases,
                'jadwal_list' => $jadwalList,
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
