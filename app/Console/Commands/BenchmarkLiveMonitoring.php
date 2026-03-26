<?php

namespace App\Console\Commands;

use App\Http\Controllers\MonitoringLiveController;
use Illuminate\Console\Command;

class BenchmarkLiveMonitoring extends Command
{
    protected $signature = 'benchmark:live-monitoring {--runs=120 : Number of warm runs}';

    protected $description = 'Benchmark live monitoring payload generation speed';

    public function handle(): int
    {
        $runs = max(1, (int) $this->option('runs'));
        $controller = app(MonitoringLiveController::class);

        $this->info('Benchmarking live monitoring payload...');

        $coldStart = microtime(true);
        $controller->data()->getData(true);
        $coldElapsed = (microtime(true) - $coldStart) * 1000;

        $totalStart = microtime(true);
        for ($i = 0; $i < $runs; $i++) {
            $controller->data()->getData(true);
        }
        $totalElapsed = (microtime(true) - $totalStart) * 1000;

        $avg = $totalElapsed / $runs;

        $this->line('Cold run  : ' . number_format($coldElapsed, 2) . ' ms');
        $this->line('Warm runs : ' . $runs);
        $this->line('Total warm: ' . number_format($totalElapsed, 2) . ' ms');
        $this->line('Avg warm  : ' . number_format($avg, 2) . ' ms/request');

        if ($avg <= 50) {
            $this->info('Result    : Good for local-network scale.');
        } elseif ($avg <= 120) {
            $this->warn('Result    : Acceptable, monitor during peak usage.');
        } else {
            $this->error('Result    : Too slow, further optimization is needed.');
        }

        return self::SUCCESS;
    }
}
