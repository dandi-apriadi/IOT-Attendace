<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Concerns\ScopesByDosen;
use App\Http\Resources\AbsensiResource;
use App\Http\Resources\DeviceResource;
use App\Models\Absensi;
use App\Models\Device;
use App\Models\Jadwal;
use App\Models\SemesterAkademik;
use App\Services\AttendanceSessionService;
use App\Services\DeviceCommandService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    use ScopesByDosen;

    public function __construct(private readonly AttendanceSessionService $attendanceSessions)
    {
    }

    public function live(Request $request): JsonResponse
    {
        $date = $this->normalizeDate((string) $request->query('date', ''));
        $jadwalId = $request->query('jadwal_id');
        $kelasId = $request->query('kelas_id');

        $payload = $this->attendanceSessions->buildLivePayload(
            $date,
            $jadwalId,
            $kelasId,
            $this->allowedMataKuliahIds($request)
        );

        return response()->json($payload);
    }

    public function devices(Request $request): JsonResponse
    {
        $onlineThreshold = now()->subMinutes(5);

        $devices = Device::query()
            ->orderByDesc('is_active')
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(function (Device $device) use ($onlineThreshold): Device {
                if (! $device->is_active) {
                    $device->computed_status = 'disabled';
                    $device->computed_status_label = 'Disabled';
                } elseif ($device->last_seen_at && $device->last_seen_at->gte($onlineThreshold)) {
                    $device->computed_status = 'online';
                    $device->computed_status_label = 'Online';
                } elseif ($device->last_seen_at) {
                    $device->computed_status = 'stale';
                    $device->computed_status_label = 'Stale';
                } else {
                    $device->computed_status = 'unknown';
                    $device->computed_status_label = 'Unknown';
                }

                return $device;
            });

        return response()->json([
            'data' => DeviceResource::collection($devices),
            'meta' => [
                'total' => $devices->count(),
                'online' => $devices->where('computed_status', 'online')->count(),
                'offline' => $devices->whereIn('computed_status', ['stale', 'unknown'])->count(),
                'disabled' => $devices->where('computed_status', 'disabled')->count(),
            ],
        ]);
    }

    public function pingDevice(Request $request, Device $device, DeviceCommandService $deviceCommandService): JsonResponse
    {
        if ($device->type !== 'zkteco' || empty($device->ip_address)) {
            return response()->json(['message' => 'Bukan perangkat ZKTeco atau IP tidak ada.'], 422);
        }

        $command = $deviceCommandService->enqueue($device, 'get_info', [], $request->user()?->id);

        return response()->json([
            'queued' => true,
            'command_id' => $command->id,
            'message' => 'Perintah cek koneksi dikirim ke agent lokal.',
        ]);
    }

    public function attendanceHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mahasiswa_id' => ['nullable', 'integer'],
            'kelas_id' => ['nullable', 'integer'],
            'jadwal_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Absensi::query()
            ->with(['mahasiswa:id,nama,nim', 'jadwal:id,kelas_id,mata_kuliah_id', 'jadwal.kelas:id,nama_kelas', 'jadwal.mata_kuliah:id,kode_mk,nama_mk']);

        $allowedMataKuliahIds = $this->allowedMataKuliahIds($request);
        if ($allowedMataKuliahIds !== null) {
            $scopedJadwalIds = Jadwal::query()->whereIn('mata_kuliah_id', $allowedMataKuliahIds)->pluck('id');
            $query->whereIn('jadwal_id', $scopedJadwalIds);
        }

        if (! empty($validated['mahasiswa_id'])) {
            $query->where('mahasiswa_id', $validated['mahasiswa_id']);
        }

        if (! empty($validated['jadwal_id'])) {
            $query->where('jadwal_id', $validated['jadwal_id']);
        } elseif (! empty($validated['kelas_id'])) {
            $query->whereHas('jadwal', fn ($q) => $q->where('kelas_id', $validated['kelas_id']));
        }

        if (! empty($validated['start_date'])) {
            $query->whereDate('tanggal', '>=', $validated['start_date']);
        }

        if (! empty($validated['end_date'])) {
            $query->whereDate('tanggal', '<=', $validated['end_date']);
        }

        $perPage = (int) ($validated['per_page'] ?? 20);

        $paginated = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_tap')
            ->paginate($perPage);

        return AbsensiResource::collection($paginated)->response();
    }

    public function dashboardSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $cacheKey = sprintf('mobile:dashboard:summary:%s:%d', $user->role, $user->id);

        $payload = Cache::remember($cacheKey, 30, function () use ($request) {
            $now = Carbon::now();
            $today = $now->copy()->startOfDay();
            $timeNow = $now->format('H:i:s');
            $dayNames = $this->attendanceSessions->dayNames($now);

            $allowedMataKuliahIds = $this->allowedMataKuliahIds($request);

            $hadirHariIniQuery = Absensi::whereDate('tanggal', $today);
            $sesiAktifQuery = Jadwal::whereIn('hari', $dayNames)
                ->where('jam_mulai', '<=', $timeNow)
                ->where('jam_selesai', '>=', $timeNow);

            if ($allowedMataKuliahIds !== null) {
                $scopedJadwalIds = Jadwal::whereIn('mata_kuliah_id', $allowedMataKuliahIds)->pluck('id');
                $hadirHariIniQuery->whereIn('jadwal_id', $scopedJadwalIds);
                $sesiAktifQuery->whereIn('mata_kuliah_id', $allowedMataKuliahIds);
            }

            $activeSemester = SemesterAkademik::query()
                ->where('is_active', true)
                ->orderByDesc('tanggal_mulai')
                ->first();

            return [
                'hadir_hari_ini' => $hadirHariIniQuery->count(),
                'sesi_aktif' => $sesiAktifQuery->count(),
                'device_aktif' => Device::where('is_active', true)->count(),
                'device_online' => Device::where('is_active', true)
                    ->where('last_seen_at', '>=', $now->copy()->subMinutes(5))
                    ->count(),
                'semester_aktif' => $activeSemester?->nama_semester,
                'generated_at' => $now->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }

    private function normalizeDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}
