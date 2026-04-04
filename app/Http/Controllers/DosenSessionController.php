<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MataKuliahDosenAssignment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DosenSessionController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('dosen-courses')
            ->with('info', 'Sesi presensi dibuka langsung dari jadwal pada halaman Mata Kuliah Saya.');
    }

    public function courses(Request $request): View
    {
        $user = $request->user();
        $assignedCourseIds = $this->assignedCourseIds((int) ($user?->id ?? 0));

        $activeSession = Cache::get('active_attendance_session');

        // Auto-close: only close if the specific jadwal's time has passed on its scheduled day
        if ($activeSession) {
            $now = now();
            $jadwalId = $activeSession['jadwal_id'] ?? null;

            if ($jadwalId) {
                $jadwal = Jadwal::find($jadwalId);

                if ($jadwal) {
                    $currentDayNames = $this->dayNames($now);
                    $jamSelesai = Carbon::parse($jadwal->jam_selesai);

                    // Only auto-close if today is the scheduled day AND time has passed
                    $isScheduledDay = in_array($jadwal->hari, $currentDayNames);
                    $isPastEndTime = $now->gt($jamSelesai);

                    if ($isScheduledDay && $isPastEndTime) {
                        Cache::forget('active_attendance_session');
                        $activeSession = null;
                    }
                } else {
                    Cache::forget('active_attendance_session');
                    $activeSession = null;
                }
            } else {
                // Legacy session without jadwal_id - close if any matching schedule has passed
                $jadwal = Jadwal::query()
                    ->where('mata_kuliah_id', $activeSession['mata_kuliah_id'])
                    ->where('kelas_id', $activeSession['kelas_id'])
                    ->first();

                if ($jadwal) {
                    $currentDayNames = $this->dayNames($now);
                    $jamSelesai = Carbon::parse($jadwal->jam_selesai);
                    $isScheduledDay = in_array($jadwal->hari, $currentDayNames);
                    $isPastEndTime = $now->gt($jamSelesai);

                    if ($isScheduledDay && $isPastEndTime) {
                        Cache::forget('active_attendance_session');
                        $activeSession = null;
                    }
                }
            }
        }

        // Auto-open: if no active session but a schedule is currently in its time window, open it
        if (! $activeSession) {
            $now = now();
            $currentTime = $now->format('H:i:s');
            $dayNames = $this->dayNames($now);

            $autoOpenJadwal = Jadwal::query()
                ->with(['semesterAkademik', 'kelas', 'mata_kuliah'])
                ->whereIn('hari', $dayNames)
                ->where('jam_mulai', '<=', $currentTime)
                ->where('jam_selesai', '>=', $currentTime)
                ->when($user?->role !== 'admin', function ($builder) use ($user): void {
                    $courseIds = $this->assignedCourseIds((int) ($user?->id ?? 0));
                    if ($courseIds === []) {
                        $builder->whereRaw('1 = 0');
                    } else {
                        $builder->whereIn('mata_kuliah_id', $courseIds);
                    }
                })
                ->orderBy('jam_mulai')
                ->first();

            if ($autoOpenJadwal) {
                $activeSession = [
                    'mata_kuliah_id' => $autoOpenJadwal->mata_kuliah_id,
                    'kelas_id' => $autoOpenJadwal->kelas_id,
                    'jadwal_id' => $autoOpenJadwal->id,
                    'started_at' => now()->toDateTimeString(),
                    'user_id' => $user?->id,
                    'source' => 'auto_schedule',
                ];

                Cache::put('active_attendance_session', $activeSession, now()->addHours(3));
            }
        }

        $query = Jadwal::with(['semesterAkademik', 'kelas', 'mata_kuliah'])
            ->when($user?->role !== 'admin', function ($builder) use ($user): void {
                $courseIds = $this->assignedCourseIds((int) ($user?->id ?? 0));
                if ($courseIds === []) {
                    $builder->whereRaw('1 = 0');
                } else {
                    $builder->whereIn('mata_kuliah_id', $courseIds);
                }
            })
            ->orderByDesc('semester_akademik_id')
            ->orderBy('mata_kuliah_id')
            ->orderBy('kelas_id')
            ->orderBy('hari')
            ->orderBy('jam_mulai');

        $groupedSchedules = $query->get()
            ->groupBy(function (Jadwal $jadwal): string {
                return $jadwal->semesterAkademik?->display_name ?? 'Belum ditentukan';
            })
            ->map(function ($items, string $semesterLabel): array {
                return [
                    'semester' => $semesterLabel,
                    'total' => $items->count(),
                    'items' => $items->values(),
                ];
            })
            ->values();

        return view('dosen.courses', [
            'groupedSchedules' => $groupedSchedules,
            'todayDate' => now()->toDateString(),
            'assignedCourseIds' => $assignedCourseIds,
            'activeSession' => $activeSession,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliah,id',
            'kelas_id' => 'required|exists:kelas,id',
        ]);

        $user = $request->user();

        if ($user?->role !== 'admin') {
            $isOwner = MataKuliahDosenAssignment::query()
                ->where('mata_kuliah_id', (int) $data['mata_kuliah_id'])
                ->where('user_id', (int) $user?->id)
                ->exists();

            if (! $isOwner) {
                return redirect()->route('dosen-courses')
                    ->with('error', 'Sesi hanya bisa dibuka untuk mata kuliah yang Anda ampu.');
            }
        }

        $jadwalQuery = Jadwal::query()
            ->where('mata_kuliah_id', $data['mata_kuliah_id'])
            ->where('kelas_id', $data['kelas_id']);

        $hasAssignedSchedule = $jadwalQuery->exists();
        if (! $hasAssignedSchedule) {
            return redirect()->route('dosen-courses')
                ->with('error', 'Sesi hanya bisa dibuka untuk jadwal yang ditetapkan kepada dosen.');
        }

        // Find the specific jadwal for today's day
        $now = now();
        $dayNames = $this->dayNames($now);
        $currentTime = $now->format('H:i:s');

        $targetJadwal = Jadwal::query()
            ->where('mata_kuliah_id', $data['mata_kuliah_id'])
            ->where('kelas_id', $data['kelas_id'])
            ->whereIn('hari', $dayNames)
            ->where('jam_mulai', '<=', $currentTime)
            ->where('jam_selesai', '>=', $currentTime)
            ->first();

        // If no matching schedule for today, find any schedule for this course/class
        if (! $targetJadwal) {
            $targetJadwal = $jadwalQuery->first();
        }

        Cache::put('active_attendance_session', [
            'mata_kuliah_id' => $data['mata_kuliah_id'],
            'kelas_id' => $data['kelas_id'],
            'jadwal_id' => $targetJadwal?->id,
            'started_at' => now()->toDateTimeString(),
            'user_id' => $request->user()?->id,
            'source' => 'schedule',
        ], now()->addHours(3));

        return redirect()->route('monitoring')->with('success', 'Sesi presensi jadwal berhasil diaktifkan.');
    }

    public function destroy(): RedirectResponse
    {
        Cache::forget('active_attendance_session');
        return redirect()->route('dosen-courses')->with('success', 'Sesi presensi telah ditutup.');
    }

    public function detail(): View|RedirectResponse
    {
        return $this->detailByDate(now()->toDateString());
    }

    public function detailByFilter(Request $request): View|RedirectResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $mataKuliahId = $request->query('mata_kuliah_id');
        $kelasId = $request->query('kelas_id');

        return $this->detailByDate($selectedDate, $mataKuliahId, $kelasId);
    }

    public function exportExcel(Request $request): StreamedResponse|RedirectResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $mataKuliahId = $request->query('mata_kuliah_id');
        $kelasId = $request->query('kelas_id');
        
        $detailData = $this->buildDetailData($selectedDate, $mataKuliahId, $kelasId);

        if (isset($detailData['redirect'])) {
            return $detailData['redirect'];
        }

        $fileDate = str_replace('-', '', $selectedDate);
        $filename = "detail_sesi_{$fileDate}.xlsx";

        return response()->streamDownload(function () use ($detailData): void {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'Tanggal');
            $sheet->setCellValue('B1', $detailData['selectedDate']);
            $sheet->setCellValue('A2', 'Mata Kuliah');
            $sheet->setCellValue('B2', $detailData['mataKuliah']->nama_mk . ' (' . $detailData['mataKuliah']->kode_mk . ')');
            $sheet->setCellValue('A3', 'Kelas');
            $sheet->setCellValue('B3', $detailData['kelas']->nama_kelas);

            $headerRow = 5;
            $sheet->fromArray(['NIM', 'Nama', 'Status', 'Metode', 'Waktu Tap'], null, "A{$headerRow}");

            $rowIndex = $headerRow + 1;
            foreach ($detailData['studentRows'] as $row) {
                $sheet->fromArray([
                    $row['nim'],
                    $row['nama'],
                    $row['status'] === 'Pending' ? 'Belum Absensi' : $row['status'],
                    $row['metode'],
                    $row['waktu_tap'],
                ], null, "A{$rowIndex}");

                $rowIndex++;
            }

            foreach (['A', 'B', 'C', 'D', 'E'] as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function exportPdf(Request $request): Response|RedirectResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $mataKuliahId = $request->query('mata_kuliah_id');
        $kelasId = $request->query('kelas_id');

        $detailData = $this->buildDetailData($selectedDate, $mataKuliahId, $kelasId);

        if (isset($detailData['redirect'])) {
            return $detailData['redirect'];
        }

        $fileDate = str_replace('-', '', $selectedDate);
        $filename = "detail_sesi_{$fileDate}.pdf";

        return Pdf::loadView('dosen.session-detail-pdf', $detailData)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    private function detailByDate(string $selectedDate, $mataKuliahId = null, $kelasId = null): View|RedirectResponse
    {
        $detailData = $this->buildDetailData($selectedDate, $mataKuliahId, $kelasId);

        if (isset($detailData['redirect'])) {
            return $detailData['redirect'];
        }

        return view('dosen.session-detail', $detailData);
    }

    private function buildDetailData(string $selectedDate, $mataKuliahId = null, $kelasId = null): array
    {
        $activeSession = Cache::get('active_attendance_session');
        $currentUser = request()->user();

        // Prioritize parameters, fallback to cache
        $finalMkId = $mataKuliahId ?? ($activeSession['mata_kuliah_id'] ?? null);
        $finalKelasId = $kelasId ?? ($activeSession['kelas_id'] ?? null);

        if (! $finalMkId || ! $finalKelasId) {
            return [
                'redirect' => redirect()->route('dosen-courses')->with('error', 'Buka sesi dari jadwal yang tersedia di Mata Kuliah Saya.'),
            ];
        }

        $mataKuliah = MataKuliah::find($finalMkId);
        $kelas = Kelas::find($finalKelasId);

        if (! $mataKuliah || ! $kelas) {
            return [
                'redirect' => redirect()->route('dosen-courses')->with('error', 'Data mata kuliah atau kelas tidak ditemukan.'),
            ];
        }

        $date = Carbon::parse($selectedDate);
        $dayNames = $this->dayNames($date);

        // Find jadwal by mata_kuliah and kelas, prioritizing today's day but falling back to any day
        $jadwalQuery = Jadwal::query()
            ->where('kelas_id', $kelas->id)
            ->where('mata_kuliah_id', $mataKuliah->id);

        if ($currentUser?->role !== 'admin') {
            $isOwner = MataKuliahDosenAssignment::query()
                ->where('mata_kuliah_id', $mataKuliah->id)
                ->where('user_id', (int) $currentUser?->id)
                ->exists();

            if (! $isOwner) {
                return [
                    'redirect' => redirect()->route('dosen-courses')->with('error', 'Anda tidak memiliki akses ke mata kuliah ini.'),
                ];
            }
        }

        // Get all jadwal IDs for this course and class.
        // This ensures attendance records are fetched regardless of which specific 
        // day/session they were recorded under (fixes sync issue with Live Monitoring).
        $jadwalIds = $jadwalQuery->pluck('id');

        if ($jadwalIds->isEmpty()) {
            return [
                'redirect' => redirect()->route('dosen-courses')->with('error', 'Jadwal tidak ditemukan untuk mata kuliah/kelas ini.'),
            ];
        }

        $students = Mahasiswa::query()
            ->where('kelas_id', $kelas->id)
            ->orderBy('nama')
            ->get(['id', 'nim', 'nama']);

        $attendanceRows = collect();
        if ($jadwalIds->isNotEmpty()) {
            $attendanceRows = Absensi::query()
                ->whereDate('tanggal', $selectedDate)
                ->whereIn('jadwal_id', $jadwalIds)
                ->orderByDesc('created_at')
                ->get(['id', 'mahasiswa_id', 'status', 'metode_absensi', 'waktu_tap', 'created_at']);
        }

        $latestAttendanceByStudent = $attendanceRows
            ->unique('mahasiswa_id')
            ->keyBy('mahasiswa_id');

        $studentRows = $students->map(function (Mahasiswa $student) use ($latestAttendanceByStudent): array {
            $attendance = $latestAttendanceByStudent->get($student->id);
            $status = $attendance?->status ?? 'Pending';

            return [
                'nim' => $student->nim,
                'nama' => $student->nama,
                'status' => $status,
                'metode' => $attendance?->metode_absensi ?? '-',
                'waktu_tap' => $this->formatTapTime($status, $attendance?->waktu_tap),
                'is_pending' => ! $attendance,
            ];
        })->values();

        $summary = [
            'total_students' => $students->count(),
            'hadir' => $studentRows->where('status', 'Hadir')->count(),
            'telat' => $studentRows->where('status', 'Telat')->count(),
            'sakit' => $studentRows->where('status', 'Sakit')->count(),
            'izin' => $studentRows->where('status', 'Izin')->count(),
            'alpa' => $studentRows->where('status', 'Alpa')->count(),
            'pending' => $studentRows->where('status', 'Pending')->count(),
        ];

        return [
            'activeSession' => $activeSession,
            'mataKuliah' => $mataKuliah,
            'kelas' => $kelas,
            'selectedDate' => $selectedDate,
            'summary' => $summary,
            'studentRows' => $studentRows,
        ];
    }

    private function dayNames(Carbon $date): array
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

    private function normalizeDate(string $date): string
    {
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }

    private function assignedCourseIds(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        return MataKuliahDosenAssignment::query()
            ->where('user_id', $userId)
            ->pluck('mata_kuliah_id')
            ->map(static fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function formatTapTime(string $status, mixed $waktuTap): string
    {
        if (! $waktuTap) {
            return '-';
        }

        $time = substr((string) $waktuTap, 0, 8);
        if (strtolower($status) === 'alpa' && $time === '00:00:00') {
            return '-';
        }

        return $time;
    }
}
