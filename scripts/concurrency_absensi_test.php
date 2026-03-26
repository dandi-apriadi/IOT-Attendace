<?php

declare(strict_types=1);

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$identifier = '00ACFC25';
$endpoint = 'http://127.0.0.1:8000/api/absensi';
$requestsCount = 100;
$concurrency = 20;
$deviceToken = (string) env('DEVICE_API_TOKEN', 'change-this-token-for-iot-devices');

$mahasiswa = Mahasiswa::query()->where('rfid_uid', $identifier)->first();
if (! $mahasiswa) {
    fwrite(STDERR, "ERROR: mahasiswa dengan RFID {$identifier} tidak ditemukan." . PHP_EOL);
    exit(1);
}

$jadwal = Jadwal::query()->where('kelas_id', $mahasiswa->kelas_id)->first();
if (! $jadwal) {
    fwrite(STDERR, "ERROR: jadwal untuk kelas mahasiswa tidak ditemukan." . PHP_EOL);
    exit(1);
}

$now = now();
$jadwal->hari = $now->isoFormat('dddd');
$jadwal->jam_mulai = $now->copy()->subHour()->format('H:i:s');
$jadwal->jam_selesai = $now->copy()->addHour()->format('H:i:s');
$jadwal->save();

Absensi::query()
    ->where('mahasiswa_id', $mahasiswa->id)
    ->where('jadwal_id', $jadwal->id)
    ->whereDate('tanggal', $now->toDateString())
    ->delete();

$body = json_encode([
    'identifier' => $identifier,
    'type' => 'RFID',
], JSON_THROW_ON_ERROR);

$headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'X-Device-Token' => $deviceToken,
];

$client = new Client([
    'http_errors' => false,
    'timeout' => 10,
]);

$statusCount = [];
$errors = 0;

$requests = function () use ($requestsCount, $endpoint, $headers, $body) {
    for ($i = 0; $i < $requestsCount; $i++) {
        yield new Request('POST', $endpoint, $headers, $body);
    }
};

$start = microtime(true);

$pool = new Pool($client, $requests(), [
    'concurrency' => $concurrency,
    'fulfilled' => function ($response) use (&$statusCount): void {
        $status = (string) $response->getStatusCode();
        $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
    },
    'rejected' => function () use (&$errors): void {
        $errors++;
    },
]);

$pool->promise()->wait();

$durationMs = (microtime(true) - $start) * 1000;

$absensiCount = Absensi::query()
    ->where('mahasiswa_id', $mahasiswa->id)
    ->where('jadwal_id', $jadwal->id)
    ->whereDate('tanggal', $now->toDateString())
    ->count();

echo '=== CONCURRENCY TEST RESULT ===' . PHP_EOL;
echo 'Endpoint      : ' . $endpoint . PHP_EOL;
echo 'Total Request : ' . $requestsCount . PHP_EOL;
echo 'Concurrency   : ' . $concurrency . PHP_EOL;
echo 'Duration (ms) : ' . number_format($durationMs, 2) . PHP_EOL;
ksort($statusCount);
foreach ($statusCount as $status => $count) {
    echo 'HTTP ' . $status . '     : ' . $count . PHP_EOL;
}
echo 'Rejected      : ' . $errors . PHP_EOL;
echo 'Rows for key  : ' . $absensiCount . PHP_EOL;

echo PHP_EOL;
if ($absensiCount === 1) {
    echo 'PASS: unik per mahasiswa+jadwal+tanggal tetap terjaga.' . PHP_EOL;
    exit(0);
}

echo 'FAIL: ditemukan duplikasi row absensi pada kunci yang sama.' . PHP_EOL;
exit(2);
