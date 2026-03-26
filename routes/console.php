<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quotes()->random());
})->purpose('Display an inspiring quote');

Schedule::command('backup:database-local --keep=7')
    ->dailyAt('23:30')
    ->withoutOverlapping();
