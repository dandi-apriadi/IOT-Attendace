<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentBridgeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\DeviceEnrollmentController;
use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\ReferenceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::post('/absensi', [AttendanceController::class, 'store'])
	->middleware(['device.token', 'throttle:120,1']);

Route::middleware(['device.token', 'throttle:120,1'])->group(function () {
	Route::post('/device/heartbeat', [DeviceEnrollmentController::class, 'heartbeat']);
	Route::get('/device/enrollment/next-command', [DeviceEnrollmentController::class, 'nextCommand']);
	Route::post('/device/enrollment/jobs/{job}/result', [DeviceEnrollmentController::class, 'submitResult']);
});

// Jembatan AGENT lokal <-> SERVER (VPS). Agent ZKTeco standalone memakai
// endpoint ini untuk push absensi dan menjalankan antrean perintah.
Route::prefix('agent')->middleware(['device.token', 'throttle:120,1'])->group(function () {
	Route::post('/attendance', [AgentBridgeController::class, 'ingestAttendance']);
	Route::get('/commands/next', [AgentBridgeController::class, 'nextCommand']);
	Route::post('/commands/{command}/result', [AgentBridgeController::class, 'submitResult']);
});

// API mobile (aplikasi Flutter monitoring) — auth via Sanctum token, terpisah
// dari device-token API di atas yang dipakai alat IoT.
Route::prefix('v1')->group(function () {
	Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

	Route::middleware(['auth:sanctum', 'role:admin,dosen', 'throttle:60,1'])->group(function () {
		Route::post('/logout', [AuthController::class, 'logout']);
		Route::get('/me', [AuthController::class, 'me']);

		Route::get('/monitoring/live', [MonitoringController::class, 'live']);
		Route::get('/devices', [MonitoringController::class, 'devices']);
		Route::post('/devices/{device}/ping', [MonitoringController::class, 'pingDevice']);
		Route::get('/attendance/history', [MonitoringController::class, 'attendanceHistory']);
		Route::get('/dashboard/summary', [MonitoringController::class, 'dashboardSummary']);

		Route::get('/kelas', [ReferenceController::class, 'kelas']);
		Route::get('/mata-kuliah', [ReferenceController::class, 'mataKuliah']);
		Route::get('/jadwal', [ReferenceController::class, 'jadwal']);

		Route::get('/audit-log', [AdminController::class, 'auditLog']);
		Route::get('/reports/student-summary', [AdminController::class, 'studentReport']);
	});
});
