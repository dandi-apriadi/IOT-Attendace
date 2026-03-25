<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = [];
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $content = file_get_contents($logFile);
                $lines = explode("\n", $content);
                foreach (array_reverse($lines) as $line) {
                    if (trim($line)) {
                        // Parse log line
                        if (strpos($line, 'ERROR') !== false) {
                            $logs[] = ['type' => 'error', 'message' => trim(preg_replace('/<.*?>/', '', $line))];
                        } elseif (strpos($line, 'WARNING') !== false) {
                            $logs[] = ['type' => 'unauthorized', 'message' => trim(preg_replace('/<.*?>/', '', $line))];
                        } else {
                            $logs[] = ['type' => 'auth', 'message' => trim(preg_replace('/<.*?>/', '', $line))];
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error reading logs: ' . $e->getMessage());
        }

        return view('reports.audit', ['logs' => array_slice($logs, 0, 100)]);
    }
}
