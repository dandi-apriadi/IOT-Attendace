<?php

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$dayMap = [
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

$start = Carbon::yesterday()->startOfDay();
$end = Carbon::today()->addDays(7)->startOfDay();

$jadwalPool = Jadwal::query()
    ->with(['mata_kuliah:id,kode_mk,nama_mk', 'kelas:id,nama_kelas', 'dosen:id,name'])
    ->whereHas('mata_kuliah', function ($query) {
        $query->where('kode_mk', '!=', 'SIM101');
    })
    ->whereHas('kelas', function ($query) {
        $query->where('nama_kelas', '!=', 'TI-REG-SIM');
    })
    ->orderBy('jam_mulai')
    ->get()
    ->filter(function (Jadwal $jadwal) {
        return Mahasiswa::where('kelas_id', $jadwal->kelas_id)->exists();
    })
    ->values();

if ($jadwalPool->isEmpty()) {
    echo json_encode([
        'message' => 'Tidak ada jadwal existing yang memiliki mahasiswa pada kelas terkait.',
        'records_upserted' => 0,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    return;
}

$sessionPerDay = [];
for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
    $hari = $dayMap[$date->dayOfWeekIso];
    $hariEn = $dayMapEn[$date->dayOfWeekIso];

    $sessionPerDay[$date->toDateString()] = $jadwalPool
        ->filter(function (Jadwal $jadwal) use ($hari, $hariEn) {
            return in_array((string) $jadwal->hari, [$hari, $hariEn], true);
        })
        ->take(8)
        ->values();
}

$targetDates = [Carbon::yesterday(), Carbon::today()];
$recordsUpserted = 0;
$jadwalUsed = collect();

foreach ($targetDates as $date) {
    $dateKey = $date->toDateString();
    $sessions = $sessionPerDay[$dateKey] ?? collect();

    foreach ($sessions as $jadwal) {
        $students = Mahasiswa::query()
            ->where('kelas_id', $jadwal->kelas_id)
            ->whereNotExists(function ($query) use ($jadwal, $dateKey) {
                $query->select(DB::raw(1))
                    ->from('absensi')
                    ->whereColumn('absensi.mahasiswa_id', 'mahasiswa.id')
                    ->where('absensi.jadwal_id', $jadwal->id)
                    ->whereDate('absensi.tanggal', $dateKey);
            })
            ->orderBy('id')
            ->limit(20)
            ->get();

        if ($students->isEmpty()) {
            continue;
        }

        $jadwalUsed->push($jadwal->id);

        $baseTime = Carbon::parse($dateKey . ' ' . substr((string) $jadwal->jam_mulai, 0, 8));

        foreach ($students as $idx => $mahasiswa) {
            $status = $idx < 14 ? 'Hadir' : ($idx < 17 ? 'Telat' : ($idx < 19 ? 'Izin' : 'Sakit'));
            $metode = $idx % 3 === 0 ? 'Fingerprint' : ($idx % 3 === 1 ? 'RFID' : 'Barcode');
            $tapAt = $baseTime->copy()->addMinutes($idx * 2 + 3);

            $inserted = DB::table('absensi')->insertOrIgnore([
                'mahasiswa_id' => $mahasiswa->id,
                'jadwal_id' => $jadwal->id,
                'tanggal' => $dateKey,
                'waktu_tap' => $tapAt->format('H:i:s'),
                'metode_absensi' => $metode,
                'status' => $status,
                'created_at' => $tapAt,
                'updated_at' => now(),
            ]);

            $recordsUpserted += (int) $inserted;
        }
    }
}

$jadwalUsed = $jadwalUsed->unique()->values();

$preview = [];
foreach ($sessionPerDay as $dateKey => $sessions) {
    $preview[] = [
        'tanggal' => $dateKey,
        'hari' => $dayMap[Carbon::parse($dateKey)->dayOfWeekIso],
        'jumlah_sesi' => $sessions->count(),
        'jadwal_id' => $sessions->pluck('id')->values()->all(),
    ];
}

$result = [
    'mode' => 'existing-data-sync',
    'range_start' => $start->toDateString(),
    'range_end' => $end->toDateString(),
    'jadwal_existing_total' => $jadwalPool->count(),
    'jadwal_used_for_simulation' => $jadwalUsed->count(),
    'records_upserted' => $recordsUpserted,
    'absensi_kemarin' => Absensi::query()
        ->whereDate('tanggal', Carbon::yesterday()->toDateString())
        ->whereIn('jadwal_id', $jadwalUsed)
        ->count(),
    'absensi_hari_ini' => Absensi::query()
        ->whereDate('tanggal', Carbon::today()->toDateString())
        ->whereIn('jadwal_id', $jadwalUsed)
        ->count(),
    'preview_jadwal_8_hari' => $preview,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
