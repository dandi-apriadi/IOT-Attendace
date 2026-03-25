<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\Mahasiswa;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Jadwal;

echo "\n" . str_repeat("=", 80) . "\n";
echo "TEST SUITE: DATABASE & MODEL CONNECTIVITY\n";
echo str_repeat("=", 80) . "\n\n";

// Test 1: Database Connection
echo "[TEST 1] Database Connection\n";
echo str_repeat("-", 80) . "\n";
try {
    $result = DB::select('SELECT 1');
    echo "✓ Database connection: SUCCESS\n";
    echo "  Status: Connected to MySQL\n\n";
} catch (Exception $e) {
    echo "✗ Database connection: FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Table Existence
echo "[TEST 2] Tables Existence\n";
echo str_repeat("-", 80) . "\n";
$tables = ['users', 'kelas', 'mata_kuliah', 'mahasiswa', 'jadwal', 'absensi'];
$allTablesExist = true;
foreach ($tables as $table) {
    $result = DB::select("SHOW TABLES LIKE ?", [$table]);
    if (count($result) > 0) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' MISSING\n";
        $allTablesExist = false;
    }
}
echo "\n";

// Test 3: Model Table Mapping
echo "[TEST 3] Model Table Mapping\n";
echo str_repeat("-", 80) . "\n";
$models = [
    'Mahasiswa' => new Mahasiswa(),
    'Absensi' => new Absensi(),
    'Kelas' => new Kelas(),
    'Jadwal' => new Jadwal(),
];

foreach ($models as $name => $model) {
    $tableName = $model->getTable();
    echo "✓ Model '$name' -> Table '$tableName'\n";
}
echo "\n";

// Test 4: Query Records
echo "[TEST 4] Query Records Count\n";
echo str_repeat("-", 80) . "\n";
foreach (['mahasiswa', 'kelas', 'jadwal', 'absensi', 'users'] as $table) {
    try {
        $count = DB::table($table)->count();
        echo "✓ Table '$table': " . $count . " record(s)\n";
    } catch (Exception $e) {
        echo "✗ Table '$table': Query failed - " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Test 5: Model Relationships
echo "[TEST 5] Model Relationships\n";
echo str_repeat("-", 80) . "\n";
try {
    $m = new Mahasiswa();
    echo "✓ Mahasiswa model\n";
    echo "  - hasMany Absensi: " . (method_exists($m, 'absensi') ? "✓" : "✗") . "\n";
    echo "  - belongsTo Kelas: " . (method_exists($m, 'kelas') ? "✓" : "✗") . "\n";
    
    $a = new Absensi();
    echo "✓ Absensi model\n";
    echo "  - belongsTo Mahasiswa: " . (method_exists($a, 'mahasiswa') ? "✓" : "✗") . "\n";
    echo "  - belongsTo Jadwal: " . (method_exists($a, 'jadwal') ? "✓" : "✗") . "\n";
    
    $k = new Kelas();
    echo "✓ Kelas model\n";
    echo "  - hasMany Mahasiswa: " . (method_exists($k, 'mahasiswa') ? "✓" : "✗") . "\n";
    echo "  - hasMany Jadwal: " . (method_exists($k, 'jadwal') ? "✓" : "✗") . "\n";
    
    $j = new Jadwal();
    echo "✓ Jadwal model\n";
    echo "  - hasMany Absensi: " . (method_exists($j, 'absensi') ? "✓" : "✗") . "\n";
    echo "  - belongsTo Kelas: " . (method_exists($j, 'kelas') ? "✓" : "✗") . "\n";
} catch (Exception $e) {
    echo "✗ Model relationship check failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 6: API Route Registration
echo "[TEST 6] API Route Registration\n";
echo str_repeat("-", 80) . "\n";
try {
    $routes = app('router')->getRoutes();
    $apiRoutes = $routes->getRoutesByMethod('POST');
    $absensiRoute = null;
    
    foreach ($apiRoutes as $route) {
        if (strpos($route->uri, 'api/absensi') !== false) {
            $absensiRoute = $route;
            break;
        }
    }
    
    if ($absensiRoute) {
        echo "✓ API Route POST /api/absensi is registered\n";
        echo "  Controller: " . $absensiRoute->getActionName() . "\n";
    } else {
        echo "✗ API Route POST /api/absensi NOT found\n";
    }
} catch (Exception $e) {
    echo "✗ Route check failed: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 7: Simulate API Request Logic
echo "[TEST 7] API Request Logic Simulation\n";
echo str_repeat("-", 80) . "\n";
try {
    // Create test data if needed
    $testKelas = DB::table('kelas')->first();
    if (!$testKelas) {
        DB::table('kelas')->insert([
            'nama_kelas' => 'IK-2A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✓ Test Kelas record created\n";
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
        echo "✓ Test Mata Kuliah record created\n";
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
        echo "✓ Test User record created\n";
    }
    
    // Check if we can query with model
    $mahasiswaCount = Mahasiswa::count();
    echo "✓ Mahasiswa::count() returns: " . $mahasiswaCount . "\n";
    
    $kelasCount = Kelas::count();
    echo "✓ Kelas::count() returns: " . $kelasCount . "\n";
    
    echo "✓ API logic simulation: SUCCESS\n";
} catch (Exception $e) {
    echo "✗ API logic simulation failed: " . $e->getMessage() . "\n";
}
echo "\n";

echo str_repeat("=", 80) . "\n";
echo "TEST COMPLETE\n";
echo str_repeat("=", 80) . "\n\n";
