<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentBridgeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\DeviceEnrollmentController;

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
