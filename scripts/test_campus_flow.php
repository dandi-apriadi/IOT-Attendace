<?php

use App\Models\Mahasiswa;
use App\Models\Jadwal;
use App\Models\Absensi;
use App\Models\Device;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require_once dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function log_step($msg) {
    echo "[" . date('H:i:s') . "] " . $msg . "\n";
}

// 1. Setup Device
log_step("Setting up Test Device...");
$token = 'change-this-token-for-iot-devices';
$deviceId = 'CAMPUS_AUDIT_DEV_01';
Device::updateOrCreate(
    ['device_id' => $deviceId],
    ['name' => 'Campus Audit Device', 'token_hash' => hash('sha256', $token), 'is_active' => true]
);

// 2. Setup Active Session (Manual Session)
log_step("Starting Manual Session for TI-1A (Pemrograman Dasar)...");
$now = Carbon::now();
$sessionData = [
    'mata_kuliah_id' => 1,
    'kelas_id' => 1,
    'started_at' => $now->toTimeString(), // Session starts NOW
    'started_at_full' => $now->toDateTimeString()
];
Cache::put('active_attendance_session', $sessionData, 7200); // 2 hours

// 3. Test API Sequence
$baseUrl = 'http://localhost:8000/api/absensi';

// Scenario A: Hadir (Tepat Waktu)
log_step("Scenario A: Student taps RFID (Tepat Waktu)...");
$responseA = Http::withHeaders(['X-Device-Token' => $token, 'X-Device-Id' => $deviceId])
    ->post($baseUrl, ['identifier' => 'NEW_RFID_123', 'type' => 'RFID']);

if ($responseA->successful()) {
    log_step("SUCCESS: " . json_encode($responseA->json()['data']));
} else {
    log_step("FAILED: " . $responseA->body());
}

// Scenario B: Telat (After Grace Period)
log_step("Scenario B: Simulating Late Tap (> 15 mins)...");
// We update the session start time to 20 minutes ago in cache
$lateSession = $sessionData;
$lateSession['started_at'] = $now->copy()->subMinutes(20)->toTimeString();
Cache::put('active_attendance_session', $lateSession, 7200);

$responseB = Http::withHeaders(['X-Device-Token' => $token, 'X-Device-Id' => $deviceId])
    ->post($baseUrl, ['identifier' => 'NEW_RFID_123', 'type' => 'RFID']); // Re-tap same student (should update?)

if ($responseB->successful()) {
    log_step("SUCCESS (Updated to Telat): " . json_encode($responseB->json()['data']));
} else {
    log_step("FAILED: " . $responseB->body());
}

// 4. Verification in DB
log_step("Verifying Database Records...");
$absensi = Absensi::where('tanggal', $now->toDateString())
    ->where('mahasiswa_id', 1)
    ->with(['mahasiswa', 'jadwal.mata_kuliah'])
    ->first();

if ($absensi) {
    echo "--------------------------------------------------\n";
    echo "MAHASISWA: " . $absensi->mahasiswa->nama . "\n";
    echo "MATKUL: " . $absensi->jadwal->mata_kuliah->nama_mk . "\n";
    echo "STATUS AKHIR: " . $absensi->status . "\n";
    echo "METODE: " . $absensi->metode_absensi . "\n";
    echo "--------------------------------------------------\n";
} else {
    log_step("ERROR: No record in database!");
}

// Cleanup
Cache::forget('active_attendance_session');
log_step("Test Sequence Finished.");
