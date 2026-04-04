<?php

/**
 * Script testing fitur-fitur utama IOT Attendance System
 * Jalankan: php scripts/test_all_features.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\SemesterAkademik;
use App\Models\MataKuliah;
use App\Models\Jadwal;
use App\Models\Kelas;
use App\Models\Absensi;

$passed = 0;
$failed = 0;
$errors = [];

function test($name, $callback) {
    global $passed, $failed, $errors;
    try {
        $result = $callback();
        if ($result === true) {
            echo "✅ PASS: $name\n";
            $passed++;
        } else {
            echo "❌ FAIL: $name - $result\n";
            $failed++;
            $errors[] = "$name: $result";
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: $name - " . $e->getMessage() . "\n";
        $failed++;
        $errors[] = "$name: " . $e->getMessage();
    }
}

echo "========================================\n";
echo "  IOT Attendance - Feature Testing\n";
echo "========================================\n\n";

// 1. Test Database Connection
test("Database Connection", function() {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return true;
    } catch (\Exception $e) {
        return "Database connection failed: " . $e->getMessage();
    }
});

// 2. Test Models Exist
test("Model: User", fn() => class_exists(User::class) ? true : "Model User tidak ditemukan");
test("Model: Mahasiswa", fn() => class_exists(Mahasiswa::class) ? true : "Model Mahasiswa tidak ditemukan");
test("Model: SemesterAkademik", fn() => class_exists(SemesterAkademik::class) ? true : "Model SemesterAkademik tidak ditemukan");
test("Model: MataKuliah", fn() => class_exists(MataKuliah::class) ? true : "Model MataKuliah tidak ditemukan");
test("Model: Jadwal", fn() => class_exists(Jadwal::class) ? true : "Model Jadwal tidak ditemukan");
test("Model: Kelas", fn() => class_exists(Kelas::class) ? true : "Model Kelas tidak ditemukan");
test("Model: Absensi", fn() => class_exists(Absensi::class) ? true : "Model Absensi tidak ditemukan");

// 3. Test Data Availability
test("Data: Users tersedia", function() {
    $count = User::count();
    return $count > 0 ? true : "Tidak ada data user";
});

test("Data: Kelas tersedia", function() {
    $count = Kelas::count();
    return $count > 0 ? true : "Tidak ada data kelas";
});

test("Data: Mahasiswa tersedia", function() {
    $count = Mahasiswa::count();
    return $count > 0 ? true : "Tidak ada data mahasiswa";
});

test("Data: SemesterAkademik tersedia", function() {
    $count = SemesterAkademik::count();
    return $count > 0 ? true : "Tidak ada data semester";
});

test("Data: MataKuliah tersedia", function() {
    $count = MataKuliah::count();
    return $count > 0 ? true : "Tidak ada data mata kuliah";
});

test("Data: Jadwal tersedia", function() {
    $count = Jadwal::count();
    return $count > 0 ? true : "Tidak ada data jadwal";
});

test("Data: Absensi tersedia", function() {
    $count = Absensi::count();
    return $count > 0 ? true : "Tidak ada data absensi";
});

// 4. Test Relationships
test("Relasi: Jadwal -> SemesterAkademik", function() {
    $jadwal = Jadwal::with('semesterAkademik')->first();
    return $jadwal ? true : "Tidak ada data jadwal untuk test relasi";
});

test("Relasi: Jadwal -> MataKuliah", function() {
    $jadwal = Jadwal::with('mata_kuliah')->first();
    return $jadwal ? true : "Tidak ada data jadwal untuk test relasi";
});

test("Relasi: Mahasiswa -> Absensi", function() {
    $mhs = Mahasiswa::with('absensi')->first();
    return $mhs ? true : "Tidak ada data mahasiswa untuk test relasi";
});

test("Relasi: Absensi -> Jadwal", function() {
    $absensi = Absensi::with('jadwal')->first();
    return $absensi ? true : "Tidak ada data absensi untuk test relasi";
});

// 5. Test StudentDetailController buildCourseProgress
test("Controller: StudentDetailController buildCourseProgress", function() {
    $controller = new \App\Http\Controllers\StudentDetailController();
    $mahasiswa = Mahasiswa::first();
    if (!$mahasiswa) return "Tidak ada data mahasiswa";
    
    $semester = SemesterAkademik::first();
    $semesterId = $semester ? (string) $semester->id : '';
    
    $presentStatuses = ['Hadir'];
    $excusedStatuses = ['Sakit', 'Izin'];
    $absentStatus = 'Alpa';
    
    // Gunakan reflection untuk akses private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('buildCourseProgress');
    $method->setAccessible(true);
    
    $result = $method->invoke($controller, $mahasiswa, $semesterId, $presentStatuses, $excusedStatuses, $absentStatus);
    
    return is_array($result) ? true : "Result bukan array";
});

// 6. Test Routes
test("Route: student-detail terdaftar", function() {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->getName() === 'student-detail') {
            return true;
        }
    }
    return "Route student-detail tidak ditemukan";
});

test("Route: student-detail.export.excel terdaftar", function() {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->getName() === 'student-detail.export.excel') {
            return true;
        }
    }
    return "Route student-detail.export.excel tidak ditemukan";
});

test("Route: student-detail.export.pdf terdaftar", function() {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    foreach ($routes as $route) {
        if ($route->getName() === 'student-detail.export.pdf') {
            return true;
        }
    }
    return "Route student-detail.export.pdf tidak ditemukan";
});

// 7. Test View Files
test("View: student-detail.blade.php ada", function() {
    $path = resource_path('views/master/student-detail.blade.php');
    return file_exists($path) ? true : "File view tidak ditemukan: $path";
});

// 8. Test Config
test("Config: attendance tersedia", function() {
    $config = config('attendance');
    return $config !== null ? true : "Config attendance tidak ditemukan";
});

// Summary
echo "\n========================================\n";
echo "  Testing Summary\n";
echo "========================================\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed > 0) {
    echo "\n❌ Errors:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

echo "\n========================================\n";
echo $failed === 0 ? "🎉 SEMUA TEST BERHASIL!\n" : "⚠️ ADA TEST YANG GAGAL\n";
echo "========================================\n";

exit($failed > 0 ? 1 : 0);
