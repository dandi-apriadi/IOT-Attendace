<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DosenSessionController extends Controller
{
    public function create(): View
    {
        // Check if there is already an active manual session
        $activeSession = Cache::get('active_attendance_session');

        // Fetch all kelas and mata_kuliah for dropdown options
        $kelasOptions = Kelas::all()->map(fn ($k) => [
            'id' => $k->id,
            'label' => $k->nama_kelas,
        ])->values();
        
        $mataKuliahOptions = MataKuliah::all()->map(fn ($mk) => [
            'id' => $mk->id,
            'label' => "{$mk->nama_mk} ({$mk->kode_mk})",
        ])->values();
        
        return view('dosen.session', [
            'kelasOptions' => $kelasOptions,
            'mataKuliahOptions' => $mataKuliahOptions,
            'activeSession' => $activeSession,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mata_kuliah_id' => 'required|exists:mata_kuliah,id',
            'kelas_id' => 'required|exists:kelas,id',
        ]);

        Cache::put('active_attendance_session', [
            'mata_kuliah_id' => $data['mata_kuliah_id'],
            'kelas_id' => $data['kelas_id'],
            'started_at' => now()->toDateTimeString(),
            'user_id' => auth()->id(),
            'source' => 'manual',
        ], now()->addHours(3));

        return redirect()->route('monitoring')->with('success', 'Sesi manual berhasil diaktifkan.');
    }

    public function destroy(): RedirectResponse
    {
        Cache::forget('active_attendance_session');
        return redirect()->route('dosen-session')->with('success', 'Sesi manual telah ditutup.');
    }

    public function detail(): View|RedirectResponse
    {
        return $this->detailByDate(now()->toDateString());
    }

    public function detailByFilter(Request $request): View|RedirectResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));

        return $this->detailByDate($selectedDate);
    }

    public function exportExcel(Request $request): StreamedResponse|RedirectResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $detailData = $this->buildDetailData($selectedDate);

        if (isset($detailData['redirect'])) {
            return $detailData['redirect'];
        }

        $fileDate = str_replace('-', '', $selectedDate);
        $filename = "detail_sesi_{$fileDate}.csv";

        return response()->streamDownload(function () use ($detailData): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Tanggal', $detailData['selectedDate']]);
            fputcsv($handle, ['Mata Kuliah', $detailData['mataKuliah']->nama_mk . ' (' . $detailData['mataKuliah']->kode_mk . ')']);
            fputcsv($handle, ['Kelas', $detailData['kelas']->nama_kelas]);
            fputcsv($handle, []);
            fputcsv($handle, ['NIM', 'Nama', 'Status', 'Metode', 'Waktu Tap']);

            foreach ($detailData['studentRows'] as $row) {
                fputcsv($handle, [
                    $row['nim'],
                    $row['nama'],
                    $row['status'] === 'Pending' ? 'Belum Absensi' : $row['status'],
                    $row['metode'],
                    $row['waktu_tap'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): View|RedirectResponse
    {
        $selectedDate = $this->normalizeDate((string) $request->query('date', ''));
        $detailData = $this->buildDetailData($selectedDate);

        if (isset($detailData['redirect'])) {
            return $detailData['redirect'];
        }

        return view('dosen.session-detail-pdf', $detailData);
    }

    private function detailByDate(string $selectedDate): View|RedirectResponse
    {
        $detailData = $this->buildDetailData($selectedDate);

        if (isset($detailData['redirect'])) {
            return $detailData['redirect'];
        }

        return view('dosen.session-detail', $detailData);
    }

    private function buildDetailData(string $selectedDate): array
    {
        $activeSession = Cache::get('active_attendance_session');

        if (! $activeSession) {
            return [
                'redirect' => redirect()->route('dosen-session')->with('error', 'Belum ada sesi manual yang aktif.'),
            ];
        }

        $mataKuliah = MataKuliah::find($activeSession['mata_kuliah_id'] ?? null);
        $kelas = Kelas::find($activeSession['kelas_id'] ?? null);

        if (! $mataKuliah || ! $kelas) {
            return [
                'redirect' => redirect()->route('dosen-session')->with('error', 'Data sesi tidak valid. Silakan aktifkan ulang sesi manual.'),
            ];
        }

        $date = Carbon::parse($selectedDate);
        $dayNames = $this->dayNames($date);

        $jadwalIds = Jadwal::query()
            ->where('kelas_id', $kelas->id)
            ->where('mata_kuliah_id', $mataKuliah->id)
            ->whereIn('hari', $dayNames)
            ->pluck('id')
            ->values();

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
