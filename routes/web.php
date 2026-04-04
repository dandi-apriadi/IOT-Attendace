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
    Route::redirect('/mahasiswa', '/master/mahasiswa');

    // Operational
    Route::get('/dosen/mata-kuliah', [DosenSessionController::class, 'courses'])->name('dosen-courses');
    Route::get('/dosen/schedule/detail', [DosenSessionController::class, 'detailByFilter'])->name('dosen-schedule.detail');
    Route::get('/dosen/schedule/detail/export/excel', [DosenSessionController::class, 'exportExcel'])->name('dosen-schedule.detail.export.excel');
    Route::get('/dosen/schedule/detail/export/pdf', [DosenSessionController::class, 'exportPdf'])->name('dosen-schedule.detail.export.pdf');
    Route::post('/dosen/schedule/start', [DosenSessionController::class, 'store'])->name('dosen-schedule.start');
    Route::delete('/dosen/schedule/stop', [DosenSessionController::class, 'destroy'])->name('dosen-schedule.stop');
    Route::get('/monitoring/live', [MonitoringLiveController::class, 'index'])->name('monitoring');
    Route::get('/monitoring/live/data', [MonitoringLiveController::class, 'data'])->name('monitoring.live.data');
    Route::get('/monitoring/live/{absensi}/edit', [MonitoringLiveController::class, 'edit'])->name('monitoring.live.edit');
    Route::put('/monitoring/live/{absensi}', [MonitoringLiveController::class, 'update'])->name('monitoring.live.update');
    Route::get('/monitoring/health', [MonitoringHealthController::class, 'index'])->name('iot-health');
    Route::get('/monitoring/performance/reports', [MonitoringPerformanceController::class, 'reports'])->name('monitoring.performance.reports');
    Route::get('/monitoring/performance/reports-view', [MonitoringViewController::class, 'performanceReports'])->name('monitoring.performance.view');

    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/excel', [ReportsController::class, 'exportExcel'])->name('reports.export.excel');
    Route::get('/reports/export/pdf', [ReportsController::class, 'exportPdf'])->name('reports.export.pdf');

    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('settings');
    Route::post('/profile/settings', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/student/{id}', [StudentDetailController::class, 'show'])->name('student-detail');
    Route::get('/student/{id}/export/excel', [StudentDetailController::class, 'exportExcel'])->name('student-detail.export.excel');
    Route::get('/student/{id}/export/pdf', [StudentDetailController::class, 'exportPdf'])->name('student-detail.export.pdf');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Master Data
    Route::get('/master/mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa');
    Route::get('/master/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'show'])->name('mahasiswa.show');
    Route::post('/master/mahasiswa', [MahasiswaController::class, 'store'])->name('mahasiswa.store');
    Route::get('/master/mahasiswa/{mahasiswa}/edit', [MahasiswaController::class, 'edit'])->name('mahasiswa.edit');
    Route::post('/master/mahasiswa/{mahasiswa}/enrollment/start', [MahasiswaController::class, 'startEnrollment'])->name('mahasiswa.enrollment.start');
    Route::get('/master/mahasiswa/{mahasiswa}/enrollment/{job}/status', [MahasiswaController::class, 'enrollmentStatus'])->name('mahasiswa.enrollment.status');
    Route::post('/master/mahasiswa/{mahasiswa}/enrollment/{job}/cancel', [MahasiswaController::class, 'cancelEnrollment'])->name('mahasiswa.enrollment.cancel');
    Route::put('/master/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('mahasiswa.update');
    Route::delete('/master/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');
    Route::get('/master/matakuliah', [MasterDataController::class, 'mataKuliah'])->name('matakuliah');
    Route::post('/master/matakuliah', [MasterDataController::class, 'storeMataKuliah'])->name('matakuliah.store');
    Route::get('/master/matakuliah/{id}/edit', [MasterDataController::class, 'editMataKuliah'])->name('matakuliah.edit');
    Route::put('/master/matakuliah/{id}', [MasterDataController::class, 'updateMataKuliah'])->name('matakuliah.update');
    Route::delete('/master/matakuliah/{id}', [MasterDataController::class, 'destroyMataKuliah'])->name('matakuliah.destroy');
    Route::get('/master/matakuliah/{id}/report', [MasterDataController::class, 'mataKuliahReport'])->name('matakuliah.report');
    Route::get('/master/matakuliah/{id}/report/export', [MasterDataController::class, 'mataKuliahReportExport'])->name('matakuliah.report.export');
    Route::get('/master/matakuliah/{id}/report/student/{mahasiswaId}', [MasterDataController::class, 'mataKuliahStudentDetail'])->name('matakuliah.report.student');
    Route::get('/master/kelas', [MasterDataController::class, 'kelas'])->name('kelas');
    Route::post('/master/kelas', [MasterDataController::class, 'storeKelas'])->name('kelas.store');
    Route::get('/master/kelas/{id}/edit', [MasterDataController::class, 'editKelas'])->name('kelas.edit');
    Route::put('/master/kelas/{id}', [MasterDataController::class, 'updateKelas'])->name('kelas.update');
    Route::delete('/master/kelas/{id}', [MasterDataController::class, 'destroyKelas'])->name('kelas.destroy');
    Route::get('/master/jadwal', [MasterDataController::class, 'jadwal'])->name('jadwal');
    Route::post('/master/jadwal', [MasterDataController::class, 'storeJadwal'])->name('jadwal.store');
    Route::get('/master/jadwal/{id}/edit', [MasterDataController::class, 'editJadwal'])->name('jadwal.edit');
    Route::put('/master/jadwal/{id}', [MasterDataController::class, 'updateJadwal'])->name('jadwal.update');
    Route::delete('/master/jadwal/{id}', [MasterDataController::class, 'destroyJadwal'])->name('jadwal.destroy');
    Route::get('/master/semester', [MasterDataController::class, 'semester'])->name('semester');
    Route::post('/master/semester', [MasterDataController::class, 'storeSemester'])->name('semester.store');
    Route::get('/master/semester/{id}/edit', [MasterDataController::class, 'editSemester'])->name('semester.edit');
    Route::put('/master/semester/{id}', [MasterDataController::class, 'updateSemester'])->name('semester.update');
    Route::delete('/master/semester/{id}', [MasterDataController::class, 'destroySemester'])->name('semester.destroy');
    Route::post('/master/semester/{id}/set-active', [MasterDataController::class, 'setActiveSemester'])->name('semester.set-active');
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
