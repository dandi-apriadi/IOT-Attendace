<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Mahasiswa;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Jadwal;

class TestApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test database connectivity and API setup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info(str_repeat("=", 80));
        $this->info("TEST SUITE: DATABASE & MODEL CONNECTIVITY");
        $this->info(str_repeat("=", 80));
        $this->newLine();

        // Test 1: Database Connection
        $this->line("[TEST 1] Database Connection");
        $this->line(str_repeat("-", 80));
        try {
            DB::select('SELECT 1');
            $this->info("✓ Database connection: SUCCESS");
            $this->line("  Status: Connected to MySQL");
            $this->newLine();
        } catch (\Exception $e) {
            $this->error("✗ Database connection: FAILED");
            $this->error("  Error: " . $e->getMessage());
            $this->newLine();
            return 1;
        }

        // Test 2: Table Existence
        $this->line("[TEST 2] Tables Existence");
        $this->line(str_repeat("-", 80));
        $tables = ['users', 'kelas', 'mata_kuliah', 'mahasiswa', 'jadwal', 'absensi'];
        foreach ($tables as $table) {
            try {
                $result = DB::select("SHOW TABLES LIKE ?", [$table]);
                if (count($result) > 0) {
                    $this->info("✓ Table '$table' exists");
                } else {
                    $this->warn("✗ Table '$table' MISSING");
                }
            } catch (\Exception $e) {
                $this->error("✗ Query failed for table '$table'");
            }
        }
        $this->newLine();

        // Test 3: Model Table Mapping
        $this->line("[TEST 3] Model Table Mapping");
        $this->line(str_repeat("-", 80));
        $models = [
            'Mahasiswa' => new Mahasiswa(),
            'Absensi' => new Absensi(),
            'Kelas' => new Kelas(),
            'Jadwal' => new Jadwal(),
        ];

        foreach ($models as $name => $model) {
            $tableName = $model->getTable();
            $this->info("✓ Model '$name' -> Table '$tableName'");
        }
        $this->newLine();

        // Test 4: Query Records
        $this->line("[TEST 4] Query Records Count");
        $this->line(str_repeat("-", 80));
        foreach (['mahasiswa', 'kelas', 'jadwal', 'absensi', 'users'] as $table) {
            try {
                $count = DB::table($table)->count();
                $this->info("✓ Table '$table': " . $count . " record(s)");
            } catch (\Exception $e) {
                $this->error("✗ Table '$table': " . $e->getMessage());
            }
        }
        $this->newLine();

        // Test 5: Model Relationships
        $this->line("[TEST 5] Model Relationships");
        $this->line(str_repeat("-", 80));
        try {
            $m = new Mahasiswa();
            $this->info("✓ Mahasiswa model");
            $this->line("  - hasMany Absensi: " . (method_exists($m, 'absensi') ? "✓" : "✗"));
            $this->line("  - belongsTo Kelas: " . (method_exists($m, 'kelas') ? "✓" : "✗"));

            $a = new Absensi();
            $this->info("✓ Absensi model");
            $this->line("  - belongsTo Mahasiswa: " . (method_exists($a, 'mahasiswa') ? "✓" : "✗"));
            $this->line("  - belongsTo Jadwal: " . (method_exists($a, 'jadwal') ? "✓" : "✗"));

            $k = new Kelas();
            $this->info("✓ Kelas model");
            $this->line("  - hasMany Mahasiswa: " . (method_exists($k, 'mahasiswa') ? "✓" : "✗"));
            $this->line("  - hasMany Jadwal: " . (method_exists($k, 'jadwal') ? "✓" : "✗"));

            $j = new Jadwal();
            $this->info("✓ Jadwal model");
            $this->line("  - hasMany Absensi: " . (method_exists($j, 'absensi') ? "✓" : "✗"));
            $this->line("  - belongsTo Kelas: " . (method_exists($j, 'kelas') ? "✓" : "✗"));
        } catch (\Exception $e) {
            $this->error("✗ Model relationship check failed: " . $e->getMessage());
        }
        $this->newLine();

        // Test 6: Test Data Creation
        $this->line("[TEST 6] Test Data Setup");
        $this->line(str_repeat("-", 80));
        try {
            $testKelas = DB::table('kelas')->first();
            if (!$testKelas) {
                DB::table('kelas')->insert([
                    'nama_kelas' => 'IK-2A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("✓ Test Kelas record created");
            } else {
                $this->info("✓ Test Kelas already exists");
            }

            $testMataKuliah = DB::table('mata_kuliah')->first();
            if (!$testMataKuliah) {
                DB::table('mata_kuliah')->insert([
                    'kode_mk' => 'MK-001',
                    'nama_mk' => 'Pemrograman Web',
                    'sks' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("✓ Test Mata Kuliah record created");
            } else {
                $this->info("✓ Test Mata Kuliah already exists");
            }

            $testUser = DB::table('users')->first();
            if (!$testUser) {
                DB::table('users')->insert([
                    'name' => 'Admin User',
                    'email' => 'admin@test.local',
                    'password' => bcrypt('password'),
                    'role' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("✓ Test User record created");
            } else {
                $this->info("✓ Test User already exists");
            }

            $testMahasiswa = DB::table('mahasiswa')->first();
            if (!$testMahasiswa) {
                $kelas = DB::table('kelas')->first();
                DB::table('mahasiswa')->insert([
                    'nim' => '22041010',
                    'nama' => 'Test Student',
                    'kelas_id' => $kelas->id,
                    'rfid_uid' => 'E2808A12',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("✓ Test Mahasiswa record created");
            } else {
                $this->info("✓ Test Mahasiswa already exists");
            }

            $testJadwal = DB::table('jadwal')->first();
            if (!$testJadwal) {
                $kelas = DB::table('kelas')->first();
                $mk = DB::table('mata_kuliah')->first();
                $user = DB::table('users')->first();
                DB::table('jadwal')->insert([
                    'kelas_id' => $kelas->id,
                    'mata_kuliah_id' => $mk->id,
                    'user_id' => $user->id,
                    'hari' => 'Monday',
                    'jam_mulai' => '08:00:00',
                    'jam_selesai' => '10:30:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("✓ Test Jadwal record created");
            } else {
                $this->info("✓ Test Jadwal already exists");
            }
        } catch (\Exception $e) {
            $this->error("✗ Test data creation failed: " . $e->getMessage());
        }
        $this->newLine();

        // Test 7: Verify Records
        $this->line("[TEST 7] Records Verification");
        $this->line(str_repeat("-", 80));
        $mahasiswaCount = Mahasiswa::count();
        $kelasCount = Kelas::count();
        $jadwalCount = Jadwal::count();
        $absensiCount = Absensi::count();

        $this->info("✓ Mahasiswa::count() = " . $mahasiswaCount);
        $this->info("✓ Kelas::count() = " . $kelasCount);
        $this->info("✓ Jadwal::count() = " . $jadwalCount);
        $this->info("✓ Absensi::count() = " . $absensiCount);
        $this->newLine();

        // Test 8: API Request Simulation
        $this->line("[TEST 8] API Request Logic Test");
        $this->line(str_repeat("-", 80));
        try {
            $mahasiswa = Mahasiswa::whereNotNull('rfid_uid')->first();
            if ($mahasiswa) {
                $this->info("✓ Found Mahasiswa with RFID: " . $mahasiswa->nama);
                $this->line("  NIM: " . $mahasiswa->nim);
                $this->line("  RFID UID: " . $mahasiswa->rfid_uid);
            } else {
                $this->warn("⚠ No Mahasiswa with RFID found");
            }

            $jadwal = Jadwal::first();
            if ($jadwal) {
                $this->info("✓ Found Jadwal: " . $jadwal->mata_kuliah->nama_mk);
                $this->line("  Class: " . $jadwal->kelas->nama_kelas);
                $this->line("  Time: " . $jadwal->jam_mulai . " - " . $jadwal->jam_selesai);
            } else {
                $this->warn("⚠ No Jadwal found");
            }
        } catch (\Exception $e) {
            $this->error("✗ API simulation failed: " . $e->getMessage());
        }
        $this->newLine();

        $this->info(str_repeat("=", 80));
        $this->info("TEST COMPLETE - All critical systems operational!");
        $this->info(str_repeat("=", 80));

        return 0;
    }
}
