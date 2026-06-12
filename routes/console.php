<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quotes()->random());
})->purpose('Display an inspiring quote');

Schedule::command('backup:database-local --keep=7')
    ->dailyAt('23:30')
    ->withoutOverlapping();

Schedule::command('zkteco:pull')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/zkteco-pull.log'));

Schedule::command('students:promote-semester --execute --due-only')
    ->dailyAt('00:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/student-semester-promotion-auto.log'));
