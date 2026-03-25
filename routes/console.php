<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Illuminate\Foundation\Inspiring::quotes()->random());
})->purpose('Display an inspiring quote');
