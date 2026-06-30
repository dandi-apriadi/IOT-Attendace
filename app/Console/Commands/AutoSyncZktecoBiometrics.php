<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Services\DeviceCommandService;
use Illuminate\Console\Command;

class AutoSyncZktecoBiometrics extends Command
{
    protected $signature = 'zkteco:auto-sync-biometrics {--force : Tetap antrekan walau ada command pull biometrik yang masih pending}';

    protected $description = 'Mengantrekan penarikan biometrik ZKTeco otomatis untuk agent lokal.';

    public function handle(DeviceCommandService $commands): int
    {
        if (config('agent.role') !== 'server') {
            $this->components->info('Auto sync biometrik hanya berjalan pada APP_ROLE=server.');

            return self::SUCCESS;
        }

        if (! config('agent.biometric_auto_sync_enabled')) {
            $this->components->info('Auto sync biometrik dinonaktifkan.');

            return self::SUCCESS;
        }

        $onlineMinutes = max(1, (int) config('agent.biometric_auto_sync_online_minutes', 10));
        $onlineSince = now()->subMinutes($onlineMinutes);

        $devices = Device::query()
            ->where('type', 'zkteco')
            ->where('is_active', true)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $onlineSince)
            ->orderBy('id')
            ->get();

        $queued = 0;
        $skipped = 0;

        foreach ($devices as $device) {
            if (! $this->option('force') && $this->hasPendingPull($device)) {
                $skipped++;
                continue;
            }

            $commands->enqueue($device, 'pull_biometrics');
            $queued++;
        }

        $this->components->info("Auto sync biometrik: {$queued} command diantrekan, {$skipped} dilewati.");

        return self::SUCCESS;
    }

    private function hasPendingPull(Device $device): bool
    {
        return DeviceCommand::query()
            ->where('device_id', $device->id)
            ->where('type', 'pull_biometrics')
            ->whereIn('status', ['queued', 'dispatched'])
            ->exists();
    }
}
