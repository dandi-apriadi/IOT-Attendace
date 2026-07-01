<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Device;
use App\Models\Jadwal;
use App\Models\MataKuliahDosenAssignment;
use App\Models\SemesterAkademik;
use App\Services\DashboardChartService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardChartService $charts)
    {
    }

    public function index(\Illuminate\Http\Request $request): View
    {
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $timeNow = $now->format('H:i:s');

        $dayVariants = $this->charts->dayVariants($now);

        $hadirHariIni = Absensi::whereDate('tanggal', $today)->count();
        $sesiAktif = Jadwal::whereIn('hari', $dayVariants)
            ->where('jam_mulai', '<=', $timeNow)
            ->where('jam_selesai', '>=', $timeNow)
            ->count();

        $totalDeviceAktif = Device::where('is_active', true)->count();
        $activeSemester = SemesterAkademik::query()
            ->where('is_active', true)
            ->orderByDesc('tanggal_mulai')
            ->first()
            ?? SemesterAkademik::query()->orderByDesc('tanggal_mulai')->first();

        $latestAbsensi = Absensi::with(['mahasiswa', 'jadwal.mata_kuliah'])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_tap')
            ->limit(10)
            ->get();

        $recentDevices = Device::orderByDesc('last_seen_at')
            ->limit(5)
            ->get();

        $cacheTtlSeconds = 60;
        $authUser = $request->user();
        $role = (string) ($authUser?->role ?? 'guest');
        $userId = (int) ($authUser?->id ?? 0);

        $weeklyCacheKey = sprintf('dashboard:charts:%s:%d:admin_weekly', $role, $userId);
        $iotCacheKey = sprintf('dashboard:charts:%s:%d:admin_iot', $role, $userId);
        $classCacheKey = sprintf('dashboard:charts:%s:%d:dosen_class', $role, $userId);
        $courseCacheKey = sprintf('dashboard:charts:%s:%d:dosen_course', $role, $userId);

        $adminWeeklyChart = Cache::remember($weeklyCacheKey, $cacheTtlSeconds, function () use ($today) {
            return $this->charts->buildAdminWeeklyChart($today);
        });

        $adminIotChart = Cache::remember($iotCacheKey, $cacheTtlSeconds, function () use ($now) {
            return $this->charts->buildAdminIotChart($now);
        });

        $dosenClassChart = Cache::remember($classCacheKey, $cacheTtlSeconds, function () use ($userId, $now) {
            return $this->charts->buildDosenClassParticipationChart($userId, $now);
        });

        $dosenCourseChart = Cache::remember($courseCacheKey, $cacheTtlSeconds, function () use ($userId, $now) {
            return $this->charts->buildDosenCoursePerformanceChart($userId, $now);
        });

        $dosenAssignedSchedules = collect();
        $assignedCourseIds = $role === 'dosen' && $userId > 0
            ? MataKuliahDosenAssignment::query()->where('user_id', $userId)->pluck('mata_kuliah_id')
            : collect();

        if ($role === 'dosen' && $userId > 0) {
            $dosenAssignedSchedules = Jadwal::with(['semesterAkademik', 'kelas', 'mata_kuliah'])
                ->whereIn('mata_kuliah_id', $assignedCourseIds)
                ->orderByDesc('semester_akademik_id')
                ->orderBy('mata_kuliah_id')
                ->orderBy('kelas_id')
                ->orderBy('hari')
                ->orderBy('jam_mulai')
                ->get()
                ->groupBy(fn (Jadwal $jadwal): string => $jadwal->semesterAkademik?->display_name ?? 'Belum ditentukan')
                ->map(function ($items, string $semesterLabel): array {
                    return [
                        'semester' => $semesterLabel,
                        'total' => $items->count(),
                        'items' => $items->take(4)->values(),
                    ];
                })
                ->values();
        }

        $dosenScheduleCount = $role === 'dosen' && $userId > 0
            ? Jadwal::whereIn('mata_kuliah_id', $assignedCourseIds)->count()
            : 0;

        return view('dashboard', [
            'hadirHariIni' => $hadirHariIni,
            'sesiAktif' => $sesiAktif,
            'totalDeviceAktif' => $totalDeviceAktif,
            'activeSemester' => $activeSemester,
            'dosenAssignedSchedules' => $dosenAssignedSchedules,
            'dosenScheduleCount' => $dosenScheduleCount,
            'latestAbsensi' => $latestAbsensi,
            'recentDevices' => $recentDevices,
            'adminWeeklyChart' => $adminWeeklyChart,
            'adminIotChart' => $adminIotChart,
            'dosenClassChart' => $dosenClassChart,
            'dosenCourseChart' => $dosenCourseChart,
        ]);
    }
}
