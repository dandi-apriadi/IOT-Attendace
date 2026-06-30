<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quotes()->random());
})->purpose('Display an inspiring quote');

Schedule::command('backup:database-local --keep=7')
    ->dailyAt('23:30')
    ->withoutOverlapping();

// Hanya jalankan penarikan langsung saat aplikasi satu jaringan dengan alat
// (mode standalone). Di VPS (mode server) absensi masuk via push dari agent.
if (config('agent.role') === 'standalone') {
    Schedule::command('zkteco:pull')
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/zkteco-pull.log'));
}

// Di VPS, biometrik ditarik lewat agent lokal secara otomatis. Agent akan
// mengeksekusi pull_biometrics dan hasilnya dipakai untuk sync lintas alat.
if (config('agent.role') === 'server') {
    Schedule::command('zkteco:auto-sync-biometrics')
        ->everyFifteenMinutes()
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/zkteco-biometric-auto-sync.log'));
}

Schedule::command('students:promote-semester --execute --due-only')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/student-semester-promotion-auto.log'));
