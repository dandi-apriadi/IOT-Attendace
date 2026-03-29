<?php

use Illuminate\Support\Facades\Route;
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
