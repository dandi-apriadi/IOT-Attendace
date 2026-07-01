<?php

namespace App\Services;

use App\Models\Device;
use App\Models\MataKuliahDosenAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Absensi;

class DashboardChartService
{
    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function buildAdminWeeklyChart(Carbon $today): array
    {
        $startDate = $today->copy()->subDays(6)->toDateString();
        $endDate = $today->toDateString();

        $rows = Absensi::query()
            ->selectRaw('tanggal, COUNT(*) as total')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $labels = [];
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dateKey = $date->toDateString();
            $labels[] = $this->indoDayName($date);
            $data[] = (int) ($rows[$dateKey] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public function buildAdminIotChart(Carbon $now): array
    {
        $onlineThreshold = $now->copy()->subHours(12);

        $online = Device::query()
            ->where('is_active', true)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $onlineThreshold)
            ->count();

        $maintenance = Device::query()
            ->where('is_active', true)
            ->where(function ($query) use ($onlineThreshold) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', $onlineThreshold);
            })
            ->count();

        $offline = Device::query()
            ->where('is_active', false)
            ->count();

        return [
            'labels' => ['Online', 'Offline', 'Maintenance'],
            'data' => [$online, $offline, $maintenance],
        ];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    public function buildDosenClassParticipationChart(int $dosenId, Carbon $now): array
    {
        if ($dosenId <= 0) {
            return ['labels' => ['Belum ada data'], 'data' => [0]];
        }

        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();

        $presentStatuses = array_values(array_unique(array_merge(
            (array) config('attendance.absensi_present_statuses', ['Hadir']),
            ['Telat']
        )));
        $presentPlaceholders = implode(', ', array_fill(0, count($presentStatuses), '?'));

        $assignedCourseIds = MataKuliahDosenAssignment::query()
            ->where('user_id', $dosenId)
            ->pluck('mata_kuliah_id');

        if ($assignedCourseIds->isEmpty()) {
            return ['labels' => ['Belum ada data'], 'data' => [0]];
        }

        $rows = DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->whereIn('j.mata_kuliah_id', $assignedCourseIds)
            ->whereBetween('a.tanggal', [$start, $end])
            ->select('k.nama_kelas')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE WHEN a.status IN ($presentPlaceholders) THEN 1 ELSE 0 END) as hadir",
                $presentStatuses
            )
            ->groupBy('k.id', 'k.nama_kelas')
            ->orderBy('k.nama_kelas')
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => ['Belum ada data'], 'data' => [0]];
        }

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;
            $labels[] = (string) $row->nama_kelas;
            $data[] = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    public function buildDosenCoursePerformanceChart(int $dosenId, Carbon $now): array
    {
        if ($dosenId <= 0) {
            return ['labels' => ['Belum ada data'], 'data' => [0]];
        }

        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();

        $presentStatuses = array_values(array_unique(array_merge(
            (array) config('attendance.absensi_present_statuses', ['Hadir']),
            ['Telat']
        )));
        $presentPlaceholders = implode(', ', array_fill(0, count($presentStatuses), '?'));

        $assignedCourseIds = MataKuliahDosenAssignment::query()
            ->where('user_id', $dosenId)
            ->pluck('mata_kuliah_id');

        if ($assignedCourseIds->isEmpty()) {
            return ['labels' => ['Belum ada data'], 'data' => [0]];
        }

        $rows = DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->join('mata_kuliah as mk', 'mk.id', '=', 'j.mata_kuliah_id')
            ->whereIn('j.mata_kuliah_id', $assignedCourseIds)
            ->whereBetween('a.tanggal', [$start, $end])
            ->select('mk.nama_mk')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw(
                "SUM(CASE WHEN a.status IN ($presentPlaceholders) THEN 1 ELSE 0 END) as hadir",
                $presentStatuses
            )
            ->groupBy('mk.id', 'mk.nama_mk')
            ->orderBy('mk.nama_mk')
            ->limit(6)
            ->get();

        if ($rows->isEmpty()) {
            return ['labels' => ['Belum ada data'], 'data' => [0]];
        }

        $labels = [];
        $data = [];

        foreach ($rows as $row) {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;
            $labels[] = (string) $row->nama_mk;
            $data[] = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return array<int, string>
     */
    public function dayVariants(Carbon $date): array
    {
        $english = $date->format('l');

        $map = [
            'Monday' => ['Senin'],
            'Tuesday' => ['Selasa'],
            'Wednesday' => ['Rabu'],
            'Thursday' => ['Kamis'],
            'Friday' => ['Jumat'],
            'Saturday' => ['Sabtu'],
            'Sunday' => ['Minggu'],
        ];

        $variants = [$english, strtolower($english), strtoupper($english)];

        foreach (($map[$english] ?? []) as $id) {
            $variants[] = $id;
            $variants[] = strtolower($id);
            $variants[] = strtoupper($id);
        }

        return array_values(array_unique($variants));
    }

    public function indoDayName(Carbon $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
            default => $date->format('l'),
        };
    }
}
