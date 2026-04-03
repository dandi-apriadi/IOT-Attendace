<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\SemesterAkademik;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDetailController extends Controller
{
    public function show(Request $request, $id): View
    {
        $ctx = $this->buildContext($request, (int) $id);

        return view('master.student-detail', $ctx);
    }

    public function exportExcel(Request $request, $id): StreamedResponse
    {
        $ctx = $this->buildContext($request, (int) $id, false);
        $rows = $ctx['historyQuery']
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_tap')
            ->orderByDesc('created_at')
            ->get();

        $filename = $this->buildExportFilename('xlsx', [
            'riwayat-mahasiswa',
            $ctx['mahasiswa']->nim,
            $ctx['mahasiswa']->nama,
            $ctx['selectedSemesterLabel'] ?? '',
            $ctx['selectedMataKuliahLabel'] ?? '',
            $ctx['selectedKelasLabel'] ?? '',
            $ctx['selectedStartDate'] ?? '',
            $ctx['selectedEndDate'] ?? '',
        ]);

        return response()->streamDownload(function () use ($ctx, $rows): void {
            $sheet = (new Spreadsheet())->getActiveSheet();

            $sheet->setCellValue('A1', 'NIM');
            $sheet->setCellValue('B1', $ctx['mahasiswa']->nim);
            $sheet->setCellValue('A2', 'Nama');
            $sheet->setCellValue('B2', $ctx['mahasiswa']->nama);
            $sheet->setCellValue('A3', 'Semester');
            $sheet->setCellValue('B3', $ctx['selectedSemesterLabel'] ?? 'Semua Semester');
            $sheet->setCellValue('A4', 'Mata Kuliah');
            $sheet->setCellValue('B4', $ctx['selectedMataKuliahLabel'] ?? 'Semua Mata Kuliah');
            $sheet->setCellValue('A5', 'Kelas');
            $sheet->setCellValue('B5', $ctx['selectedKelasLabel'] ?? 'Semua Kelas');

            $periodLabel = ($ctx['selectedStartDate'] !== '' || $ctx['selectedEndDate'] !== '')
                ? (($ctx['selectedStartDate'] !== '' ? $ctx['selectedStartDate'] : '-') . ' s/d ' . ($ctx['selectedEndDate'] !== '' ? $ctx['selectedEndDate'] : '-'))
                : 'Semua Tanggal';
            $sheet->setCellValue('A6', 'Periode Tanggal');
            $sheet->setCellValue('B6', $periodLabel);

            $sheet->setCellValue('A7', 'Ringkasan');
            $sheet->setCellValue('B7', sprintf(
                'Total: %d | Hadir: %d | Sakit/Izin: %d | Alpa: %d | Persentase Hadir: %.1f%%',
                $ctx['totalAbsensi'],
                $ctx['hadirCount'],
                $ctx['sabitIzinCount'],
                $ctx['alpaCount'],
                $ctx['persentaseHadir']
            ));

            $headerRow = 9;
            $sheet->fromArray(['Tanggal', 'Waktu Tap', 'Mata Kuliah', 'Kode MK', 'Kelas', 'Status'], null, "A{$headerRow}");

            $rowNum = $headerRow + 1;
            foreach ($rows as $record) {
                $sheet->fromArray([
                    (string) $record->tanggal,
                    $record->waktu_tap ? substr((string) $record->waktu_tap, 0, 8) : '-',
                    $record->jadwal?->mataKuliah?->nama_mk ?? '-',
                    $record->jadwal?->mataKuliah?->kode_mk ?? '-',
                    $record->jadwal?->kelas?->nama_kelas ?? '-',
                    $ctx['statusLabels'][$record->status] ?? $record->status,
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

    public function exportPdf(Request $request, $id): Response
    {
        $ctx = $this->buildContext($request, (int) $id, false);
        $rows = $ctx['historyQuery']
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_tap')
            ->orderByDesc('created_at')
            ->get();

        $filename = $this->buildExportFilename('pdf', [
            'riwayat-mahasiswa',
            $ctx['mahasiswa']->nim,
            $ctx['mahasiswa']->nama,
            $ctx['selectedSemesterLabel'] ?? '',
            $ctx['selectedMataKuliahLabel'] ?? '',
            $ctx['selectedKelasLabel'] ?? '',
            $ctx['selectedStartDate'] ?? '',
            $ctx['selectedEndDate'] ?? '',
        ]);

        return Pdf::loadView('master.student-detail-pdf', [
            'mahasiswa' => $ctx['mahasiswa'],
            'rows' => $rows,
            'totalAbsensi' => $ctx['totalAbsensi'],
            'hadirCount' => $ctx['hadirCount'],
            'sabitIzinCount' => $ctx['sabitIzinCount'],
            'alpaCount' => $ctx['alpaCount'],
            'persentaseHadir' => $ctx['persentaseHadir'],
            'selectedSemesterLabel' => $ctx['selectedSemesterLabel'] ?? 'Semua Semester',
            'selectedMataKuliahLabel' => $ctx['selectedMataKuliahLabel'] ?? 'Semua Mata Kuliah',
            'selectedKelasLabel' => $ctx['selectedKelasLabel'] ?? 'Semua Kelas',
            'selectedStartDate' => $ctx['selectedStartDate'],
            'selectedEndDate' => $ctx['selectedEndDate'],
            'statusLabels' => $ctx['statusLabels'],
            'generatedAt' => now()->format('d-m-Y H:i:s'),
        ])->setPaper('a4', 'portrait')->download($filename);
    }

    private function buildContext(Request $request, int $id, bool $paginate = true): array
    {
        $presentStatuses = (array) config('attendance.absensi_present_statuses', ['Hadir']);
        $excusedStatuses = (array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']);
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');
        $statusLabels = (array) config('attendance.absensi_statuses', []);

        if ($presentStatuses === []) {
            $presentStatuses = ['Hadir'];
        }

        if ($excusedStatuses === []) {
            $excusedStatuses = ['Sakit', 'Izin'];
        }

        $mahasiswa = Mahasiswa::find($id);

        if (!$mahasiswa) {
            abort(404, 'Mahasiswa tidak ditemukan');
        }

        $selectedSemesterId = (string) $request->query('semester_id', '');
        $selectedMataKuliahId = (string) $request->query('mata_kuliah_id', '');
        $selectedKelasId = (string) $request->query('kelas_id', '');
        $selectedStartDate = (string) $request->query('start_date', '');
        $selectedEndDate = (string) $request->query('end_date', '');
        $selectedStatusFilter = (string) $request->query('status_filter', '');

        $baseQuery = $mahasiswa->absensi()
            ->with('jadwal.mata_kuliah', 'jadwal.kelas', 'jadwal.semesterAkademik');

        if ($selectedSemesterId !== '') {
            $baseQuery->whereHas('jadwal', function ($q) use ($selectedSemesterId) {
                $q->where('semester_akademik_id', (int) $selectedSemesterId);
            });
        }

        if ($selectedMataKuliahId !== '') {
            $baseQuery->whereHas('jadwal', function ($q) use ($selectedMataKuliahId) {
                $q->where('mata_kuliah_id', (int) $selectedMataKuliahId);
            });
        }

        if ($selectedKelasId !== '') {
            $baseQuery->whereHas('jadwal', function ($q) use ($selectedKelasId) {
                $q->where('kelas_id', (int) $selectedKelasId);
            });
        }

        if ($selectedStatusFilter === 'present') {
            $baseQuery->whereIn('status', $presentStatuses);
        } elseif ($selectedStatusFilter === 'excused') {
            $baseQuery->whereIn('status', $excusedStatuses);
        } elseif ($selectedStatusFilter === 'absent') {
            $baseQuery->where('status', $absentStatus);
        }

        if ($selectedStartDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedStartDate)) {
            $baseQuery->where('tanggal', '>=', $selectedStartDate);
        }

        if ($selectedEndDate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedEndDate)) {
            $baseQuery->where('tanggal', '<=', $selectedEndDate);
        }

        // Get attendance history
        $historyQuery = clone $baseQuery;
        $absensiHistory = $paginate
            ? $historyQuery->orderByDesc('tanggal')->orderByDesc('waktu_tap')->orderByDesc('created_at')->paginate(20)->withQueryString()
            : null;

        // Calculate attendance statistics
        $totalAbsensi = (clone $baseQuery)->count();
        $hadirCount = (clone $baseQuery)->whereIn('status', $presentStatuses)->count();
        $sabitIzinCount = (clone $baseQuery)->whereIn('status', $excusedStatuses)->count();
        $alpaCount = (clone $baseQuery)->where('status', $absentStatus)->count();
        $persentaseHadir = $totalAbsensi > 0 ? round(($hadirCount / $totalAbsensi) * 100, 1) : 0;

        // This month stats
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $thisMonthAbsensi = (clone $baseQuery)->whereBetween('tanggal', [$monthStart, $monthEnd])->count();
        $thisMonthHadir = (clone $baseQuery)->whereBetween('tanggal', [$monthStart, $monthEnd])->whereIn('status', $presentStatuses)->count();

        $statusTypeMap = [];
        foreach ($presentStatuses as $status) {
            $statusTypeMap[strtolower((string) $status)] = 'present';
        }
        foreach ($excusedStatuses as $status) {
            $statusTypeMap[strtolower((string) $status)] = 'excused';
        }
        $statusTypeMap[strtolower($absentStatus)] = 'absent';

        $reportQuery = array_filter([
            'semester_id' => $selectedSemesterId,
            'mata_kuliah_id' => $selectedMataKuliahId,
            'kelas_id' => $selectedKelasId,
            'start_date' => $selectedStartDate,
            'end_date' => $selectedEndDate,
            'status_filter' => $selectedStatusFilter,
            'month' => (string) $request->query('month', ''),
        ], static fn ($value) => (string) $value !== '');

        $selectedSemesterLabel = $selectedSemesterId !== ''
            ? (SemesterAkademik::find((int) $selectedSemesterId)?->display_name ?? 'Semester')
            : null;
        $selectedSemester = $selectedSemesterId !== ''
            ? SemesterAkademik::find((int) $selectedSemesterId)
            : null;
        $selectedMataKuliahLabel = $selectedMataKuliahId !== ''
            ? (MataKuliah::find((int) $selectedMataKuliahId)?->nama_mk ?? 'Mata Kuliah')
            : null;
        $selectedKelasLabel = $selectedKelasId !== ''
            ? (Kelas::find((int) $selectedKelasId)?->nama_kelas ?? 'Kelas')
            : null;

        $reportQueryWithoutDate = $reportQuery;
        unset($reportQueryWithoutDate['start_date'], $reportQueryWithoutDate['end_date']);

        $baseFilterQuery = array_merge(
            ['id' => $mahasiswa->id],
            $reportQueryWithoutDate,
            $request->query('from') === 'reports' ? ['from' => 'reports'] : []
        );

        $today = now();
        $quickDateRanges = [
            [
                'label' => '7 Hari',
                'query' => array_merge($baseFilterQuery, [
                    'start_date' => $today->copy()->subDays(6)->toDateString(),
                    'end_date' => $today->toDateString(),
                ]),
            ],
            [
                'label' => '30 Hari',
                'query' => array_merge($baseFilterQuery, [
                    'start_date' => $today->copy()->subDays(29)->toDateString(),
                    'end_date' => $today->toDateString(),
                ]),
            ],
        ];

        if ($selectedSemester?->tanggal_mulai && $selectedSemester?->tanggal_selesai) {
            $quickDateRanges[] = [
                'label' => 'Semester Aktif',
                'query' => array_merge($baseFilterQuery, [
                    'start_date' => Carbon::parse($selectedSemester->tanggal_mulai)->toDateString(),
                    'end_date' => Carbon::parse($selectedSemester->tanggal_selesai)->toDateString(),
                ]),
            ];
        }

        foreach ($quickDateRanges as &$preset) {
            $presetStart = (string) ($preset['query']['start_date'] ?? '');
            $presetEnd = (string) ($preset['query']['end_date'] ?? '');
            $preset['is_active'] = $selectedStartDate === $presetStart && $selectedEndDate === $presetEnd;
            $preset['url'] = route('student-detail', $preset['query']);
        }
        unset($preset);

        $weeklyTrend = $this->buildWeeklyTrend((clone $baseQuery)->get(['tanggal', 'status']), $presentStatuses);
        $trendInsight = [
            'delta' => 0.0,
            'direction' => 'flat',
            'text' => 'Belum ada pembanding tren minggu sebelumnya.',
        ];

        if (count($weeklyTrend) >= 2) {
            $last = (float) $weeklyTrend[count($weeklyTrend) - 1]['persentase'];
            $prev = (float) $weeklyTrend[count($weeklyTrend) - 2]['persentase'];
            $delta = round($last - $prev, 1);

            if ($delta > 0.1) {
                $trendInsight = [
                    'delta' => $delta,
                    'direction' => 'up',
                    'text' => 'Naik ' . number_format($delta, 1) . '% dibanding minggu sebelumnya.',
                ];
            } elseif ($delta < -0.1) {
                $trendInsight = [
                    'delta' => $delta,
                    'direction' => 'down',
                    'text' => 'Turun ' . number_format(abs($delta), 1) . '% dibanding minggu sebelumnya.',
                ];
            } else {
                $trendInsight = [
                    'delta' => $delta,
                    'direction' => 'flat',
                    'text' => 'Stabil dibanding minggu sebelumnya.',
                ];
            }
        }

        $statusFilterOptions = [
            ['value' => '', 'label' => 'Semua Status'],
            ['value' => 'present', 'label' => 'Hadir'],
            ['value' => 'excused', 'label' => 'Sakit/Izin'],
            ['value' => 'absent', 'label' => 'Alpa'],
        ];

        return [
            'mahasiswa' => $mahasiswa,
            'absensiHistory' => $absensiHistory,
            'historyQuery' => clone $baseQuery,
            'totalAbsensi' => $totalAbsensi,
            'hadirCount' => $hadirCount,
            'sabitIzinCount' => $sabitIzinCount,
            'alpaCount' => $alpaCount,
            'persentaseHadir' => $persentaseHadir,
            'thisMonthAbsensi' => $thisMonthAbsensi,
            'thisMonthHadir' => $thisMonthHadir,
            'statusTypeMap' => $statusTypeMap,
            'statusLabels' => $statusLabels,
            'reportBackUrl' => route('reports.index', $reportQuery),
            'hasReportContext' => (bool) ($request->query('from') === 'reports' || ! empty($reportQuery)),
            'selectedSemesterLabel' => $selectedSemesterLabel,
            'selectedMataKuliahLabel' => $selectedMataKuliahLabel,
            'selectedKelasLabel' => $selectedKelasLabel,
            'selectedStartDate' => $selectedStartDate,
            'selectedEndDate' => $selectedEndDate,
            'selectedStatusFilter' => $selectedStatusFilter,
            'statusFilterOptions' => $statusFilterOptions,
            'filtersQuery' => $reportQuery,
            'baseFilterQuery' => $baseFilterQuery,
            'quickDateRanges' => $quickDateRanges,
            'weeklyTrend' => $weeklyTrend,
            'trendInsight' => $trendInsight,
        ];
    }

    private function buildWeeklyTrend($rows, array $presentStatuses): array
    {
        $presentLookup = [];
        foreach ($presentStatuses as $status) {
            $presentLookup[strtolower((string) $status)] = true;
        }

        $buckets = [];

        foreach ($rows as $row) {
            $weekStart = Carbon::parse($row->tanggal)->startOfWeek()->toDateString();
            if (! isset($buckets[$weekStart])) {
                $buckets[$weekStart] = [
                    'week_start' => $weekStart,
                    'total' => 0,
                    'hadir' => 0,
                ];
            }

            $buckets[$weekStart]['total']++;
            if (isset($presentLookup[strtolower((string) $row->status)])) {
                $buckets[$weekStart]['hadir']++;
            }
        }

        ksort($buckets);
        $buckets = array_values($buckets);

        if (count($buckets) > 8) {
            $buckets = array_slice($buckets, -8);
        }

        return array_map(static function (array $bucket): array {
            $persentase = $bucket['total'] > 0
                ? round(($bucket['hadir'] / $bucket['total']) * 100, 1)
                : 0.0;

            return [
                'label' => Carbon::parse($bucket['week_start'])->format('d M'),
                'persentase' => $persentase,
                'total' => $bucket['total'],
                'hadir' => $bucket['hadir'],
            ];
        }, $buckets);
    }

    private function buildExportFilename(string $extension, array $parts): string
    {
        $normalizedParts = [];

        foreach ($parts as $part) {
            $slug = Str::slug(trim((string) $part), '-');
            if ($slug !== '') {
                $normalizedParts[] = $slug;
            }
        }

        $normalizedParts[] = now()->format('Ymd_His');

        return implode('_', $normalizedParts) . '.' . $extension;
    }
}
