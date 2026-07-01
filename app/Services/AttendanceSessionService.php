<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AttendanceSessionService
{
    public const GRACE_PERIOD_MINUTES = 15;
    public const ACTIVE_SESSIONS_CACHE_KEY = 'active_attendance_sessions';
    public const LEGACY_ACTIVE_SESSION_CACHE_KEY = 'active_attendance_session';
    public const FORCE_CLOSED_CACHE_KEY = 'force_closed_attendance_jadwal_ids';

    /**
     * @return array{jadwal: Jadwal, baseline_time: mixed}|null
     */
    public function resolveTapSchedule(Mahasiswa $mahasiswa, Carbon $now, bool $allowManualSession = true): ?array
    {
        $date = $now->toDateString();
        $time = $now->toTimeString();
        $manualSessions = $this->activeSessions();

        // Out-of-window manual session kept as last-resort (e.g. early-open or makeup session).
        $outOfWindowFallback = null;

        if ($allowManualSession) {
            foreach ($manualSessions as $manualSession) {
                if ((int) ($manualSession['kelas_id'] ?? 0) !== (int) $mahasiswa->kelas_id) {
                    continue;
                }

                $manualJadwal = $this->manualSessionSchedule($manualSession, $date);

                if (! $manualJadwal) {
                    continue;
                }

                // Sessions whose time window currently contains $time take priority.
                // Out-of-window sessions (a previous class still lingering in cache)
                // are skipped here so they don't shadow the correct current course.
                if ($manualJadwal->jam_mulai <= $time && $time <= $manualJadwal->jam_selesai) {
                    return [
                        'jadwal' => $manualJadwal,
                        'baseline_time' => $manualJadwal->jam_mulai,
                    ];
                }

                $outOfWindowFallback ??= $manualJadwal;
            }
        }

        $autoJadwal = Jadwal::query()
            ->with(['mata_kuliah', 'semesterAkademik'])
            ->where('kelas_id', $mahasiswa->kelas_id)
            ->whereIn('hari', $this->dayNames($now))
            ->where('jam_mulai', '<=', $time)
            ->where('jam_selesai', '>=', $time)
            ->whereHas('semesterAkademik', function ($q) use ($date) {
                $q->whereDate('tanggal_mulai', '<=', $date)
                    ->whereDate('tanggal_selesai', '>=', $date);
            })
            ->first();

        if ($autoJadwal) {
            return [
                'jadwal' => $autoJadwal,
                'baseline_time' => $autoJadwal->jam_mulai,
            ];
        }

        // No scheduled class active right now: fall back to an out-of-window manual session.
        // This covers early-open (dosen opened before jam_mulai) and makeup sessions.
        if ($outOfWindowFallback !== null) {
            return [
                'jadwal' => $outOfWindowFallback,
                'baseline_time' => $outOfWindowFallback->jam_mulai,
            ];
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    public function dayNames(Carbon $date): array
    {
        $dayMapId = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $dayMapEn = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return [
            $dayMapId[$date->dayOfWeekIso],
            $dayMapEn[$date->dayOfWeekIso],
        ];
    }

    public function statusForTap(mixed $tapTime, mixed $baselineTime): string
    {
        $tap = Carbon::parse($tapTime);
        $baseline = Carbon::parse($baselineTime);

        return $tap->gt($baseline->copy()->addMinutes(self::GRACE_PERIOD_MINUTES))
            ? 'Telat'
            : 'Hadir';
    }

    public function sessionPhase(
        Carbon $selectedDate,
        string $jamMulai,
        string $jamSelesai,
        Carbon $now
    ): string {
        $today = $now->copy()->startOfDay();
        $sessionDate = $selectedDate->copy()->startOfDay();

        if ($sessionDate->lt($today)) {
            return 'completed';
        }

        if ($sessionDate->gt($today)) {
            return 'upcoming';
        }

        $currentTime = $now->format('H:i:s');
        if ($currentTime < $jamMulai) {
            return 'upcoming';
        }

        if ($currentTime > $jamSelesai) {
            return 'completed';
        }

        return 'ongoing';
    }

    public function sessionPhaseLabel(string $phase): string
    {
        return match ($phase) {
            'completed' => 'Selesai',
            'ongoing' => 'Sedang Berlangsung',
            default => 'Akan Datang',
        };
    }

    public function missingAttendanceStatus(string $sessionPhase): string
    {
        return $sessionPhase === 'completed'
            ? (string) config('attendance.absensi_absent_status', 'Alpa')
            : 'Pending';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function activeSessions(): array
    {
        $sessions = Cache::get(self::ACTIVE_SESSIONS_CACHE_KEY, []);
        $sessions = is_array($sessions) ? $sessions : [];

        $normalized = [];
        foreach ($sessions as $key => $session) {
            if (! is_array($session)) {
                continue;
            }

            $normalized[$this->sessionKey($session, (string) $key)] = $session;
        }

        $legacySession = Cache::get(self::LEGACY_ACTIVE_SESSION_CACHE_KEY);
        if (is_array($legacySession)) {
            $normalized[$this->sessionKey($legacySession)] = $legacySession;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $session
     */
    public function putActiveSession(array $session, mixed $ttl = null): void
    {
        $sessions = $this->activeSessions();
        $sessions[$this->sessionKey($session)] = $session;

        Cache::put(
            self::ACTIVE_SESSIONS_CACHE_KEY,
            $sessions,
            $ttl ?: now()->addHours(3)
        );

        Cache::forget(self::LEGACY_ACTIVE_SESSION_CACHE_KEY);
    }

    public function forgetActiveSession(?int $jadwalId = null, ?int $mataKuliahId = null, ?int $kelasId = null): void
    {
        if ($jadwalId === null && $mataKuliahId === null && $kelasId === null) {
            // Close all: mark every active jadwal as force-closed so auto-open skips them.
            foreach ($this->activeSessions() as $session) {
                if (! empty($session['jadwal_id'])) {
                    $this->markForceClosed((int) $session['jadwal_id']);
                }
            }
            Cache::forget(self::ACTIVE_SESSIONS_CACHE_KEY);
            Cache::forget(self::LEGACY_ACTIVE_SESSION_CACHE_KEY);

            return;
        }

        $sessions = $this->activeSessions();
        foreach ($sessions as $key => $session) {
            $matchesJadwal = $jadwalId !== null && (int) ($session['jadwal_id'] ?? 0) === $jadwalId;
            $matchesCourseClass = $jadwalId === null
                && $mataKuliahId !== null
                && $kelasId !== null
                && (int) ($session['mata_kuliah_id'] ?? 0) === $mataKuliahId
                && (int) ($session['kelas_id'] ?? 0) === $kelasId;

            if ($matchesJadwal || $matchesCourseClass) {
                if (! empty($session['jadwal_id'])) {
                    $this->markForceClosed((int) $session['jadwal_id']);
                }
                unset($sessions[$key]);
            }
        }

        Cache::put(self::ACTIVE_SESSIONS_CACHE_KEY, $sessions, now()->addHours(3));
        Cache::forget(self::LEGACY_ACTIVE_SESSION_CACHE_KEY);
    }

    public function isForceClosedSession(int $jadwalId): bool
    {
        $closed = Cache::get(self::FORCE_CLOSED_CACHE_KEY, []);

        return in_array($jadwalId, is_array($closed) ? $closed : [], true);
    }

    private function markForceClosed(int $jadwalId): void
    {
        $closed = Cache::get(self::FORCE_CLOSED_CACHE_KEY, []);
        $closed = is_array($closed) ? $closed : [];

        if (! in_array($jadwalId, $closed, true)) {
            $closed[] = $jadwalId;
        }

        // Keep until end of the current calendar day so force-close survives page reloads.
        Cache::put(self::FORCE_CLOSED_CACHE_KEY, $closed, now()->endOfDay());
    }

    public function livePayloadCacheKey(string $date, mixed $jadwalId): string
    {
        return sprintf(
            'monitoring.live.payload.%s.%s',
            $date,
            (string) ($jadwalId ?: 'all')
        );
    }

    public function forgetLiveMonitoringCache(string $date, int $jadwalId): void
    {
        Cache::forget($this->livePayloadCacheKey($date, 'all'));
        Cache::forget($this->livePayloadCacheKey($date, $jadwalId));
    }

    /**
     * Build the live-monitoring payload (sessions, records, pending students, summary)
     * for a given date/jadwal/kelas filter. Shared by the web monitoring/live page and
     * the mobile API so both stay in sync with the same 2-second cache window.
     *
     * @param  array<int, int>|null  $allowedMataKuliahIds  When provided, restricts sessions
     *   to these mata_kuliah ids (used for dosen role scoping in the mobile API).
     * @return array<string, mixed>
     */
    public function buildLivePayload(
        string $selectedDate,
        mixed $selectedJadwalId,
        mixed $selectedKelasId = null,
        ?array $allowedMataKuliahIds = null
    ): array {
        $normalizedJadwalId = $selectedJadwalId ? (int) $selectedJadwalId : null;
        $normalizedKelasId = $selectedKelasId ? (int) $selectedKelasId : null;

        $cacheKey = $this->livePayloadCacheKey($selectedDate, $selectedJadwalId)
            . ($normalizedKelasId ? ':kelas:' . $normalizedKelasId : '')
            . ($allowedMataKuliahIds !== null ? ':mk:' . implode(',', $allowedMataKuliahIds) : '');

        return Cache::remember($cacheKey, now()->addSeconds(2), function () use ($selectedDate, $normalizedJadwalId, $normalizedKelasId, $allowedMataKuliahIds): array {
            $now = now();
            $selectedDateCarbon = Carbon::parse($selectedDate);
            $dayNames = $this->dayNames($selectedDateCarbon);

            $attendancePerSession = Absensi::query()
                ->selectRaw('jadwal_id, COUNT(*) as total')
                ->whereDate('tanggal', $selectedDate)
                ->groupBy('jadwal_id')
                ->pluck('total', 'jadwal_id');

            $sessions = Jadwal::query()
                ->with(['mata_kuliah:id,nama_mk,kode_mk', 'kelas:id,nama_kelas', 'dosen:id,name'])
                ->whereIn('hari', $dayNames)
                ->when($allowedMataKuliahIds !== null, fn ($q) => $q->whereIn('mata_kuliah_id', $allowedMataKuliahIds))
                ->orderBy('jam_mulai')
                ->get()
                ->map(function (Jadwal $jadwal) use ($attendancePerSession, $selectedDateCarbon, $now): array {
                    $phase = $this->sessionPhase(
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
                        'phase_label' => $this->sessionPhaseLabel($phase),
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
            } elseif ($allowedMataKuliahIds !== null) {
                $jadwalIdsForScope = collect($sessions)->pluck('id')->filter()->values()->all();

                $jadwalIdsForScope
                    ? $recordsQuery->whereIn('jadwal_id', $jadwalIdsForScope)
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
                    'time' => $item->waktu_tap ? substr((string) $item->waktu_tap, 0, 8) : (optional($item->updated_at)->format('H:i:s') ?? '-'),
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

                $status = $this->missingAttendanceStatus((string) ($selectedSession['phase'] ?? ''));

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
                    $missingStatus = $this->missingAttendanceStatus((string) ($jd['phase'] ?? ''));
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

    private function manualSessionSchedule(array $manualSession, string $date): ?Jadwal
    {
        $query = Jadwal::query()
            ->with(['mata_kuliah', 'semesterAkademik'])
            ->where('mata_kuliah_id', $manualSession['mata_kuliah_id'] ?? null)
            ->where('kelas_id', $manualSession['kelas_id'] ?? null)
            ->whereHas('semesterAkademik', function ($q) use ($date) {
                $q->whereDate('tanggal_mulai', '<=', $date)
                    ->whereDate('tanggal_selesai', '>=', $date);
            });

        if (! empty($manualSession['jadwal_id'])) {
            $query->whereKey((int) $manualSession['jadwal_id']);
        }

        return $query->first();
    }

    /**
     * @param array<string, mixed> $session
     */
    private function sessionKey(array $session, ?string $fallback = null): string
    {
        if (! empty($session['jadwal_id'])) {
            return 'jadwal:' . (int) $session['jadwal_id'];
        }

        $mataKuliahId = (int) ($session['mata_kuliah_id'] ?? 0);
        $kelasId = (int) ($session['kelas_id'] ?? 0);

        if ($mataKuliahId > 0 && $kelasId > 0) {
            return "course:{$mataKuliahId}:class:{$kelasId}";
        }

        return $fallback ?: md5(json_encode($session));
    }
}
