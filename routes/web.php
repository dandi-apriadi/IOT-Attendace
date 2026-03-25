<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

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

Route::get('/public/billboard', function () { return view('public.billboard'); })->name('public-display');

Route::middleware(['auth', 'role:admin,dosen'])->group(function () {
    Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

    // Operational
    Route::get('/dosen/session', function () { return view('dosen.session'); })->name('dosen-session');
    Route::get('/monitoring/live', function () { return view('monitoring.live'); })->name('monitoring');
    Route::get('/monitoring/health', function () { return view('monitoring.health'); })->name('iot-health');

    // Reports
    Route::get('/reports', function () { return view('reports.index'); })->name('reports');

    Route::get('/profile/settings', function () { return view('profile.settings'); })->name('settings');
    Route::get('/student/{id}', function ($id) { return view('master.student-detail', ['id' => $id]); })->name('student-detail');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    // Master Data
    Route::get('/master/mahasiswa', function () { return view('master.mahasiswa'); })->name('mahasiswa');
    Route::get('/master/matakuliah', function () { return view('master.matakuliah'); })->name('matakuliah');
    Route::get('/master/kelas', function () { return view('master.kelas'); })->name('kelas');
    Route::get('/master/jadwal', function () { return view('master.jadwal'); })->name('jadwal');
    Route::get('/master/users', function () { return view('master.users'); })->name('users');

    // Admin Reports
    Route::get('/reports/audit', function () { return view('reports.audit'); })->name('audit-log');
    Route::get('/reports/correction', function () { return view('reports.correction'); })->name('correction');
});
