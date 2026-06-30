<?php

namespace App\Services;

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
