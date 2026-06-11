<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\ZktecoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PullZktecoAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zkteco:pull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull attendance data from all registered ZKTeco devices (e.g. Solution X609)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $devices = Device::where('type', 'zkteco')
            ->where('is_active', true)
            ->whereNotNull('ip_address')
            ->get();

        if ($devices->isEmpty()) {
            $this->info('No active ZKTeco devices found.');

            return;
        }

        foreach ($devices as $device) {
            $this->info("Connecting to device: {$device->name} ({$device->ip_address}:{$device->port})");

            $service = new ZktecoService($device);

            try {
                $result = $service->importAttendance();
                $this->info("Connected! Inserted {$result['inserted']} new records (skipped {$result['skipped']} of {$result['total']}).");
            } catch (\Throwable $e) {
                $this->error("Failed on {$device->name} ({$device->ip_address}): {$e->getMessage()}");
                Log::warning("ZKTeco pull failed on {$device->name}: {$e->getMessage()}");
            }
        }
    }
}
