<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MataKuliahDosenAssignment;
use App\Models\SemesterAkademik;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function kelas(): View
    {
        $kelasList = Kelas::withCount('mahasiswa')
            ->orderBy('nama_kelas')
            ->paginate(12);

        return view('master.kelas', [
            'kelasList' => $kelasList,
        ]);
    }

    public function mataKuliah(Request $request): View
    {
        $selectedSemesterId = (string) $request->query('semester_id', '');
        $semesterList = SemesterAkademik::orderByDesc('is_active')->orderByDesc('tanggal_mulai')->get();

        $query = MataKuliah::with(['semesterAkademik'])->withCount('jadwal');

        if ($selectedSemesterId !== '') {
            $query->where('semester_akademik_id', (int) $selectedSemesterId);
        }

        $mataKuliahList = $query->orderBy('kode_mk')->paginate(12);

        return view('master.matakuliah', [
            'mataKuliahList' => $mataKuliahList,
            'semesterList' => $semesterList,
            'selectedSemesterId' => $selectedSemesterId,
        ]);
    }

    public function mataKuliahReport(string $id, Request $request): View
    {
        $mk = MataKuliah::with('semesterAkademik')->findOrFail($id);

        $presentStatuses = array_values((array) config('attendance.absensi_present_statuses', ['Hadir']));
        $excusedStatuses = array_values((array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']));
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        if ($presentStatuses === []) {
            $presentStatuses = ['Hadir'];
        }
        if ($excusedStatuses === []) {
            $excusedStatuses = ['Sakit', 'Izin'];
        }

        // Only show semester related to this course
        $semesterList = $mk->semesterAkademik ? collect([$mk->semesterAkademik]) : collect();
        $selectedSemesterId = $mk->semester_akademik_id ? (string) $mk->semester_akademik_id : '';

        // Build query for attendance stats per mahasiswa for this mata kuliah
        $query = \Illuminate\Support\Facades\DB::table('absensi as a')
            ->join('mahasiswa as m', 'm.id', '=', 'a.mahasiswa_id')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('j.mata_kuliah_id', (int) $id)
            ->select([
                'm.id',
                'm.nim',
                'm.nama',
                \Illuminate\Support\Facades\DB::raw('COUNT(*) AS total'),
            ])
            ->selectRaw(
                "SUM(CASE WHEN a.status IN (" . implode(', ', array_fill(0, count($presentStatuses), '?')) . ") THEN 1 ELSE 0 END) AS hadir",
                $presentStatuses
            )
            ->selectRaw(
                "SUM(CASE WHEN a.status IN (" . implode(', ', array_fill(0, count($excusedStatuses), '?')) . ") THEN 1 ELSE 0 END) AS sakit_izin",
                $excusedStatuses
            )
            ->selectRaw(
                'SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS alpa',
                [$absentStatus]
            )
            ->groupBy('m.id', 'm.nim', 'm.nama')
            ->orderByDesc('hadir')
            ->orderBy('m.nama');

        // Apply semester filter
        if ($selectedSemesterId !== '') {
            $query->where('j.semester_akademik_id', (int) $selectedSemesterId);
        }

        $stats = $query->paginate(25)->withQueryString();

        // Calculate percentages
        $stats->getCollection()->transform(function ($row) {
            $total = (int) $row->total;
            $hadir = (int) $row->hadir;
            $row->persentase = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
            return $row;
        });

        // Summary stats
        $summaryQuery = \Illuminate\Support\Facades\DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('j.mata_kuliah_id', (int) $id);

        if ($selectedSemesterId !== '') {
            $summaryQuery->where('j.semester_akademik_id', (int) $selectedSemesterId);
        }

        $totalAbsensi = (clone $summaryQuery)->count();
        $totalHadir = (clone $summaryQuery)->whereIn('a.status', $presentStatuses)->count();
        $totalSakitIzin = (clone $summaryQuery)->whereIn('a.status', $excusedStatuses)->count();
        $totalAlpa = (clone $summaryQuery)->where('a.status', $absentStatus)->count();
        $totalMahasiswa = \Illuminate\Support\Facades\DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('j.mata_kuliah_id', (int) $id)
            ->distinct('a.mahasiswa_id')
            ->count('a.mahasiswa_id');

        if ($selectedSemesterId !== '') {
            $totalMahasiswa = \Illuminate\Support\Facades\DB::table('absensi as a')
                ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
                ->where('j.mata_kuliah_id', (int) $id)
                ->where('j.semester_akademik_id', (int) $selectedSemesterId)
                ->distinct('a.mahasiswa_id')
                ->count('a.mahasiswa_id');
        }

        $avgPersentase = $totalAbsensi > 0 ? round(($totalHadir / $totalAbsensi) * 100, 1) : 0;

        return view('master.matakuliah-report', [
            'mk' => $mk,
            'stats' => $stats,
            'semesterList' => $semesterList,
            'selectedSemesterId' => $selectedSemesterId,
            'totalAbsensi' => $totalAbsensi,
            'totalHadir' => $totalHadir,
            'totalSakitIzin' => $totalSakitIzin,
            'totalAlpa' => $totalAlpa,
            'totalMahasiswa' => $totalMahasiswa,
            'avgPersentase' => $avgPersentase,
        ]);
    }

    public function mataKuliahStudentDetail(string $id, string $mahasiswaId, Request $request): View
    {
        $mk = MataKuliah::with('semesterAkademik')->findOrFail($id);
        $mahasiswa = Mahasiswa::findOrFail($mahasiswaId);

        $presentStatuses = array_values((array) config('attendance.absensi_present_statuses', ['Hadir']));
        $excusedStatuses = array_values((array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']));
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        // Get all meeting dates for this course
        $meetingDates = \Illuminate\Support\Facades\DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('j.mata_kuliah_id', (int) $id)
            ->where('a.mahasiswa_id', (int) $mahasiswaId)
            ->select('a.tanggal')
            ->distinct()
            ->orderBy('a.tanggal')
            ->pluck('tanggal')
            ->toArray();

        // Get attendance per meeting
        $attendancePerMeeting = \Illuminate\Support\Facades\DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('j.mata_kuliah_id', (int) $id)
            ->where('a.mahasiswa_id', (int) $mahasiswaId)
            ->select('a.tanggal', 'a.status', 'a.waktu_tap')
            ->orderBy('a.tanggal')
            ->get()
            ->keyBy('tanggal');

        // Build meeting rows
        $meetingRows = [];
        $hadirCount = 0;
        $sakitIzinCount = 0;
        $alpaCount = 0;

        foreach ($meetingDates as $index => $date) {
            $attendance = $attendancePerMeeting->get($date);
            $status = $attendance ? $attendance->status : 'Alpa';
            $waktuTap = $attendance ? $attendance->waktu_tap : '-';

            if (in_array($status, $presentStatuses)) {
                $hadirCount++;
            } elseif (in_array($status, $excusedStatuses)) {
                $sakitIzinCount++;
            } else {
                $alpaCount++;
            }

            $meetingRows[] = [
                'pertemuan' => $index + 1,
                'tanggal' => $date,
                'status' => $status,
                'waktu_tap' => $waktuTap,
            ];
        }

        $totalMeetings = count($meetingRows);
        $persentase = $totalMeetings > 0 ? round(($hadirCount / $totalMeetings) * 100, 1) : 0;

        return view('master.matakuliah-student-detail', [
            'mk' => $mk,
            'mahasiswa' => $mahasiswa,
            'meetingRows' => $meetingRows,
            'totalMeetings' => $totalMeetings,
            'hadirCount' => $hadirCount,
            'sakitIzinCount' => $sakitIzinCount,
            'alpaCount' => $alpaCount,
            'persentase' => $persentase,
        ]);
    }

    public function mataKuliahReportExport(string $id, Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $mk = MataKuliah::with('semesterAkademik')->findOrFail($id);

        $presentStatuses = array_values((array) config('attendance.absensi_present_statuses', ['Hadir']));
        $excusedStatuses = array_values((array) config('attendance.absensi_excused_statuses', ['Sakit', 'Izin']));
        $absentStatus = (string) config('attendance.absensi_absent_status', 'Alpa');

        // Get all meeting dates
        $meetingDates = \Illuminate\Support\Facades\DB::table('absensi as a')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('j.mata_kuliah_id', (int) $id)
            ->select('a.tanggal')
            ->distinct()
            ->orderBy('a.tanggal')
            ->pluck('tanggal')
            ->toArray();

        // Get all students with attendance
        $students = \Illuminate\Support\Facades\DB::table('mahasiswa as m')
            ->join('absensi as a', 'm.id', '=', 'a.mahasiswa_id')
            ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
            ->where('j.mata_kuliah_id', (int) $id)
            ->select('m.id', 'm.nim', 'm.nama')
            ->distinct()
            ->orderBy('m.nama')
            ->get();

        // Build attendance matrix
        $attendanceMatrix = [];
        foreach ($students as $student) {
            $studentAttendance = \Illuminate\Support\Facades\DB::table('absensi as a')
                ->join('jadwal as j', 'j.id', '=', 'a.jadwal_id')
                ->where('j.mata_kuliah_id', (int) $id)
                ->where('a.mahasiswa_id', $student->id)
                ->select('a.tanggal', 'a.status')
                ->get()
                ->keyBy('tanggal');

            $row = [
                'nim' => $student->nim,
                'nama' => $student->nama,
                'meetings' => [],
                'hadir' => 0,
                'sakit_izin' => 0,
                'alpa' => 0,
            ];

            foreach ($meetingDates as $date) {
                $status = $studentAttendance->get($date)?->status ?? 'Alpa';
                $row['meetings'][] = $status;

                if (in_array($status, $presentStatuses)) {
                    $row['hadir']++;
                } elseif (in_array($status, $excusedStatuses)) {
                    $row['sakit_izin']++;
                } else {
                    $row['alpa']++;
                }
            }

            $total = count($meetingDates);
            $row['persentase'] = $total > 0 ? round(($row['hadir'] / $total) * 100, 1) : 0;
            $attendanceMatrix[] = $row;
        }

        $semesterName = $mk->semesterAkademik?->display_name ?? 'All';
        // Sanitize filename: remove/replace invalid characters
        $semesterName = str_replace(['/', '\\', ' ', ':'], '-', $semesterName);
        $filename = 'Absen_' . $mk->kode_mk . '_' . $semesterName . '.xlsx';

        return response()->streamDownload(function () use ($mk, $meetingDates, $attendanceMatrix): void {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

            $maxCol = count($meetingDates) + 7;
            $maxColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxCol);

            // Header info
            $sheet->setCellValue('A1', 'LAPORAN KEHADIRAN');
            $sheet->mergeCells('A1:' . $maxColLetter . '1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', $mk->nama_mk);
            $sheet->mergeCells('A2:' . $maxColLetter . '2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A3', 'Semester: ' . ($mk->semesterAkademik?->display_name ?? '-'));
            $sheet->mergeCells('A3:' . $maxColLetter . '3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Column headers (row 5)
            $col = 1;
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . '5', 'No');
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . '5', 'NIM');
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . '5', 'Nama Mahasiswa');

            foreach ($meetingDates as $index => $date) {
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . '5', 'P' . ($index + 1));
            }

            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . '5', 'Hadir');
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . '5', 'S/I');
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . '5', 'Alpa');
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . '5', '%');

            // Style header row
            $sheet->getStyle('A5:' . $maxColLetter . '5')
                ->getFont()->setBold(true);
            $sheet->getStyle('A5:' . $maxColLetter . '5')
                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE6F6EC');
            $sheet->getStyle('A5:' . $maxColLetter . '5')
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Data rows
            $rowNum = 6;
            foreach ($attendanceMatrix as $index => $student) {
                $col = 1;
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $index + 1);
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $student['nim']);
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $student['nama']);

                foreach ($student['meetings'] as $status) {
                    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $coordinate = $colLetter . $rowNum;
                    $sheet->setCellValue($coordinate, $status);

                    // Color code status
                    $bgColor = 'FFF8D7DA'; // Default: Alpa (red)
                    if ($status === 'Hadir') {
                        $bgColor = 'FFC6EFCE'; // Green
                    } elseif ($status === 'Telat') {
                        $bgColor = 'FFFFF3CD'; // Yellow
                    } elseif (in_array($status, ['Sakit', 'Izin'])) {
                        $bgColor = 'FFD1E7FF'; // Blue
                    }

                    $sheet->getStyle($coordinate)
                        ->getFill()
                        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setARGB($bgColor);

                    $col++;
                }

                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $student['hadir']);
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $student['sakit_izin']);
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col++) . $rowNum, $student['alpa']);
                $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col) . $rowNum, $student['persentase'] . '%');

                $rowNum++;
            }

            // Auto-size columns
            for ($i = 1; $i <= $maxCol; $i++) {
                $sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function jadwal(): View
    {
        $semesterList = SemesterAkademik::orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->get();

        $activeSemesterId = (int) ($semesterList->firstWhere('is_active', true)?->id ?? $semesterList->first()?->id ?? 0);

        $jadwalList = Jadwal::with(['semesterAkademik', 'kelas', 'mata_kuliah', 'dosen'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->paginate(20);

        return view('master.jadwal', [
            'jadwalList' => $jadwalList,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahList' => MataKuliah::orderBy('nama_mk')->get(),
            'dosenList' => User::where('role', 'dosen')->orderBy('name')->get(),
            'semesterList' => $semesterList,
            'activeSemesterId' => $activeSemesterId,
        ]);
    }

    public function storeMataKuliah(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kode_mk' => ['required', 'string', 'max:20', 'unique:mata_kuliah,kode_mk'],
            'nama_mk' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'semester_akademik_id' => ['nullable', 'exists:semester_akademik,id'],
        ]);

        $mk = MataKuliah::create($data);

        AuditLogger::log(
            $request,
            'tambah_matakuliah',
            'Menambahkan mata kuliah ' . $mk->nama_mk . ' (' . $mk->kode_mk . ')',
            $request->user()?->id
        );

        return redirect()->route('matakuliah')->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function editMataKuliah(string $id): View
    {
        $mk = MataKuliah::with('semesterAkademik')->findOrFail($id);
        $semesterList = SemesterAkademik::orderByDesc('is_active')->orderByDesc('tanggal_mulai')->get();
        return view('master.matakuliah-edit', [
            'mk' => $mk,
            'semesterList' => $semesterList,
        ]);
    }

    public function updateMataKuliah(Request $request, string $id): RedirectResponse
    {
        $mk = MataKuliah::findOrFail($id);
        
        $data = $request->validate([
            'kode_mk' => ['required', 'string', 'max:20', Rule::unique('mata_kuliah')->ignore($mk->id)],
            'nama_mk' => ['required', 'string', 'max:255'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'semester_akademik_id' => ['nullable', 'exists:semester_akademik,id'],
        ]);

        $mk->update($data);

        AuditLogger::log(
            $request,
            'update_matakuliah',
            'Memperbarui mata kuliah ' . $mk->nama_mk . ' (' . $mk->kode_mk . ')',
            $request->user()?->id
        );

        return redirect()->route('matakuliah')->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroyMataKuliah(Request $request, string $id): RedirectResponse
    {
        $mk = MataKuliah::findOrFail($id);
        
        // Optional: Check if used in Jadwal
        if ($mk->jadwal()->count() > 0) {
            return redirect()->route('matakuliah')->with('error', 'Tidak dapat menghapus mata kuliah yang sudah ada di jadwal kuliah.');
        }

        $mkName = $mk->nama_mk;
        $mkCode = $mk->kode_mk;
        $mk->delete();

        AuditLogger::log(
            $request,
            'hapus_matakuliah',
            'Menghapus mata kuliah ' . $mkName . ' (' . $mkCode . ')',
            $request->user()?->id
        );

        return redirect()->route('matakuliah')->with('success', 'Mata kuliah berhasil dihapus.');
    }

    public function storeKelas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', 'unique:kelas,nama_kelas'],
        ]);

        $kelas = Kelas::create($data);

        AuditLogger::log(
            $request,
            'tambah_kelas',
            'Menambahkan kelas ' . $kelas->nama_kelas,
            $request->user()?->id
        );

        return redirect()->route('kelas')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function editKelas(string $id): View
    {
        $kelas = Kelas::findOrFail($id);
        return view('master.kelas-edit', [
            'kelas' => $kelas,
        ]);
    }

    public function updateKelas(Request $request, string $id): RedirectResponse
    {
        $kelas = Kelas::findOrFail($id);
        
        $data = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50', Rule::unique('kelas')->ignore($kelas->id)],
        ]);

        $kelas->update($data);

        AuditLogger::log(
            $request,
            'update_kelas',
            'Memperbarui kelas ' . $kelas->nama_kelas,
            $request->user()?->id
        );

        return redirect()->route('kelas')->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroyKelas(Request $request, string $id): RedirectResponse
    {
        $kelas = Kelas::findOrFail($id);
        
        // Safety checks
        if ($kelas->mahasiswa()->count() > 0) {
            return redirect()->route('kelas')->with('error', 'Tidak dapat menghapus kelas yang masih memiliki mahasiswa.');
        }

        if ($kelas->jadwal()->count() > 0) {
            return redirect()->route('kelas')->with('error', 'Tidak dapat menghapus kelas yang sudah terdaftar dalam jadwal kuliah.');
        }

        $kelasName = $kelas->nama_kelas;
        $kelas->delete();

        AuditLogger::log(
            $request,
            'hapus_kelas',
            'Menghapus kelas ' . $kelasName,
            $request->user()?->id
        );

        return redirect()->route('kelas')->with('success', 'Kelas berhasil dihapus.');
    }

    public function storeJadwal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliah,id'],
            'user_id' => ['required', Rule::exists('users', 'id')->where(static fn ($query) => $query->where('role', 'dosen'))],
            'semester_akademik_id' => ['required', 'exists:semester_akademik,id'],
            'hari' => ['required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required', 'after:jam_mulai'],
        ]);

        if ($ownershipConflict = $this->singleDosenOwnershipConflict($data, null)) {
            return redirect()->route('jadwal')->with('error', $ownershipConflict);
        }

        $jadwal = DB::transaction(function () use ($data) {
            $this->ensureMataKuliahAssignment((int) $data['mata_kuliah_id'], (int) $data['user_id']);
            return Jadwal::create($data);
        });

        AuditLogger::log(
            $request,
            'tambah_jadwal',
            'Menambahkan jadwal ' . $jadwal->mata_kuliah?->nama_mk . ' untuk kelas ' . $jadwal->kelas?->nama_kelas,
            $request->user()?->id
        );

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function editJadwal(string $id): View
    {
        $jadwal = Jadwal::findOrFail($id);
        return view('master.jadwal-edit', [
            'jadwal' => $jadwal,
            'kelasList' => Kelas::orderBy('nama_kelas')->get(),
            'mataKuliahList' => MataKuliah::orderBy('nama_mk')->get(),
            'dosenList' => User::where('role', 'dosen')->orderBy('name')->get(),
            'semesterList' => SemesterAkademik::orderByDesc('is_active')->orderByDesc('tanggal_mulai')->get(),
        ]);
    }

    public function updateJadwal(Request $request, string $id): RedirectResponse
    {
        $jadwal = Jadwal::findOrFail($id);
        
        $data = $request->validate([
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliah,id'],
            'user_id' => ['required', Rule::exists('users', 'id')->where(static fn ($query) => $query->where('role', 'dosen'))],
            'semester_akademik_id' => ['required', 'exists:semester_akademik,id'],
            'hari' => ['required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required', 'after:jam_mulai'],
        ]);

        if ($ownershipConflict = $this->singleDosenOwnershipConflict($data, (int) $jadwal->id)) {
            return redirect()->route('jadwal.edit', $jadwal->id)->with('error', $ownershipConflict);
        }

        $oldMataKuliahId = (int) $jadwal->mata_kuliah_id;

        DB::transaction(function () use ($jadwal, $data, $oldMataKuliahId): void {
            $this->ensureMataKuliahAssignment((int) $data['mata_kuliah_id'], (int) $data['user_id']);
            $jadwal->update($data);

            if ($oldMataKuliahId !== (int) $data['mata_kuliah_id']) {
                $this->cleanupOrphanAssignment($oldMataKuliahId);
            }
        });

        AuditLogger::log(
            $request,
            'update_jadwal',
            'Memperbarui jadwal ' . $jadwal->mata_kuliah?->nama_mk . ' kelas ' . $jadwal->kelas?->nama_kelas,
            $request->user()?->id
        );

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroyJadwal(Request $request, string $id): RedirectResponse
    {
        $jadwal = Jadwal::findOrFail($id);
        
        // Safety check: Don't delete if there is attendance data
        if ($jadwal->absensi()->count() > 0) {
            return redirect()->route('jadwal')->with('error', 'Tidak dapat menghapus jadwal yang sudah memiliki data absensi.');
        }

        $info = $jadwal->mata_kuliah?->nama_mk . ' - ' . $jadwal->kelas?->nama_kelas;
        $mataKuliahId = (int) $jadwal->mata_kuliah_id;

        DB::transaction(function () use ($jadwal, $mataKuliahId): void {
            $jadwal->delete();
            $this->cleanupOrphanAssignment($mataKuliahId);
        });

        AuditLogger::log(
            $request,
            'hapus_jadwal',
            'Menghapus jadwal ' . $info,
            $request->user()?->id
        );

        return redirect()->route('jadwal')->with('success', 'Jadwal berhasil dihapus.');
    }

    private function singleDosenOwnershipConflict(array $data, ?int $ignoreJadwalId): ?string
    {
        $assignmentConflict = MataKuliahDosenAssignment::query()
            ->where('mata_kuliah_id', (int) $data['mata_kuliah_id'])
            ->where('user_id', '!=', (int) $data['user_id'])
            ->with(['mataKuliah', 'dosen'])
            ->first();

        if ($assignmentConflict) {
            return 'Mata kuliah ' . ($assignmentConflict->mataKuliah?->nama_mk ?? 'terpilih')
                . ' sudah dimiliki oleh dosen ' . ($assignmentConflict->dosen?->name ?? '-')
                . '. Satu mata kuliah hanya boleh diampu oleh satu dosen.';
        }

        $query = Jadwal::query()
            ->where('mata_kuliah_id', (int) $data['mata_kuliah_id'])
            ->where('user_id', '!=', (int) $data['user_id']);

        if ($ignoreJadwalId !== null) {
            $query->where('id', '!=', $ignoreJadwalId);
        }

        $conflict = $query->with(['mata_kuliah', 'dosen'])->first();
        if (! $conflict) {
            return null;
        }

        return 'Mata kuliah ' . ($conflict->mata_kuliah?->nama_mk ?? 'terpilih')
            . ' sudah dimiliki oleh dosen ' . ($conflict->dosen?->name ?? '-')
            . '. Satu mata kuliah hanya boleh diampu oleh satu dosen.';
    }

    private function ensureMataKuliahAssignment(int $mataKuliahId, int $dosenId): void
    {
        MataKuliahDosenAssignment::query()->updateOrCreate(
            ['mata_kuliah_id' => $mataKuliahId],
            ['user_id' => $dosenId]
        );
    }

    private function cleanupOrphanAssignment(int $mataKuliahId): void
    {
        if ($mataKuliahId <= 0) {
            return;
        }

        $stillUsed = Jadwal::query()
            ->where('mata_kuliah_id', $mataKuliahId)
            ->exists();

        if (! $stillUsed) {
            MataKuliahDosenAssignment::query()
                ->where('mata_kuliah_id', $mataKuliahId)
                ->delete();
        }
    }

    // ==================== SEMESTER AKADEMIK CRUD ====================

    public function semester(): View
    {
        $semesterList = SemesterAkademik::orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->paginate(15);

        return view('master.semester', [
            'semesterList' => $semesterList,
        ]);
    }

    public function storeSemester(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_semester' => ['required', 'string', 'max:50'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // If setting as active, deactivate all others
        if (!empty($data['is_active'])) {
            SemesterAkademik::query()->update(['is_active' => false]);
        } else {
            $data['is_active'] = false;
        }

        $semester = SemesterAkademik::create($data);

        AuditLogger::log(
            $request,
            'tambah_semester',
            'Menambahkan semester ' . $semester->display_name,
            $request->user()?->id
        );

        return redirect()->route('semester')->with('success', 'Semester berhasil ditambahkan.');
    }

    public function editSemester(string $id): View
    {
        $semester = SemesterAkademik::findOrFail($id);
        return view('master.semester-edit', [
            'semester' => $semester,
        ]);
    }

    public function updateSemester(Request $request, string $id): RedirectResponse
    {
        $semester = SemesterAkademik::findOrFail($id);

        $data = $request->validate([
            'nama_semester' => ['required', 'string', 'max:50'],
            'tahun_ajaran' => ['required', 'string', 'max:20'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // If setting as active, deactivate all others
        if (!empty($data['is_active'])) {
            SemesterAkademik::query()->where('id', '!=', $semester->id)->update(['is_active' => false]);
        } else {
            $data['is_active'] = false;
        }

        $semester->update($data);

        AuditLogger::log(
            $request,
            'update_semester',
            'Memperbarui semester ' . $semester->display_name,
            $request->user()?->id
        );

        return redirect()->route('semester')->with('success', 'Semester berhasil diperbarui.');
    }

    public function destroySemester(Request $request, string $id): RedirectResponse
    {
        $semester = SemesterAkademik::findOrFail($id);

        // Safety check: Don't delete if there are jadwal using it
        if ($semester->jadwal()->count() > 0) {
            return redirect()->route('semester')->with('error', 'Tidak dapat menghapus semester yang sudah memiliki jadwal kuliah.');
        }

        $semesterName = $semester->display_name;
        $semester->delete();

        AuditLogger::log(
            $request,
            'hapus_semester',
            'Menghapus semester ' . $semesterName,
            $request->user()?->id
        );

        return redirect()->route('semester')->with('success', 'Semester berhasil dihapus.');
    }

    public function setActiveSemester(Request $request, string $id): RedirectResponse
    {
        $semester = SemesterAkademik::findOrFail($id);

        // Deactivate all, activate selected
        SemesterAkademik::query()->update(['is_active' => false]);
        $semester->update(['is_active' => true]);

        AuditLogger::log(
            $request,
            'set_active_semester',
            'Mengaktifkan semester ' . $semester->display_name,
            $request->user()?->id
        );

        return redirect()->route('semester')->with('success', 'Semester ' . $semester->display_name . ' berhasil diaktifkan.');
    }
}


