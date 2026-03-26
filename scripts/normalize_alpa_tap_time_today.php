<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$today = Carbon::today()->toDateString();

$updated = DB::table('absensi')
    ->whereDate('tanggal', $today)
    ->where('status', 'Alpa')
    ->where('waktu_tap', '!=', '00:00:00')
    ->update([
        'waktu_tap' => '00:00:00',
        'updated_at' => now(),
    ]);

echo json_encode([
    'date' => $today,
    'updated_alpa_rows' => $updated,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
