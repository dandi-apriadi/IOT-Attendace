<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\SemesterAkademik;
use App\Models\PerformanceMetric;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        $requestStart = microtime(true);
        $ctx = $this->buildContext($request);

        $query = $this->buildStudentStatsQuery($ctx)
            ->groupBy('m.id', 'm.nama')
            ->orderByDesc('hadir')
            ->orderBy('m.nama');

        $outlierRows = $this->buildStudentStatsQuery($ctx)
            ->groupBy('m.id', 'm.nama')
            ->get();
        $outlierRows->transform(function ($row) {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;
            $row->persentase = $total > 0 ? round(($hadir / $total) * 100, 2) : 0;
            return $row;
        });
        $outlierStudent = $outlierRows
            ->where('total', '>', 0)
            ->sortBy('persentase')
            ->first();
        $lowestStudents = $outlierRows
            ->where('total', '>', 0)
            ->sortBy('persentase')
            ->take(3)
            ->values();
        $warningStudentsCount = $ctx['warningThreshold'] !== null
            ? $outlierRows->where('total', '>', 0)->filter(fn ($row) => (float) $row->persentase < (float) $ctx['warningThreshold'])->count()
            : 0;

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
                'period_month' => $ctx['periodStart']->format('Y-m'),
                'kelas_id' => $ctx['kelasId'] !== '' ? (int) $ctx['kelasId'] : null,
                'mata_kuliah_id' => $ctx['mataKuliahId'] !== '' ? (int) $ctx['mataKuliahId'] : null,
                'user_id' => $request->user()?->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('performance metric write failed', [
                'endpoint' => 'reports.index',
                'error' => $e->getMessage(),
            ]);
        }

        $semesterCards = $this->buildSemesterCards($ctx['semesterList'], (int) $ctx['selectedSemesterId'] ?: null, $ctx);
        $courseCards = $ctx['selectedSemester'] !== null
            ? $this->buildCourseCards($ctx['selectedSemester'], $ctx['periodStart'], $ctx['periodEndExclusive'], (int) ($ctx['mataKuliahId'] !== '' ? $ctx['mataKuliahId'] : 0) ?: null, $ctx)
            : [];
        $classCards = ($ctx['selectedSemester'] !== null && $ctx['mataKuliahId'] !== '')
            ? $this->buildClassCards($ctx['selectedSemester'], (int) $ctx['mataKuliahId'], $ctx['periodStart'], $ctx['periodEndExclusive'], (int) ($ctx['kelasId'] !== '' ? $ctx['kelasId'] : 0) ?: null, $ctx)
            : [];

        return view('reports.index', [
            'stats' => $stats,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahList' => MataKuliah::orderBy('nama_mk')->get(),
            'semesterList' => $ctx['semesterList'],
            'semesterCards' => $semesterCards,
            'courseCards' => $courseCards,
            'classCards' => $classCards,
            'selectedKelasId' => $ctx['kelasId'],
            'selectedMataKuliahId' => $ctx['mataKuliahId'],
            'selectedSemesterId' => $ctx['selectedSemesterId'],
            'selectedSemester' => $ctx['selectedSemester'],
            'selectedStatusFilter' => $ctx['selectedStatusFilter'],
            'statusFilterOptions' => $ctx['statusFilterOptions'],
            'selectedStatusLabel' => $ctx['selectedStatusLabel'],
            'selectedWarningThreshold' => $ctx['selectedWarningThreshold'],
            'warningThreshold' => $ctx['warningThreshold'],
            'warningThresholdOptions' => $ctx['warningThresholdOptions'],
            'outlierStudent' => $outlierStudent,
            'lowestStudents' => $lowestStudents,
            'warningStudentsCount' => $warningStudentsCount,
            'reportStatusLabels' => $ctx['reportStatusLabels'],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $ctx = $this->buildContext($request);
        $selectedMataKuliahLabel = $ctx['mataKuliahId'] !== '' ? (MataKuliah::find((int) $ctx['mataKuliahId'])?->nama_mk ?? '-') : 'Semua Mata Kuliah';
        $selectedKelasLabel = $ctx['kelasId'] !== '' ? (Kelas::find((int) $ctx['kelasId'])?->nama_kelas ?? '-') : 'Semua Kelas';
        $selectedSemesterLabel = $ctx['selectedSemester']?->display_name ?? 'Semua Semester';
        $selectedStatusLabel = $ctx['selectedStatusLabel'];

        $rows = $this->buildStudentStatsQuery($ctx)
            ->groupBy('m.id', 'm.nama')
            ->orderByDesc('hadir')
            ->orderBy('m.nama')
            ->get();

        $rows = $rows->map(function ($row) {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;
            $row->persentase = $total > 0 ? round(($hadir / $total) * 100, 2) : 0;
            return $row;
        });
        $summary = $this->buildExportSummary($rows);

        $filename = $this->buildExportFilename('xlsx', [
            $selectedSemesterLabel,
            $selectedMataKuliahLabel,
            $selectedKelasLabel,
            $selectedStatusLabel,
        ]);

        return response()->streamDownload(function () use ($rows, $summary, $selectedSemesterLabel, $selectedMataKuliahLabel, $selectedKelasLabel, $selectedStatusLabel): void {
            $sheet = (new Spreadsheet())->getActiveSheet();

            $sheet->setCellValue('A1', 'Semester');
            $sheet->setCellValue('B1', $selectedSemesterLabel);
            $sheet->setCellValue('A2', 'Mata Kuliah');
            $sheet->setCellValue('B2', $selectedMataKuliahLabel);
            $sheet->setCellValue('A3', 'Kelas');
            $sheet->setCellValue('B3', $selectedKelasLabel);
            $sheet->setCellValue('A4', 'Status');
            $sheet->setCellValue('B4', $selectedStatusLabel);
            $sheet->setCellValue('A5', 'Ringkasan');
            $sheet->setCellValue('B5', sprintf(
                'Mahasiswa: %d | Total Pertemuan: %d | Hadir: %d | Rata-rata Kehadiran: %.2f%%',
                $summary['total_mahasiswa'],
                $summary['total_pertemuan'],
                $summary['total_hadir'],
                $summary['rata_rata_persentase']
            ));

            $headerRow = 7;
            $sheet->fromArray(['Mahasiswa', 'Total', 'Hadir', 'Sakit/Izin', 'Alpa', 'Persentase'], null, "A{$headerRow}");

            $rowNum = $headerRow + 1;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    $row->nama,
                    (int) $row->total,
                    (int) $row->hadir,
                    (int) $row->sakit_izin,
                    (int) $row->alpa,
                    number_format((float) $row->persentase, 2) . '%',
                ], null, "A{$rowNum}");
                $rowNum++;
            }

            foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($sheet->getParent());
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $ctx = $this->buildContext($request);
        $selectedMataKuliahLabel = $ctx['mataKuliahId'] !== '' ? (MataKuliah::find((int) $ctx['mataKuliahId'])?->nama_mk ?? '-') : 'Semua Mata Kuliah';
        $selectedKelasLabel = $ctx['kelasId'] !== '' ? (Kelas::find((int) $ctx['kelasId'])?->nama_kelas ?? '-') : 'Semua Kelas';
        $selectedSemesterLabel = $ctx['selectedSemester']?->display_name ?? 'Semua Semester';
        $selectedStatusLabel = $ctx['selectedStatusLabel'];

        $rows = $this->buildStudentStatsQuery($ctx)
            ->groupBy('m.id', 'm.nama')
            ->orderByDesc('hadir')
            ->orderBy('m.nama')
            ->get();

        $rows = $rows->map(function ($row) {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;
            $row->persentase = $total > 0 ? round(($hadir / $total) * 100, 2) : 0;
            return $row;
        });
        $summary = $this->buildExportSummary($rows);

        $filename = $this->buildExportFilename('pdf', [
            $selectedSemesterLabel,
            $selectedMataKuliahLabel,
            $selectedKelasLabel,
            $selectedStatusLabel,
        ]);

        return Pdf::loadView('reports.summary-pdf', [
            'rows' => $rows,
            'summary' => $summary,
            'selectedSemesterLabel' => $selectedSemesterLabel,
            'selectedMataKuliahLabel' => $selectedMataKuliahLabel,
            'selectedKelasLabel' => $selectedKelasLabel,
            'selectedStatusLabel' => $selectedStatusLabel,
            'generatedAt' => now()->format('d-m-Y H:i:s'),
        ])->setPaper('a4', 'portrait')->download($filename);
    }

    private function buildContext(Request $request): array
    {
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

        $statusFilterOptions = [
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => 'present', 'label' => $reportStatusLabels['hadir']],
            ['value' => 'excused', 'label' => $reportStatusLabels['sakit_izin']],
            ['value' => 'absent', 'label' => $reportStatusLabels['alpa']],
        ];
        $selectedStatusFilter = (string) $request->query('status_filter', '');
        $selectedStatusLabel = collect($statusFilterOptions)->firstWhere('value', $selectedStatusFilter)['label'] ?? 'Semua Status';
        $warningThresholdOptions = ['' => 'Tanpa Threshold', '60' => '< 60%', '70' => '< 70%', '75' => '< 75%', '80' => '< 80%'];
        $selectedWarningThreshold = (string) $request->query('warning_threshold', '');
        $warningThreshold = null;
        if ($selectedWarningThreshold !== '' && isset($warningThresholdOptions[$selectedWarningThreshold])) {
            $warningThreshold = (float) $selectedWarningThreshold;
        }

        $semesterList = SemesterAkademik::orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->get();

        $semesterId = (string) $request->query('semester_id', '');
        $defaultSemester = $semesterList->firstWhere('is_active', true) ?? $semesterList->first();
        $selectedSemesterId = $semesterId !== '' ? $semesterId : (string) ($defaultSemester?->id ?? '');
        $selectedSemester = $selectedSemesterId !== ''
            ? $semesterList->firstWhere('id', (int) $selectedSemesterId)
            : null;

        if ($selectedSemester?->tanggal_mulai && $selectedSemester?->tanggal_selesai) {
            $periodStart = CarbonImmutable::parse($selectedSemester->tanggal_mulai)->startOfDay();
            $periodEndExclusive = CarbonImmutable::parse($selectedSemester->tanggal_selesai)->addDay()->startOfDay();
        } else {
            $month = (string) $request->query('month', now()->format('Y-m'));
            [$year, $monthNum] = $this->parseMonth($month);
            $periodStart = CarbonImmutable::create($year, $monthNum, 1)->startOfDay();
            $periodEndExclusive = $periodStart->addMonth();
        }

        return [
            'presentStatuses' => $presentStatuses,
            'excusedStatuses' => $excusedStatuses,
            'absentStatus' => $absentStatus,
            'reportStatusLabels' => $reportStatusLabels,
            'semesterList' => $semesterList,
            'selectedSemesterId' => $selectedSemesterId,
            'selectedSemester' => $selectedSemester,
            'periodStart' => $periodStart,
            'periodEndExclusive' => $periodEndExclusive,
            'kelasId' => (string) $request->query('kelas_id', ''),
            'mataKuliahId' => (string) $request->query('mata_kuliah_id', ''),
            'selectedStatusFilter' => $selectedStatusFilter,
            'selectedStatusLabel' => $selectedStatusLabel,
            'statusFilterOptions' => $statusFilterOptions,
            'selectedWarningThreshold' => $selectedWarningThreshold,
            'warningThreshold' => $warningThreshold,
            'warningThresholdOptions' => $warningThresholdOptions,
        ];
    }

    private function buildStudentStatsQuery(array $ctx)
    {
        $presentPlaceholders = implode(', ', array_fill(0, count($ctx['presentStatuses']), '?'));
        $excusedPlaceholders = implode(', ', array_fill(0, count($ctx['excusedStatuses']), '?'));

        $query = DB::table('absensi as a')
            ->join('mahasiswa as m', 'm.id', '=', 'a.mahasiswa_id')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('a.tanggal', '>=', $ctx['periodStart']->toDateString())
            ->where('a.tanggal', '<', $ctx['periodEndExclusive']->toDateString())
            ->select([
                'm.id',
                'm.nama',
                DB::raw('COUNT(*) AS total'),
            ])
            ->selectRaw(
                "SUM(CASE WHEN a.status IN ($presentPlaceholders) THEN 1 ELSE 0 END) AS hadir",
                $ctx['presentStatuses']
            )
            ->selectRaw(
                "SUM(CASE WHEN a.status IN ($excusedPlaceholders) THEN 1 ELSE 0 END) AS sakit_izin",
                $ctx['excusedStatuses']
            )
            ->selectRaw(
                'SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS alpa',
                [$ctx['absentStatus']]
            );

        if ($ctx['kelasId'] !== '') {
            $query->where('j.kelas_id', $ctx['kelasId']);
        }

        if ($ctx['mataKuliahId'] !== '') {
            $query->where('j.mata_kuliah_id', $ctx['mataKuliahId']);
        }

        if ($ctx['selectedSemester'] !== null) {
            $query->where('j.semester_akademik_id', $ctx['selectedSemester']->id);
        }

        $this->applyStatusFilterToAbsensiQuery($query, $ctx, 'a.status');

        return $query;
    }

    private function buildSemesterCards($semesterList, ?int $selectedSemesterId = null, ?array $ctx = null): array
    {
        return $semesterList->map(function (SemesterAkademik $semester) use ($selectedSemesterId, $ctx): array {
            $rangeStart = $semester->tanggal_mulai?->toDateString() ?? now()->toDateString();
            $rangeEnd = $semester->tanggal_selesai?->toDateString() ?? now()->toDateString();

            $baseQuery = DB::table('absensi as a')
                ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
                ->whereBetween('a.tanggal', [$rangeStart, $rangeEnd]);

            if ($ctx !== null) {
                $this->applyStatusFilterToAbsensiQuery($baseQuery, $ctx, 'a.status');
            }

            $totalAbsensi = (clone $baseQuery)->count();
            $totalMataKuliah = (clone $baseQuery)->distinct()->count('j.mata_kuliah_id');
            $totalKelas = (clone $baseQuery)->distinct()->count('j.kelas_id');

            return [
                'id' => $semester->id,
                'label' => $semester->display_name,
                'is_active' => (bool) $semester->is_active,
                'is_selected' => $selectedSemesterId !== null && $selectedSemesterId === (int) $semester->id,
                'total_absensi' => $totalAbsensi,
                'total_mata_kuliah' => $totalMataKuliah,
                'total_kelas' => $totalKelas,
            ];
        })->values()->all();
    }

    private function buildCourseCards(SemesterAkademik $semester, CarbonImmutable $periodStart, CarbonImmutable $periodEndExclusive, ?int $selectedMataKuliahId = null, ?array $ctx = null): array
    {
        $query = DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->join('mata_kuliah as mk', 'mk.id', '=', 'j.mata_kuliah_id')
            ->where('j.semester_akademik_id', $semester->id)
            ->where('a.tanggal', '>=', $periodStart->toDateString())
            ->where('a.tanggal', '<', $periodEndExclusive->toDateString())
            ->select([
                'mk.id',
                'mk.kode_mk',
                'mk.nama_mk',
            ])
            ->selectRaw('COUNT(*) as total_absensi')
            ->selectRaw('COUNT(DISTINCT j.kelas_id) as total_kelas');

        if ($ctx !== null) {
            $this->applyStatusFilterToAbsensiQuery($query, $ctx, 'a.status');
        }

        return $query
            ->groupBy('mk.id', 'mk.kode_mk', 'mk.nama_mk')
            ->orderBy('mk.nama_mk')
            ->get()
            ->map(function ($row) use ($selectedMataKuliahId): array {
                return [
                    'id' => (int) $row->id,
                    'kode_mk' => (string) $row->kode_mk,
                    'nama_mk' => (string) $row->nama_mk,
                    'total_absensi' => (int) $row->total_absensi,
                    'total_kelas' => (int) $row->total_kelas,
                    'is_selected' => $selectedMataKuliahId !== null && $selectedMataKuliahId === (int) $row->id,
                ];
            })
            ->values()
            ->all();
    }

    private function buildClassCards(SemesterAkademik $semester, int $mataKuliahId, CarbonImmutable $periodStart, CarbonImmutable $periodEndExclusive, ?int $selectedKelasId = null, ?array $ctx = null): array
    {
        $query = DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->where('j.semester_akademik_id', $semester->id)
            ->where('j.mata_kuliah_id', $mataKuliahId)
            ->where('a.tanggal', '>=', $periodStart->toDateString())
            ->where('a.tanggal', '<', $periodEndExclusive->toDateString())
            ->select([
                'k.id',
                'k.nama_kelas',
            ])
            ->selectRaw('COUNT(*) as total_absensi');

        if ($ctx !== null) {
            $this->applyStatusFilterToAbsensiQuery($query, $ctx, 'a.status');
        }

        return $query
            ->groupBy('k.id', 'k.nama_kelas')
            ->orderBy('k.nama_kelas')
            ->get()
            ->map(function ($row) use ($selectedKelasId): array {
                return [
                    'id' => (int) $row->id,
                    'nama_kelas' => (string) $row->nama_kelas,
                    'total_absensi' => (int) $row->total_absensi,
                    'is_selected' => $selectedKelasId !== null && $selectedKelasId === (int) $row->id,
                ];
            })
            ->values()
            ->all();
    }

    private function applyStatusFilterToAbsensiQuery($query, array $ctx, string $statusColumn): void
    {
        $selectedStatusFilter = (string) ($ctx['selectedStatusFilter'] ?? '');

        if ($selectedStatusFilter === 'present') {
            $query->whereIn($statusColumn, $ctx['presentStatuses']);
        } elseif ($selectedStatusFilter === 'excused') {
            $query->whereIn($statusColumn, $ctx['excusedStatuses']);
        } elseif ($selectedStatusFilter === 'absent') {
            $query->where($statusColumn, $ctx['absentStatus']);
        }
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

    private function buildExportFilename(string $extension, array $labels): string
    {
        $parts = ['laporan-presensi'];

        foreach ($labels as $label) {
            $normalized = trim((string) $label);
            if ($normalized === '' || str_starts_with(strtolower($normalized), 'semua ')) {
                continue;
            }

            $slug = Str::slug($normalized, '-');
            if ($slug !== '') {
                $parts[] = $slug;
            }
        }

        $parts[] = now()->format('Ymd_His');

        return implode('_', $parts) . '.' . $extension;
    }

    private function buildExportSummary($rows): array
    {
        $totalMahasiswa = (int) $rows->count();
        $totalPertemuan = (int) $rows->sum(fn ($row) => (int) $row->total);
        $totalHadir = (int) $rows->sum(fn ($row) => (int) $row->hadir);
        $rataRataPersentase = $totalMahasiswa > 0
            ? round((float) $rows->avg(fn ($row) => (float) $row->persentase), 2)
            : 0.0;

        return [
            'total_mahasiswa' => $totalMahasiswa,
            'total_pertemuan' => $totalPertemuan,
            'total_hadir' => $totalHadir,
            'rata_rata_persentase' => $rataRataPersentase,
        ];
    }
}
