<?php

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$today = Carbon::today();
$todayDate = $today->toDateString();

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

$todayDayId = $dayMapId[$today->dayOfWeekIso];
$todayDayEn = $dayMapEn[$today->dayOfWeekIso];

$jadwalToday = Jadwal::query()
    ->whereIn('hari', [$todayDayId, $todayDayEn])
    ->orderBy('jam_mulai')
    ->get();

$inserted = 0;
$checkedStudents = 0;
$details = [];

foreach ($jadwalToday as $jadwal) {
    $students = Mahasiswa::query()
        ->where('kelas_id', $jadwal->kelas_id)
        ->orderBy('id')
        ->get(['id']);

    if ($students->isEmpty()) {
        $details[] = [
            'jadwal_id' => $jadwal->id,
            'kelas_id' => $jadwal->kelas_id,
            'checked' => 0,
            'inserted' => 0,
        ];

        continue;
    }

    $checkedStudents += $students->count();

    $existingMahasiswaIds = Absensi::query()
        ->where('jadwal_id', $jadwal->id)
        ->whereDate('tanggal', $todayDate)
        ->pluck('mahasiswa_id')
        ->all();

    $missingStudents = $students
        ->reject(fn ($student) => in_array($student->id, $existingMahasiswaIds, true))
        ->values();

    $rows = [];
    foreach ($missingStudents as $student) {
        $rows[] = [
            'mahasiswa_id' => $student->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => $todayDate,
            // Alpa otomatis tidak memiliki waktu tap riil.
            'waktu_tap' => '00:00:00',
            'metode_absensi' => 'RFID',
            'status' => 'Alpa',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    $insertedForSchedule = 0;
    if (!empty($rows)) {
        $insertedForSchedule = DB::table('absensi')->insertOrIgnore($rows);
        $inserted += (int) $insertedForSchedule;
    }

    $details[] = [
        'jadwal_id' => $jadwal->id,
        'kelas_id' => $jadwal->kelas_id,
        'checked' => $students->count(),
        'inserted' => (int) $insertedForSchedule,
    ];
}

echo json_encode([
    'date' => $todayDate,
    'day' => $todayDayId,
    'jadwal_count' => $jadwalToday->count(),
    'checked_students' => $checkedStudents,
    'inserted_missing_absensi' => $inserted,
    'status_used' => 'Alpa',
    'details' => $details,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
