<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "📊 Mengisi data absensi April 2026 untuk Dosen Utama (user_id=8)...\n\n";

$jadwalList = DB::table('jadwal')
    ->where('user_id', 8)
    ->get();

$now = Carbon::now();
$monthStart = $now->copy()->startOfMonth();
$monthEnd = $now->copy()->endOfMonth();

$dayMap = [
    'Monday' => 1,
    'Tuesday' => 2,
    'Wednesday' => 3,
    'Thursday' => 4,
    'Friday' => 5,
    'Saturday' => 6,
    'Sunday' => 7,
];

// Generate meeting dates for this month
$meetingDates = [];
$currentDate = $monthStart->copy();

while ($currentDate->lte($monthEnd)) {
    $dayOfWeekIso = $currentDate->dayOfWeekIso;
    foreach ($jadwalList as $jadwal) {
        $jadwalDayIso = $dayMap[$jadwal->hari] ?? null;
        if ($jadwalDayIso === $dayOfWeekIso) {
            $meetingDates[] = $currentDate->copy()->toDateString();
        }
    }
    $currentDate->addDay();
}

$meetingDates = array_values(array_unique($meetingDates));
sort($meetingDates);

echo "📅 Generated " . count($meetingDates) . " meeting dates in April 2026\n";
echo "   Dates: " . implode(', ', $meetingDates) . "\n\n";

$totalInserted = 0;
$statuses = ['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Telat', 'Telat', 'Sakit', 'Izin', 'Alpa'];

foreach ($jadwalList as $jadwal) {
    $mahasiswaList = DB::table('mahasiswa')
        ->where('kelas_id', $jadwal->kelas_id)
        ->get();

    echo "📚 Jadwal ID {$jadwal->id}: MK {$jadwal->mata_kuliah_id} - Kelas {$jadwal->kelas_id} ({$mahasiswaList->count()} mahasiswa)\n";

    foreach ($meetingDates as $meetingDate) {
        foreach ($mahasiswaList as $mahasiswa) {
            $exists = DB::table('absensi')
                ->where('mahasiswa_id', $mahasiswa->id)
                ->where('jadwal_id', $jadwal->id)
                ->where('tanggal', $meetingDate)
                ->exists();

            if ($exists) {
                continue;
            }

            $status = $statuses[array_rand($statuses)];
            $waktuTap = '00:00:00';

            if ($status === 'Hadir') {
                $jamMulai = Carbon::parse($jadwal->jam_mulai);
                $offsetMinutes = rand(-10, 10);
                $waktuTap = $jamMulai->copy()->addMinutes($offsetMinutes)->format('H:i:s');
            } elseif ($status === 'Telat') {
                $jamMulai = Carbon::parse($jadwal->jam_mulai);
                $offsetMinutes = rand(16, 45);
                $waktuTap = $jamMulai->copy()->addMinutes($offsetMinutes)->format('H:i:s');
            }

            DB::table('absensi')->insert([
                'mahasiswa_id' => $mahasiswa->id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $meetingDate,
                'waktu_tap' => $waktuTap,
                'metode_absensi' => 'RFID',
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totalInserted++;
        }
    }

    echo "   ✅ Inserted records for this jadwal\n";
}

echo "\n✅ Total {$totalInserted} absensi records inserted for April 2026.\n";

// Verify
echo "\n📊 Verification:\n";
foreach ($jadwalList as $jadwal) {
    $count = DB::table('absensi')
        ->where('jadwal_id', $jadwal->id)
        ->whereBetween('tanggal', [$monthStart->toDateString(), $monthEnd->toDateString()])
        ->count();
    echo "   Jadwal {$jadwal->id} (MK {$jadwal->mata_kuliah_id}): {$count} records in April\n";
}
