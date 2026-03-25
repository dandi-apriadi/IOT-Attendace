<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceTokenCommand extends Command
{
    protected $signature = 'device:token
        {device_id? : Device ID, contoh ROOM_101}
        {--name= : Nama device}
        {--plain= : Plain token jika ingin custom}
        {--store : Simpan/update token ke tabel devices}';

    protected $description = 'Generate token + sha256 hash untuk autentikasi device IoT';

    public function handle(): int
    {
        $deviceId = (string) $this->argument('device_id');
        $plainToken = (string) ($this->option('plain') ?: Str::random(40));
        $tokenHash = hash('sha256', $plainToken);

        $this->line('Device Token Credential');
        $this->line(str_repeat('-', 60));
        $this->line('Device ID  : ' . ($deviceId !== '' ? $deviceId : '(not provided)'));
        $this->line('Plain Token: ' . $plainToken);
        $this->line('SHA256 Hash: ' . $tokenHash);
        $this->newLine();

        if ($this->option('store')) {
            if ($deviceId === '') {
                $this->error('device_id wajib diisi jika menggunakan --store.');
                return self::FAILURE;
            }

            DB::table('devices')->updateOrInsert(
                ['device_id' => $deviceId],
                [
                    'name' => (string) ($this->option('name') ?: $deviceId),
                    'token_hash' => $tokenHash,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $this->info('Token berhasil disimpan/diupdate ke tabel devices.');
        } else {
            $this->warn('Token belum disimpan ke DB. Gunakan --store untuk menyimpan.');
        }

        return self::SUCCESS;
    }
}
