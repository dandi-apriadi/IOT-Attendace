<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringLiveController extends Controller
{
    public function index(Request $request): View
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $selectedJadwalId = $request->query('jadwal_id');
        $payload = $this->buildLivePayload($selectedDate, $selectedJadwalId);

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

    private function getIndoDayName(Carbon $date): string
    {
        // Database stores English day names (Monday, Tuesday, etc.)
        return $date->format('l');
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
        $cacheKey = sprintf(
            'monitoring.live.payload.%s.%s',
            $selectedDate,
            (string) ($selectedJadwalId ?: 'all')
        );

        return Cache::remember($cacheKey, now()->addSeconds(5), function () use ($selectedDate, $selectedJadwalId): array {
            $now = now();
            $selectedDateCarbon = Carbon::parse($selectedDate);
            $dayName = $this->getIndoDayName($selectedDateCarbon);
            $normalizedJadwalId = $selectedJadwalId ? (int) $selectedJadwalId : null;

            $attendancePerSession = Absensi::query()
                ->selectRaw('jadwal_id, COUNT(*) as total')
                ->whereDate('tanggal', $selectedDate)
                ->groupBy('jadwal_id')
                ->pluck('total', 'jadwal_id');

            $sessions = Jadwal::query()
                ->with(['mata_kuliah:id,nama_mk,kode_mk', 'kelas:id,nama_kelas', 'dosen:id,name'])
                ->where('hari', $dayName)
                ->orderBy('jam_mulai')
                ->get()
                ->map(function (Jadwal $jadwal) use ($attendancePerSession, $selectedDateCarbon, $now): array {
                    $phase = $this->determineSessionPhase(
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
                ->select(['id', 'mahasiswa_id', 'jadwal_id', 'tanggal', 'waktu_tap', 'metode_absensi', 'status', 'created_at'])
                ->whereDate('tanggal', $selectedDate);

            if ($normalizedJadwalId) {
                $recordsQuery->where('jadwal_id', $normalizedJadwalId);
            }

            $liveStream = $recordsQuery
                ->orderByDesc('created_at')
                ->limit(30)
                ->get();

            $todayTotal = Absensi::query()
                ->whereDate('tanggal', $selectedDate)
                ->count();

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
                    'time' => optional($item->created_at)->format('H:i:s') ?? '-',
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
                $attendedMahasiswaIds = collect($records)
                    ->pluck('id')
                    ->filter()
                    ->isNotEmpty()
                    ? $liveStream->pluck('mahasiswa_id')->filter()->values()->all()
                    : [];

                $pendingRows = Mahasiswa::query()
                    ->select(['id', 'nama', 'nim'])
                    ->where('kelas_id', (int) $selectedSession['kelas_id'])
                    ->when(!empty($attendedMahasiswaIds), function ($query) use ($attendedMahasiswaIds) {
                        $query->whereNotIn('id', $attendedMahasiswaIds);
                    })
                    ->orderBy('nama')
                    ->get()
                    ->map(function (Mahasiswa $mahasiswa) use ($selectedSession, $selectedDate, $normalizedJadwalId): array {
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
                            'status' => 'Pending',
                            'is_pending' => true,
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

    private function determineSessionPhase(
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

    private function sessionPhaseLabel(string $phase): string
    {
        return match ($phase) {
            'completed' => 'Selesai',
            'ongoing' => 'Sedang Berlangsung',
            default => 'Akan Datang',
        };
    }

    private function forgetLiveCache(string $selectedDate, int $jadwalId): void
    {
        Cache::forget(sprintf('monitoring.live.payload.%s.%s', $selectedDate, 'all'));
        Cache::forget(sprintf('monitoring.live.payload.%s.%d', $selectedDate, $jadwalId));
    }
}
