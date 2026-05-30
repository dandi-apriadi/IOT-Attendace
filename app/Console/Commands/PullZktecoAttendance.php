<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Absensi;
use App\Models\Mahasiswa;
use App\Models\Jadwal;
use Jmrashed\Zkteco\Lib\Zkteco;
use Carbon\Carbon;
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
            
            $port = $device->port ?: 4370;
            $zk = new Zkteco($device->ip_address, $port);
            
            if ($zk->connect()) {
                $device->update(['last_seen_at' => now()]);
                $this->info("Connected! Pulling attendance...");
                
                try {
                    $attendances = $zk->getAttendance();
                    if (!$attendances || count($attendances) === 0) {
                         $this->info("No new attendance records found.");
                    } else {
                        $this->processAttendance($attendances, $device);
                    }
                    
                    // Optionally clear attendance
                    // $zk->clearAttendance(); 
                } catch (\Exception $e) {
                    $this->error("Error pulling data: " . $e->getMessage());
                    Log::error("ZKTeco Pull Error on {$device->name}: " . $e->getMessage());
                } finally {
                    $zk->disconnect();
                }
            } else {
                $this->error("Failed to connect to {$device->name} ({$device->ip_address}:{$port})");
                Log::warning("ZKTeco failed to connect: {$device->ip_address}");
            }
        }
    }

    private function processAttendance($attendances, $device)
    {
        $count = 0;
        foreach ($attendances as $record) {
            // $record structure depends on the library, typical: ['id' => 1, 'timestamp' => '2023-10-12 08:00:00', ...]
            $uid = $record['id'] ?? null;
            $timestamp = $record['timestamp'] ?? null;

            if (!$uid || !$timestamp) continue;

            $time = Carbon::parse($timestamp);
            $date = $time->toDateString();
            
            // Find student by biometric ID (stored in rfid_uid or fingerprint_data or barcode_id)
            // Assuming ZKTeco ID corresponds to rfid_uid for simplicity, or we can use another field.
            $mahasiswa = Mahasiswa::where('rfid_uid', $uid)->orWhere('nim', $uid)->first();

            if (!$mahasiswa) {
                // Not found
                continue;
            }

            // Find active schedule for this student's class today at this time
            $hariInggris = $time->format('l');
            $jadwal = Jadwal::where('kelas_id', $mahasiswa->kelas_id)
                ->where('hari', $hariInggris)
                ->whereTime('jam_mulai', '<=', $time->toTimeString())
                ->whereTime('jam_selesai', '>=', $time->toTimeString())
                ->first();

            if (!$jadwal) {
                // If they tap outside class hours, maybe just skip or record without schedule
                continue;
            }

            // Check if already exists
            $exists = Absensi::where('mahasiswa_id', $mahasiswa->id)
                ->where('tanggal', $date)
                ->exists();

            if (!$exists) {
                Absensi::create([
                    'mahasiswa_id' => $mahasiswa->id,
                    'jadwal_id' => $jadwal->id,
                    'tanggal' => $date,
                    'waktu_tap' => $time->toTimeString(),
                    'metode_absensi' => 'Fingerprint', // or whatever
                    'status' => 'Hadir', 
                ]);
                $count++;
            }
        }
        $this->info("Successfully inserted $count new attendance records.");
    }
}
