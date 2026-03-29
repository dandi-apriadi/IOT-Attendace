<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\PerformanceMetric;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $requestStart = microtime(true);

        $presentStatuses = array_values((array) config('attendance.absensi_present_statuses', ['Hadir']));
        $excusedStatuses = array_values((array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']));
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        if ($presentStatuses === []) {
            $presentStatuses = ['Hadir'];
        }

        if ($excusedStatuses === []) {
            $excusedStatuses = ['Sakit', 'Izin'];
        }

        $statusLabels = (array) config('attendance.absensi_statuses', []);
        $labelFor = static function (string $status) use ($statusLabels): string {
            return (string) ($statusLabels[$status] ?? $status);
        };

        $reportStatusLabels = [
            'hadir' => implode('/', array_map($labelFor, $presentStatuses)),
            'sakit_izin' => implode('/', array_map($labelFor, $excusedStatuses)),
            'alpa' => $labelFor($absentStatus),
        ];

        $month = (string) $request->query('month', now()->format('Y-m'));
        [$year, $monthNum] = $this->parseMonth($month);
        $periodStart = CarbonImmutable::create($year, $monthNum, 1)->startOfDay();
        $periodEndExclusive = $periodStart->addMonth();

        $kelasId = (string) $request->query('kelas_id', '');
        $mataKuliahId = (string) $request->query('mata_kuliah_id', '');

        $presentPlaceholders = implode(', ', array_fill(0, count($presentStatuses), '?'));
        $excusedPlaceholders = implode(', ', array_fill(0, count($excusedStatuses), '?'));

        $query = DB::table('absensi as a')
            ->join('mahasiswa as m', 'm.id', '=', 'a.mahasiswa_id')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('a.tanggal', '>=', $periodStart->toDateString())
            ->where('a.tanggal', '<', $periodEndExclusive->toDateString())
            ->select([
                'm.id',
                'm.nama',
                DB::raw('COUNT(*) AS total'),
            ])
            ->selectRaw(
                "SUM(CASE WHEN a.status IN ($presentPlaceholders) THEN 1 ELSE 0 END) AS hadir",
                $presentStatuses
            )
            ->selectRaw(
                "SUM(CASE WHEN a.status IN ($excusedPlaceholders) THEN 1 ELSE 0 END) AS sakit_izin",
                $excusedStatuses
            )
            ->selectRaw(
                'SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS alpa',
                [$absentStatus]
            )
            ->groupBy('m.id', 'm.nama')
            ->orderByDesc('hadir')
            ->orderBy('m.nama');

        if ($kelasId !== '') {
            $query->where('j.kelas_id', $kelasId);
        }

        if ($mataKuliahId !== '') {
            $query->where('j.mata_kuliah_id', $mataKuliahId);
        }

        $queryStart = microtime(true);
        $stats = $query->paginate(25)->withQueryString();
        $queryDurationMs = (microtime(true) - $queryStart) * 1000;

        $stats->getCollection()->transform(function ($row) {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;
            $row->persentase = $total > 0 ? round(($hadir / $total) * 100, 2) : 0;
            return $row;
        });

        $totalDurationMs = (microtime(true) - $requestStart) * 1000;

        try {
            PerformanceMetric::create([
                'endpoint' => 'reports.index',
                'query_duration_ms' => round($queryDurationMs, 3),
                'total_duration_ms' => round($totalDurationMs, 3),
                'result_count' => $stats->count(),
                'page' => (int) $stats->currentPage(),
                'period_month' => sprintf('%04d-%02d', $year, $monthNum),
                'kelas_id' => $kelasId !== '' ? (int) $kelasId : null,
                'mata_kuliah_id' => $mataKuliahId !== '' ? (int) $mataKuliahId : null,
                'user_id' => $request->user()?->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('performance metric write failed', [
                'endpoint' => 'reports.index',
                'error' => $e->getMessage(),
            ]);
        }

        return view('reports.index', [
            'stats' => $stats,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahList' => MataKuliah::orderBy('nama_mk')->get(),
            'selectedKelasId' => $kelasId,
            'selectedMataKuliahId' => $mataKuliahId,
            'selectedMonth' => sprintf('%04d-%02d', $year, $monthNum),
            'reportStatusLabels' => $reportStatusLabels,
        ]);
    }

    private function parseMonth(string $month): array
    {
        if (preg_match('/^(\\d{4})-(\\d{2})$/', $month, $m)) {
            $year = (int) $m[1];
            $monthNum = (int) $m[2];

            if ($year >= 2000 && $year <= 2100 && $monthNum >= 1 && $monthNum <= 12) {
                return [$year, $monthNum];
            }
        }

        return [(int) now()->format('Y'), (int) now()->format('m')];
    }
}
