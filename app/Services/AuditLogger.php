<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function log(Request $request, string $action, string $description, ?int $userId = null): void
    {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('audit log write failed', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}