<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\Jadwal;
use App\Models\Mahasiswa;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class LoadTestAbsensi extends Command
{
    protected $signature = 'test:absensi-load {--requests=100 : Total requests to send} {--concurrency=20 : Requests per parallel batch}';

    protected $description = 'Run concurrent load test for POST /api/absensi and validate duplicate protection';

    public function handle(): int
    {
        $requests = max(1, (int) $this->option('requests'));
        $concurrency = max(1, (int) $this->option('concurrency'));

        $baseUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');
        $endpoint = $baseUrl . '/api/absensi';
        $identifier = '00ACFC25';
        $token = (string) env('DEVICE_API_TOKEN', 'change-this-token-for-iot-devices');

        $mahasiswa = Mahasiswa::query()->where('rfid_uid', $identifier)->first();
        if (! $mahasiswa) {
            $this->error('Mahasiswa test RFID tidak ditemukan: ' . $identifier);
            return self::FAILURE;
        }

        $jadwal = Jadwal::query()->where('kelas_id', $mahasiswa->kelas_id)->first();
        if (! $jadwal) {
            $this->error('Jadwal untuk kelas mahasiswa test tidak ditemukan.');
            return self::FAILURE;
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

        $this->info('=== ABSENSI CONCURRENCY TEST ===');
        $this->line('Endpoint: ' . $endpoint);
        $this->line('Requests: ' . $requests);
        $this->line('Concurrency per batch: ' . $concurrency);

        $statusCount = [];
        $failed = 0;
        $remaining = $requests;
        $firstMessage = null;

        $start = microtime(true);

        while ($remaining > 0) {
            $batch = min($remaining, $concurrency);

            $responses = Http::pool(function (Pool $pool) use ($batch, $endpoint, $token) {
                $pending = [];

                for ($i = 0; $i < $batch; $i++) {
                    $pending[] = $pool
                        ->withHeaders([
                            'X-Device-Token' => $token,
                            'Accept' => 'application/json',
                        ])
                        ->timeout(10)
                        ->post($endpoint, [
                            'identifier' => '00ACFC25',
                            'type' => 'RFID',
                        ]);
                }

                return $pending;
            });

            foreach ($responses as $response) {
                if ($response === null) {
                    $failed++;
                    continue;
                }

                $code = (string) $response->status();
                $statusCount[$code] = ($statusCount[$code] ?? 0) + 1;

                if ($firstMessage === null) {
                    $firstMessage = $response->json('message')
                        ?? $response->json('status')
                        ?? 'no-message';
                }
            }

            $remaining -= $batch;
        }

        $durationMs = (microtime(true) - $start) * 1000;

        $rowsForKey = Absensi::query()
            ->where('mahasiswa_id', $mahasiswa->id)
            ->where('jadwal_id', $jadwal->id)
            ->whereDate('tanggal', $now->toDateString())
            ->count();

        ksort($statusCount);
        foreach ($statusCount as $code => $count) {
            $this->line('HTTP ' . $code . ': ' . $count);
        }

        $this->line('Failed requests: ' . $failed);
        $this->line('Rows for unique key: ' . $rowsForKey);
        $this->line('Duration (ms): ' . number_format($durationMs, 2));
        if ($firstMessage !== null) {
            $this->line('Sample message: ' . $firstMessage);
        }

        if ($rowsForKey !== 1) {
            $this->error('FAIL: duplicate protection gagal, rows != 1.');
            return self::FAILURE;
        }

        $this->info('PASS: unique key + locking menjaga 1 row untuk kombinasi kunci absensi.');
        return self::SUCCESS;
    }
}
