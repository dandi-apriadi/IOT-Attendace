<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BillboardController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DosenSessionController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MonitoringHealthController;
use App\Http\Controllers\MonitoringLiveController;
use App\Http\Controllers\MonitoringPerformanceController;
use App\Http\Controllers\MonitoringViewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StudentDetailController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Web Routes - IoT Attendance System Prototype
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/', function () { return view('login'); })->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.attempt');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/public/billboard', [BillboardController::class, 'index'])->name('public-display');

Route::middleware(['auth', 'role:admin,dosen'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Operational
    Route::get('/dosen/session', [DosenSessionController::class, 'create'])->name('dosen-session');
    Route::get('/monitoring/live', [MonitoringLiveController::class, 'index'])->name('monitoring');
    Route::get('/monitoring/live/data', [MonitoringLiveController::class, 'data'])->name('monitoring.live.data');
    Route::get('/monitoring/health', [MonitoringHealthController::class, 'index'])->name('iot-health');
    Route::get('/monitoring/performance/reports', [MonitoringPerformanceController::class, 'reports'])->name('monitoring.performance.reports');
    Route::get('/monitoring/performance/reports-view', [MonitoringViewController::class, 'performanceReports'])->name('monitoring.performance.view');

    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('settings');
    Route::post('/profile/settings', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/student/{id}', [StudentDetailController::class, 'show'])->name('student-detail');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Master Data
    Route::get('/master/mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa');
    Route::get('/master/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'show'])->name('mahasiswa.show');
    Route::post('/master/mahasiswa', [MahasiswaController::class, 'store'])->name('mahasiswa.store');
    Route::get('/master/mahasiswa/{mahasiswa}/edit', [MahasiswaController::class, 'edit'])->name('mahasiswa.edit');
    Route::put('/master/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('mahasiswa.update');
    Route::delete('/master/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');
    Route::get('/master/matakuliah', [MasterDataController::class, 'mataKuliah'])->name('matakuliah');
    Route::post('/master/matakuliah', [MasterDataController::class, 'storeMataKuliah'])->name('matakuliah.store');
    Route::get('/master/matakuliah/{id}/edit', [MasterDataController::class, 'editMataKuliah'])->name('matakuliah.edit');
    Route::put('/master/matakuliah/{id}', [MasterDataController::class, 'updateMataKuliah'])->name('matakuliah.update');
    Route::delete('/master/matakuliah/{id}', [MasterDataController::class, 'destroyMataKuliah'])->name('matakuliah.destroy');
    Route::get('/master/kelas', [MasterDataController::class, 'kelas'])->name('kelas');
    Route::get('/master/jadwal', [MasterDataController::class, 'jadwal'])->name('jadwal');
    Route::get('/master/users', [UserController::class, 'index'])->name('users');
    Route::get('/master/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/master/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/master/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/master/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/master/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/master/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Admin Reports
    Route::get('/reports/audit', [AuditLogController::class, 'index'])->name('audit-log');
    Route::get('/reports/correction', [CorrectionController::class, 'index'])->name('correction');
    Route::get('/reports/correction/create', [CorrectionController::class, 'create'])->name('correction.create');
    Route::post('/reports/correction', [CorrectionController::class, 'store'])->name('correction.store');
    Route::get('/reports/correction/{correction}/edit', [CorrectionController::class, 'edit'])->name('correction.edit');
    Route::put('/reports/correction/{correction}', [CorrectionController::class, 'update'])->name('correction.update');
});
