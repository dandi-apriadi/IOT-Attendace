<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AttendanceController;
use App\Models\Mahasiswa;
use App\Models\Jadwal;
use Carbon\Carbon;

class TestApiEndpoint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:api-endpoint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test POST /api/absensi endpoint';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(str_repeat("=", 80));
        $this->info("TEST: POST /api/absensi Endpoint");
        $this->info(str_repeat("=", 80));
        $this->newLine();

        // Setup test data
        $this->line("[SETUP] Preparing test data");
        $this->line(str_repeat("-", 80));

        try {
            $kelas = DB::table('kelas')->first();
            if (!$kelas) {
                throw new \Exception("No Kelas found, please run test:api first");
            }
            $this->info("✓ Kelas: " . $kelas->nama_kelas);

            $mk = DB::table('mata_kuliah')->first();
            if (!$mk) {
                throw new \Exception("No Mata Kuliah found, please run test:api first");
            }
            $this->info("✓ Mata Kuliah: " . $mk->nama_mk);

            $user = DB::table('users')->first();
            if (!$user) {
                throw new \Exception("No User found, please run test:api first");
            }
            $this->info("✓ User (Dosen): " . $user->name);

            $mahasiswa = Mahasiswa::whereNotNull('rfid_uid')->first();
            if (!$mahasiswa) {
                throw new \Exception("No Mahasiswa with RFID found, please run test:api first");
            }
            $this->info("✓ Mahasiswa: " . $mahasiswa->nama . " (RFID: " . $mahasiswa->rfid_uid . ")");

            // Update jadwal to be Monday (today must be Monday for test to work reliably)
            $today = Carbon::now();
            $dayName = $today->format('l'); // e.g., "Monday"
            
            $jadwal = Jadwal::first();
            if ($jadwal) {
                $jadwal->update(['hari' => $dayName]);
                $this->info("✓ Jadwal updated to today: " . $dayName);
            } else {
                throw new \Exception("No Jadwal found");
            }

            $this->newLine();
        } catch (\Exception $e) {
            $this->error("✗ Setup failed: " . $e->getMessage());
            return 1;
        }

        // Test Case 1: Valid RFID Request
        $this->line("[TEST 1] Valid RFID Attendance Request");
        $this->line(str_repeat("-", 80));

        try {
            $request = new Request([
                'identifier' => $mahasiswa->rfid_uid,
                'type' => 'RFID',
                'device_id' => 'ROOM_101',
            ]);

            $controller = new AttendanceController();
            $response = $controller->store($request);
            $data = json_decode($response->getContent(), true);

            if ($data['status'] === 'success') {
                $this->info("✓ Request Status: SUCCESS");
                $this->line("  Response: " . json_encode($data['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $this->line("  Mahasiswa: " . $data['data']['nama']);
                $this->line("  Mata Kuliah: " . $data['data']['mata_kuliah']);
                $this->line("  Waktu: " . $data['data']['waktu']);
                $this->line("  Status: " . $data['data']['keterangan']);
            } else {
                $this->warn("⚠ Request returned: " . $data['message']);
            }
        } catch (\Exception $e) {
            $this->error("✗ Test 1 Failed: " . $e->getMessage());
        }
        $this->newLine();

        // Test Case 2: Invalid Identifier
        $this->line("[TEST 2] Invalid Identifier Request");
        $this->line(str_repeat("-", 80));

        try {
            $request = new Request([
                'identifier' => 'INVALID12345',
                'type' => 'RFID',
                'device_id' => 'ROOM_101',
            ]);

            $controller = new AttendanceController();
            $response = $controller->store($request);
            $data = json_decode($response->getContent(), true);

            if (isset($data['message'])) {
                $this->info("✓ Request handled correctly");
                $this->line("  Response: " . $data['message']);
                $this->line("  HTTP Status: " . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            $this->error("✗ Test 2 Failed: " . $e->getMessage());
        }
        $this->newLine();

        // Test Case 3: Invalid Method Type
        $this->line("[TEST 3] Invalid Method Type Request");
        $this->line(str_repeat("-", 80));

        try {
            $request = new Request([
                'identifier' => $mahasiswa->rfid_uid,
                'type' => 'INVALID_TYPE',
                'device_id' => 'ROOM_101',
            ]);

            // This should fail validation
            $controller = new AttendanceController();
            try {
                $response = $controller->store($request);
                $this->warn("⚠ Request was not validated properly");
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->info("✓ Validation error caught correctly");
                $this->line("  Error: Invalid method type");
            }
        } catch (\Exception $e) {
            $this->info("✓ Validation caught: " . $e->getMessage());
        }
        $this->newLine();

        // Test Case 4: Database Integrity
        $this->line("[TEST 4] Database Integrity Check");
        $this->line(str_repeat("-", 80));

        try {
            $absensiCount = DB::table('absensi')->count();
            $this->info("✓ Absensi records in database: " . $absensiCount);

            if ($absensiCount > 0) {
                $latestAbsensi = DB::table('absensi')
                    ->latest('created_at')
                    ->first();

                $mhs = Mahasiswa::find($latestAbsensi->mahasiswa_id);
                $jdw = Jadwal::find($latestAbsensi->jadwal_id);

                $this->line("  Latest Record:");
                $this->line("    Mahasiswa: " . $mhs->nama);
                $this->line("    Jadwal: " . $jdw->mata_kuliah->nama_mk);
                $this->line("    Status: " . $latestAbsensi->status);
                $this->line("    Metode: " . $latestAbsensi->metode_absensi);
            }
        } catch (\Exception $e) {
            $this->error("✗ Database check failed: " . $e->getMessage());
        }
        $this->newLine();

        // Test Case 5: API Route Registration
        $this->line("[TEST 5] API Route Registration Check");
        $this->line(str_repeat("-", 80));

        try {
            $routes = app('router')->getRoutes();
            $apiFound = false;

            foreach ($routes as $route) {
                if ($route->uri === 'api/absensi' && in_array('POST', $route->methods)) {
                    $apiFound = true;
                    $this->info("✓ Route found: POST /api/absensi");
                    $this->line("  Middleware: " . implode(', ', $route->middleware()));
                    $this->line("  Controller: " . $route->getActionName());
                    break;
                }
            }

            if (!$apiFound) {
                $this->error("✗ API route POST /api/absensi not registered");
            }
        } catch (\Exception $e) {
            $this->error("✗ Route check failed: " . $e->getMessage());
        }
        $this->newLine();

        $this->info(str_repeat("=", 80));
        $this->info("API ENDPOINT TEST COMPLETE");
        $this->info(str_repeat("=", 80));

        return 0;
    }
}
